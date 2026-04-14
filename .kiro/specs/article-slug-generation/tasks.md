# 实现计划：article-slug-generation

## 概述

按分层架构依次实现：修改 `LlmTranslationService` 支持自定义 prompt → 新建 `ArticleSlugService` → 注册服务绑定 → 新建 `GenerateSlugsCommand` → 安装属性测试库 → 编写属性测试 → 编写功能测试。

## 任务

- [x] 1. 修改 LlmTranslationService：支持自定义 System Prompt
  - 在 `app/Services/LlmTranslationService.php` 构造函数新增可选参数 `?string $systemPrompt = null`
  - 新增 `private readonly string $systemPrompt` 属性，构造函数中赋值：`$this->systemPrompt = $systemPrompt ?? self::SYSTEM_PROMPT`
  - 将 `attemptTranslate()` 中所有 `self::SYSTEM_PROMPT` 引用替换为 `$this->systemPrompt`
  - 现有调用方（`TranslationService`）无需修改，Laravel 容器自动解析时使用默认值
  - _需求：4.4（slug-generation system prompt 注入）_

- [x] 2. 新建 ArticleSlugService
  - [x] 2.1 创建 `app/Services/ArticleSlugService.php`，实现 `generateSlugs()` 主方法
    - 构造函数注入 `private readonly LlmTranslationService $llmTranslationService`
    - 实现游标分页：`WHERE slug IS NULL AND id > $afterId ORDER BY id ASC LIMIT $batchSize`
    - 过滤空/null title 记录，不发送给 LLM（需求 6.3）
    - 调用 `translateBatch($items, 'articles', null)` 获取翻译结果（需求 4.5）
    - 对每条 translation 调用 `formatSlug()` 格式化
    - 格式化后为空字符串则跳过（需求 4.4）
    - LLM 返回空结果则 `skipped_batches++`，继续下一批（需求 5.3）
    - 调用 `writeBatch()` 在事务中写回，累加 `success` 统计
    - 返回 `array{success: int, skipped_batches: int}`
    - _需求：3.1、3.2、3.3、3.4、5.1、5.3、5.4、6.2、6.3_

  - [x] 2.2 实现 `formatSlug(string $raw): string` 纯函数
    - 步骤依次：`mb_strtolower` → 空格/下划线替换为 `-` → 移除非 `[a-z-]` 字符 → 合并连续 `-` → 去除首尾 `-`
    - 超过 120 字符截断，截断后再次去除尾部 `-`
    - _需求：4.2、4.3_

  - [x] 2.3 实现 `resolveUniqueSlug(string $slug, int $excludeId): ?string` 唯一性冲突处理
    - 先检查原始 slug 是否已被占用（排除当前文章 id）
    - 若冲突，依次尝试 `{slug}-2` 到 `{slug}-99`
    - 超过 99 仍冲突则返回 null（跳过该记录）
    - _需求：5.2_

  - [x] 2.4 实现 `writeBatch(array $slugMap): void` 事务写回
    - 在数据库事务中批量执行 `UPDATE articles SET slug = ? WHERE id = ?`
    - 只更新 `slug` 字段，不触碰其他字段（需求 5.5）
    - 事务失败时回滚并重新抛出异常（需求 6.4）
    - _需求：5.1、5.4、5.5、6.4_

- [x] 3. 在 AppServiceProvider 注册 ArticleSlugService 绑定
  - 在 `app/Providers/AppServiceProvider.php` 的 `register()` 方法中添加绑定
  - 手动构造带 slug-generation system prompt 的 `LlmTranslationService` 实例，再注入 `ArticleSlugService`
  - 确保现有 `LlmTranslationService` 的默认绑定不受影响
  - _需求：4.4（通过 DI 注入自定义 prompt）_

- [x] 4. 新建 GenerateSlugsCommand
  - 创建 `app/Console/Commands/GenerateSlugsCommand.php`
  - 注册签名：`articles:generate-slugs`，选项：`--batch-size=10`、`--limit=`（可选）
  - 构造函数注入 `private readonly ArticleSlugService $articleSlugService`
  - `handle()` 中验证 `--batch-size < 1` 和 `--limit < 1`，输出错误并返回 `FAILURE`（需求 1.4、1.5）
  - 待处理记录数为 0 时输出提示并返回 `SUCCESS`，不显示进度条（需求 2.4）
  - 创建进度条，总量为待处理记录数；每批完成后更新进度（需求 2.1、2.2）
  - 捕获 `ConnectionException`，输出错误信息，返回 `FAILURE`（需求 6.1）
  - 捕获其他 `\Throwable`（数据库事务失败等），输出错误信息，返回 `FAILURE`（需求 6.4）
  - 完成后输出 `success` 和 `skipped_batches` 统计（需求 2.3）
  - _需求：1.1、1.2、1.3、1.4、1.5、2.1、2.2、2.3、2.4、6.1、6.4_

- [x] 5. 安装 eris 属性测试库
  - 执行 `composer require --dev giorgiosironi/eris`
  - 确认 `composer.json` 的 `require-dev` 中已包含该依赖
  - _需求：测试策略（属性测试）_

- [x] 6. 编写属性测试
  - [x] 6.1 创建 `tests/Unit/Services/ArticleSlugServiceTest.php`，配置 eris TestTrait，最少 100 次迭代
    - 文件顶部 `declare(strict_types=1)`，使用 SQLite in-memory + mock `LlmTranslationService`
    - _需求：测试策略_

  - [x]* 6.2 属性 1：slug 格式化不变量（`formatSlug` 输出满足所有格式约束）
    - **属性 1：slug format invariant**
    - 对任意字符串输入，输出只含 `[a-z-]`，不以 `-` 开头/结尾，不含 `--`，长度 ≤ 120
    - **验证：需求 4.2、4.3**

  - [x]* 6.3 属性 2：空/null title 过滤（空 title 记录不传给 LLM）
    - **属性 2：empty title filtering**
    - 对含空 title 的文章集合，`translateBatch` 的 `$items` 参数不包含这些记录
    - **验证：需求 6.3**

  - [x]* 6.4 属性 3：唯一性冲突解决（返回后缀最小的未占用值，全占用时返回 null）
    - **属性 3：unique slug resolution**
    - 对任意基础 slug 和任意已占用后缀集合（2 到 N，N < 99），返回最小未占用后缀；全占用返回 null
    - **验证：需求 5.2**

  - [x]* 6.5 属性 4：写入正确性（只有 slug 字段被更新）
    - **属性 4：write correctness**
    - 写入后 `success` 统计等于实际写入记录数；文章其他字段（`title`、`status` 等）保持不变
    - **验证：需求 5.4、5.5**

  - [x]* 6.6 属性 5：limit 约束（处理总数不超过 limit）
    - **属性 5：limit constraint**
    - 对任意正整数 `$limit` 和任意数据集，`generateSlugs()` 处理的文章总数 ≤ `$limit`
    - **验证：需求 3.3**

- [x] 7. 编写功能测试（GenerateSlugsCommand）
  - [x] 7.1 创建 `tests/Feature/Commands/GenerateSlugsCommandTest.php`
    - 文件顶部 `declare(strict_types=1)`，使用 `RefreshDatabase` + SQLite in-memory
    - mock `LlmTranslationService`（通过 `$this->mock()`）
    - _需求：测试策略_

  - [ ]* 7.2 测试 `--batch-size=0` 返回 FAILURE 并输出错误信息
    - **验证：需求 1.4**

  - [ ]* 7.3 测试 `--limit=-1` 返回 FAILURE 并输出错误信息
    - **验证：需求 1.5**

  - [ ]* 7.4 测试无待处理记录时输出提示并返回 SUCCESS
    - **验证：需求 2.4**

  - [ ]* 7.5 测试正常流程：进度条更新、统计输出正确（success / skipped_batches）
    - **验证：需求 2.1、2.2、2.3**

  - [ ]* 7.6 测试 `ConnectionException` 时返回 FAILURE 并输出错误信息
    - **验证：需求 6.1**

  - [ ]* 7.7 测试部分批次 LLM 返回空结果时整体流程继续，`skipped_batches` 统计正确
    - **验证：需求 5.3、6.2**

- [x] 8. 最终检查点
  - 运行 `php artisan test` 确保所有测试通过
  - 运行 `./vendor/bin/pint` 确保代码格式符合规范
  - 如有问题，向用户说明并等待确认

## 备注

- 标有 `*` 的子任务为可选测试任务，可跳过以加快 MVP 交付
- 属性测试（6.2–6.6）每个最少运行 100 次迭代（通过 eris `MinLength` 或 `suchThat` 配置）
- 所有文件顶部必须声明 `declare(strict_types=1)`
- 依赖注入统一使用构造函数注入，属性声明为 `private readonly`
- `ArticleSlugService` 直接使用 DB Query Builder 操作 `articles` 表（无需 Repository，该表为可写业务表）
