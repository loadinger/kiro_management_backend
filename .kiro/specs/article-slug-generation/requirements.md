# 需求文档

## 简介

为 Filmly 管理后台的专题文章（articles）模块新增 slug 自动生成功能。前端创建文章时 `slug` 字段可为空，由独立 Artisan Command 调用 LLM 将中文 `title` 翻译为英文 slug 并写回 `articles.slug`。该功能复用现有 `LlmTranslationService`（Ollama + Qwen 2.5 7B），只处理 `slug IS NULL` 的记录，支持断点续传。

## 词汇表

- **ArticleSlugService**：负责查询待处理文章、调用 LLM 生成 slug、将结果写回数据库的服务类
- **GenerateSlugsCommand**：Artisan Command，入口为 `php artisan articles:generate-slugs`，负责参数解析、进度展示和统计输出
- **LlmTranslationService**：现有服务，封装 Ollama `/api/chat` 调用，提供批量翻译能力
- **Slug**：SEO 友好的 URL 片段，格式为全小写英文单词以连字符分隔，如 `avengers-alliance`
- **待处理记录**：`articles` 表中 `slug IS NULL` 的记录
- **断点续传**：每次运行只处理 `slug IS NULL` 的记录，已生成 slug 的记录不重复处理

---

## 需求

### 需求 1：Artisan Command 入口

**用户故事：** 作为运维人员，我希望通过 Artisan Command 触发 slug 批量生成，以便在不影响 API 服务的情况下异步处理历史数据。

#### 验收标准

1. THE **GenerateSlugsCommand** SHALL 注册为 `articles:generate-slugs` Artisan Command
2. THE **GenerateSlugsCommand** SHALL 支持 `--batch-size` 选项（整数，默认值 10），用于控制每次 LLM 调用处理的文章数量
3. THE **GenerateSlugsCommand** SHALL 支持 `--limit` 选项（整数，可选），用于限制本次运行最多处理的记录总数
4. WHEN `--batch-size` 选项值小于 1，THE **GenerateSlugsCommand** SHALL 输出错误信息并以失败状态退出
5. WHEN `--limit` 选项值小于 1，THE **GenerateSlugsCommand** SHALL 输出错误信息并以失败状态退出

---

### 需求 2：进度展示与统计输出

**用户故事：** 作为运维人员，我希望在命令运行时看到进度条和最终统计，以便了解处理进展和结果。

#### 验收标准

1. WHEN **GenerateSlugsCommand** 开始处理，THE **GenerateSlugsCommand** SHALL 显示进度条，进度条总量为本次待处理记录数
2. WHEN 每个批次处理完成，THE **GenerateSlugsCommand** SHALL 更新进度条至当前已处理记录数
3. WHEN 所有记录处理完成，THE **GenerateSlugsCommand** SHALL 输出成功写入数量（`success`）和跳过批次数量（`skipped_batches`）的统计信息
4. WHEN 待处理记录数为 0，THE **GenerateSlugsCommand** SHALL 输出提示信息并以成功状态退出，不显示进度条

---

### 需求 3：待处理记录查询与断点续传

**用户故事：** 作为运维人员，我希望命令只处理尚未生成 slug 的文章，并支持中断后继续，以便安全地分批处理大量历史数据。

#### 验收标准

1. THE **ArticleSlugService** SHALL 只查询 `articles.slug IS NULL` 的记录
2. THE **ArticleSlugService** SHALL 使用基于 `id` 的游标分页（`WHERE id > $afterId ORDER BY id`）遍历待处理记录，确保断点续传正确性
3. WHEN `--limit` 选项已指定，THE **ArticleSlugService** SHALL 在累计处理记录数达到 `limit` 值后停止查询
4. WHEN 查询结果为空，THE **ArticleSlugService** SHALL 停止处理并返回统计结果

---

### 需求 4：LLM Slug 生成

**用户故事：** 作为系统，我希望通过 LLM 将中文文章标题翻译为符合格式规范的英文 slug，以便生成 SEO 友好的 URL。

#### 验收标准

1. THE **ArticleSlugService** SHALL 调用 **LlmTranslationService** 的 `translateBatch` 方法，以文章 `id` 和 `title` 构造输入项
2. WHEN **LlmTranslationService** 返回翻译结果，THE **ArticleSlugService** SHALL 对每条 `translation` 值执行容错格式化，步骤依次为：转为全小写、将空格和下划线替换为连字符、移除所有非 ASCII 字母和连字符的字符（含中文、数字、标点等）、合并连续连字符、去除首尾连字符
3. WHEN 格式化后的 slug 超过 120 个字符，THE **ArticleSlugService** SHALL 将其截断至 120 个字符，并去除截断后的尾部连字符
4. WHEN 格式化后的 slug 为空字符串，THE **ArticleSlugService** SHALL 跳过该记录，不写入数据库
4. THE **ArticleSlugService** SHALL 使用如下 System Prompt 指导 LLM 生成 slug：

```
你是一个专业的影视内容 SEO 助手，专门将中文影视专题文章标题转换为英文 URL slug。

规则：
1. 提取标题的核心关键词翻译为英文，不要逐字翻译完整标题
2. slug 应简短精炼，控制在 3~15 个英文单词以内，便于记忆和 SEO
3. 输出必须是纯英文单词，以连字符分隔
4. 全部小写，不含大写字母
5. 不含数字前缀、标点符号、空格、特殊字符
6. 只输出 slug 本身，不含任何解释或额外文字
7. 必须处理输入中的每一条，不能遗漏

输入格式：{"task":"generate_slug","items":[{"id":1,"text":"盘点2024年最值得一看的十部科幻电影"},{"id":2,"text":"星际穿越"}]}

输出格式（严格按此 JSON 数组，字段名必须是 id 和 translation，必须包含所有输入条目）：
[{"id":1,"translation":"best-sci-fi-2024"},{"id":2,"translation":"interstellar"}]

错误示例（禁止）：
✗ [{"id":1,"translation":"Top-Ten-Most-Worth-Watching-Sci-Fi-Movies-In-2024"}]  （逐字翻译，过长，含大写）
✗ [{"id":1,"translation":"复仇者联盟"}]                                          （未翻译）
✗ [{"id":1,"translation":"001-avengers"}]                                        （含数字前缀）
✓ [{"id":1,"translation":"best-sci-fi-2024"},{"id":2,"translation":"interstellar"}]
```

5. THE **ArticleSlugService** 调用 `translateBatch` 时 `$tableType` 参数传入 `'articles'`，`$context` 参数传入 `null`

---

### 需求 5：Slug 写回数据库

**用户故事：** 作为系统，我希望生成的 slug 被可靠地写回数据库，以便文章可以通过 slug 访问。

#### 验收标准

1. WHEN **LlmTranslationService** 返回非空翻译结果，THE **ArticleSlugService** SHALL 在数据库事务中批量更新对应文章的 `slug` 字段
2. WHEN 写入 `slug` 时发生唯一性冲突（`UNIQUE` 约束），THE **ArticleSlugService** SHALL 在原 slug 后追加数字后缀（`-2`、`-3`，依此类推），直到找到未被占用的值后再写入；后缀数字上限为 99，超出则跳过该记录
3. WHEN **LlmTranslationService** 返回空结果（全批次失败），THE **ArticleSlugService** SHALL 跳过该批次，不更新任何记录，并将跳过批次数加 1
4. WHEN 数据库写入成功，THE **ArticleSlugService** SHALL 将成功写入数量累加到统计结果中
5. THE **ArticleSlugService** SHALL 只更新 `slug` 字段，不修改文章的其他字段（如 `title`、`status`、`updated_at` 等）

---

### 需求 6：错误处理与容错

**用户故事：** 作为运维人员，我希望单个批次的失败不影响整体运行，以便最大化处理成功率。

#### 验收标准

1. WHEN **LlmTranslationService** 抛出连接异常（`ConnectionException`），THE **GenerateSlugsCommand** SHALL 捕获异常，输出错误信息，并以失败状态退出
2. WHEN 单个批次的 LLM 调用失败（返回空结果），THE **ArticleSlugService** SHALL 继续处理下一批次，不中断整体流程
3. WHEN `title` 字段为空字符串或 null，THE **ArticleSlugService** SHALL 跳过该记录，不将其发送给 LLM
4. IF 数据库写入事务失败，THEN THE **ArticleSlugService** SHALL 回滚事务并重新抛出异常，由 **GenerateSlugsCommand** 捕获后输出错误信息并以失败状态退出
