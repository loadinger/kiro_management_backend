# 设计文档：article-slug-generation

## 概述

为 Filmly 管理后台新增 `articles:generate-slugs` Artisan Command，通过复用现有 `LlmTranslationService`（Ollama + Qwen 2.5 7B）将中文文章标题翻译为英文 slug，并写回 `articles.slug` 字段。

核心设计目标：
- 只处理 `slug IS NULL` 的记录，天然支持断点续传
- 复用 `LlmTranslationService` 的批量调用和重试逻辑，避免重复实现
- 通过构造函数注入自定义 System Prompt，扩展 `LlmTranslationService` 而不破坏现有调用方
- 严格遵循项目分层架构：Command → Service → LlmTranslationService

---

## 架构

```
php artisan articles:generate-slugs
  └── GenerateSlugsCommand（参数解析、进度展示、统计输出）
        └── ArticleSlugService（查询待处理记录、格式化 slug、写回数据库）
              └── LlmTranslationService（封装 Ollama 调用，支持自定义 prompt）
                    └── Ollama /api/chat（format: "json"）
```

### 关键设计决策：如何扩展 LlmTranslationService 支持自定义 System Prompt

**问题**：`LlmTranslationService` 的 `SYSTEM_PROMPT` 是 `private const`，`ArticleSlugService` 需要使用完全不同的 prompt（生成 slug 而非翻译为中文）。

**方案对比**：

| 方案 | 描述 | 优缺点 |
|------|------|--------|
| A：修改 `LlmTranslationService`，构造函数接收可选 `$systemPrompt` | 通过 DI 注入自定义 prompt | ✅ 符合项目 DI 规范；✅ 不破坏现有调用方（默认值保持原 prompt）；✅ 无需子类化 |
| B：子类化 `LlmTranslationService` | 创建 `ArticleSlugLlmService` 继承并覆盖 prompt | ❌ 继承关系语义不清晰；❌ 需要额外绑定 |
| C：在 `ArticleSlugService` 中直接调用 Ollama HTTP | 绕过 `LlmTranslationService` | ❌ 重复实现重试逻辑；❌ 违反 DRY 原则 |

**选择方案 A**：修改 `LlmTranslationService` 构造函数，新增可选参数 `?string $systemPrompt = null`。当传入非 null 值时使用自定义 prompt，否则使用原有 `SYSTEM_PROMPT` 常量。

这是最小侵入性的修改，完全向后兼容，符合项目构造函数注入规范。

---

## 组件与接口

### 1. LlmTranslationService（修改）

**修改内容**：构造函数新增可选参数 `?string $systemPrompt = null`，内部用 `$this->systemPrompt` 替代 `self::SYSTEM_PROMPT`。

```php
class LlmTranslationService
{
    private const SYSTEM_PROMPT = '...'; // 保持不变，作为默认值

    private readonly string $systemPrompt;

    public function __construct(?string $systemPrompt = null)
    {
        $this->systemPrompt = $systemPrompt ?? self::SYSTEM_PROMPT;
    }

    // translateBatch 签名不变
    public function translateBatch(array $items, string $tableType, ?string $context = null): array
}
```

现有调用方（`TranslationService`）无需任何修改，因为构造函数参数有默认值，Laravel 容器自动解析时会使用默认值。

### 2. ArticleSlugService（新建）

**职责**：
- 查询 `slug IS NULL` 的文章（游标分页）
- 过滤空 title 记录
- 调用 `LlmTranslationService` 生成 slug
- 格式化 slug（纯函数，可独立测试）
- 处理唯一性冲突（追加 `-2`、`-3` 后缀）
- 在事务中写回数据库
- 返回统计结果

```php
class ArticleSlugService
{
    public function __construct(
        private readonly LlmTranslationService $llmTranslationService,
    ) {}

    /**
     * Generate slugs for articles where slug IS NULL.
     *
     * @return array{success: int, skipped_batches: int}
     */
    public function generateSlugs(
        int $batchSize = 10,
        ?int $limit = null,
        ?callable $onProgress = null,
    ): array

    /**
     * Format a raw LLM translation into a valid slug.
     * Pure function: lowercase, replace spaces/underscores with hyphens,
     * remove non-ASCII-letter/hyphen chars, collapse hyphens, trim, truncate to 120.
     */
    public function formatSlug(string $raw): string

    private function countPending(?int $limit): int
    private function fetchBatch(int $afterId, int $batchSize): Collection
    private function resolveUniqueSlug(string $slug, int $excludeId): ?string
    private function writeBatch(array $slugMap): void
}
```

**注意**：`ArticleSlugService` 需要一个使用 slug-generation prompt 的 `LlmTranslationService` 实例。由于 Laravel 容器默认无参数构造 `LlmTranslationService`（使用翻译 prompt），需要在 `AppServiceProvider` 中为 `ArticleSlugService` 注册绑定，手动传入 slug prompt。

### 3. GenerateSlugsCommand（新建）

**职责**：参数解析、参数验证、进度条管理、统计输出、异常捕获。

```php
class GenerateSlugsCommand extends Command
{
    protected $signature = 'articles:generate-slugs
        {--batch-size=10 : Number of articles per LLM call}
        {--limit= : Maximum number of articles to process}';

    public function __construct(private readonly ArticleSlugService $articleSlugService)
    {
        parent::__construct();
    }

    public function handle(): int
}
```

---

## 数据模型

### articles 表（已存在，只写 slug 字段）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | 游标分页的基准字段 |
| title | varchar | 中文标题，LLM 输入源 |
| slug | varchar UNIQUE NULL | 目标字段，NULL 表示待处理 |
| （其他字段） | — | 本功能不读取也不修改 |

### 查询模式

```sql
-- 计数（用于进度条总量）
SELECT COUNT(*) FROM articles WHERE slug IS NULL [AND id <= (SELECT MAX(id) FROM articles WHERE slug IS NULL)]

-- 游标分页批量查询
SELECT id, title FROM articles
WHERE slug IS NULL AND id > :afterId
ORDER BY id ASC
LIMIT :batchSize
```

### slug 唯一性冲突解决

```
原始 slug: "avengers-alliance"
冲突时尝试: "avengers-alliance-2", "avengers-alliance-3", ..., "avengers-alliance-99"
超过 99 仍冲突: 跳过该记录（不写入）
```

---

## 正确性属性

*属性是在系统所有有效执行中都应成立的特征或行为——本质上是关于系统应该做什么的形式化陈述。属性是人类可读规范与机器可验证正确性保证之间的桥梁。*

### 属性 1：slug 格式化不变量

*对于任意* 字符串输入，`formatSlug()` 的输出应满足以下所有约束：
- 只包含 ASCII 小写字母（`a-z`）和连字符（`-`）
- 不以连字符开头或结尾
- 不包含连续连字符
- 长度不超过 120 个字符
- 若输出非空，则不以连字符结尾（截断后也需满足）

**验证：需求 4.2、4.3**

### 属性 2：空/null title 过滤

*对于任意* 文章集合，其中 title 为空字符串或 null 的记录，不应出现在传递给 `LlmTranslationService::translateBatch()` 的 `$items` 数组中。

**验证：需求 6.3**

### 属性 3：唯一性冲突解决

*对于任意* 基础 slug 和任意已占用的 slug 集合（后缀 2 到 N，N < 99），`resolveUniqueSlug()` 应返回后缀最小的未占用值；若所有后缀（2-99）均已占用，则返回 null。

**验证：需求 5.2**

### 属性 4：写入正确性

*对于任意* 批次的翻译结果，成功写入数据库后：
- `success` 统计等于实际写入的记录数
- 只有 `slug` 字段被更新，文章的其他字段（`title`、`status`、`updated_at` 等）保持不变

**验证：需求 5.4、5.5**

### 属性 5：limit 约束

*对于任意* `$limit` 值（正整数）和任意数据集，`generateSlugs()` 处理的文章总数不超过 `$limit`。

**验证：需求 3.3**

---

## 错误处理

| 场景 | 处理方式 | 层级 |
|------|---------|------|
| `--batch-size < 1` | 输出错误信息，返回 `FAILURE` | Command |
| `--limit < 1` | 输出错误信息，返回 `FAILURE` | Command |
| `ConnectionException`（Ollama 不可达） | Command 捕获，输出错误，返回 `FAILURE` | Command |
| LLM 返回空结果（批次失败） | 跳过该批次，`skipped_batches++`，继续下一批 | Service |
| 格式化后 slug 为空字符串 | 跳过该记录，不写入 | Service |
| title 为空/null | 过滤掉，不发送给 LLM | Service |
| 唯一性冲突，后缀超过 99 | 跳过该记录，不写入 | Service |
| 数据库事务失败 | 回滚，重新抛出异常，由 Command 捕获后输出错误，返回 `FAILURE` | Service → Command |

---

## 测试策略

### 单元测试（Unit Test）

**`ArticleSlugServiceTest`**（`tests/Unit/Services/ArticleSlugServiceTest.php`）

重点测试纯逻辑方法，使用 mock 隔离数据库和 LLM 依赖：

- `formatSlug()` 的各种输入场景：
  - 中文字符被移除
  - 空格/下划线转连字符
  - 大写转小写
  - 连续连字符合并
  - 首尾连字符去除
  - 超过 120 字符截断（截断后不以连字符结尾）
  - 全部字符被移除后返回空字符串
- `generateSlugs()` 的行为：
  - 空 title 记录被过滤，不传给 LLM
  - LLM 返回空结果时 `skipped_batches` 加 1
  - 唯一性冲突时追加后缀
  - 后缀超过 99 时跳过记录
  - `limit` 约束生效

**属性测试**（使用 [eris](https://github.com/giorgiosironi/eris) 或 [PHPCheck](https://github.com/vimeo/psalm) 等 PHP PBT 库）

本项目选用 **[giorgiosironi/eris](https://github.com/giorgiosironi/eris)**（PHP 属性测试库，支持 PHPUnit 集成）。

每个属性测试最少运行 100 次迭代。

```php
// 属性 1：slug 格式化不变量
// Feature: article-slug-generation, Property 1: slug format invariant
public function test_format_slug_output_satisfies_slug_constraints(): void
{
    $this->forAll(Generator\string())
        ->then(function (string $input): void {
            $result = $this->service->formatSlug($input);
            // 只含 a-z 和 -
            $this->assertMatchesRegularExpression('/^[a-z-]*$/', $result);
            // 不以 - 开头或结尾
            if ($result !== '') {
                $this->assertStringStartsNotWith('-', $result);
                $this->assertStringEndsNotWith('-', $result);
            }
            // 不含连续 --
            $this->assertStringNotContainsString('--', $result);
            // 长度不超过 120
            $this->assertLessThanOrEqual(120, strlen($result));
        });
}

// 属性 2：空/null title 过滤
// Feature: article-slug-generation, Property 2: empty title filtering
public function test_empty_title_articles_not_sent_to_llm(): void

// 属性 3：唯一性冲突解决
// Feature: article-slug-generation, Property 3: unique slug resolution
public function test_resolve_unique_slug_returns_unoccupied_minimum_suffix(): void

// 属性 4：写入正确性
// Feature: article-slug-generation, Property 4: write correctness
public function test_only_slug_field_is_updated(): void

// 属性 5：limit 约束
// Feature: article-slug-generation, Property 5: limit constraint
public function test_processed_count_never_exceeds_limit(): void
```

### 功能测试（Feature Test / Command Test）

**`GenerateSlugsCommandTest`**（`tests/Feature/Commands/GenerateSlugsCommandTest.php`）

使用 `RefreshDatabase` + SQLite in-memory，mock `LlmTranslationService`：

- `--batch-size=0` 返回 FAILURE
- `--limit=-1` 返回 FAILURE
- 无待处理记录时输出提示并返回 SUCCESS
- 正常流程：进度条显示、统计输出正确
- `ConnectionException` 时返回 FAILURE 并输出错误信息
- 部分批次失败时整体流程继续，`skipped_batches` 统计正确

### 不测试的内容

- `LlmTranslationService` 内部重试逻辑（已有独立测试）
- Ollama HTTP 调用（集成测试范畴，需真实环境）
- 进度条的视觉样式
