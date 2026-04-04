# 需求文档

## 简介

LLM 翻译模块负责将数据库中 `departments`、`jobs`、`keywords`、`languages` 四张参考数据表的英文 `name` 字段批量翻译为中文，并将结果写入各表新增的 `name_zh` 字段。翻译由本地部署的 Ollama（Qwen 2.5 7B）完成，通过 Artisan Command 手动触发，支持断点续传与容错重试。API 层在 `name_zh` 为 null 时降级返回原始英文 `name`。

## 词汇表

- **TranslateNamesCommand**：Artisan 命令 `translate:names`，翻译任务的入口
- **TranslationService**：按表分发翻译任务、构建上下文的服务层
- **LlmTranslationService**：封装 Ollama HTTP 调用的底层服务
- **Ollama**：本地部署的 LLM 推理服务，兼容 OpenAI Chat Completions 格式
- **name_zh**：各参考数据表新增的中文译名字段（nullable varchar）
- **translated_at**：各参考数据表新增的翻译时间戳字段（nullable timestamp），null 表示未翻译
- **批次（Batch）**：单次 LLM 调用处理的条目集合，默认大小为 20
- **断点续传**：通过 `WHERE name_zh IS NULL` 跳过已翻译条目，支持中断后继续

---

## 需求

### 需求 1：数据库结构扩展

**用户故事：** 作为开发者，我希望在各参考数据表中新增中文译名和翻译时间字段，以便存储翻译结果并追踪翻译状态。

#### 验收标准

1. THE System SHALL 在 `departments` 表新增 `name_zh`（nullable varchar(255)）和 `translated_at`（nullable timestamp）字段
2. THE System SHALL 在 `jobs` 表新增 `name_zh`（nullable varchar(255)）和 `translated_at`（nullable timestamp）字段
3. THE System SHALL 在 `keywords` 表新增 `name_zh`（nullable varchar(255)）和 `translated_at`（nullable timestamp）字段
4. THE System SHALL 在 `languages` 表新增 `name_zh`（nullable varchar(255)）和 `translated_at`（nullable timestamp）字段
5. WHEN `translated_at` 为 null 时，THE System SHALL 将该条记录视为未翻译
6. WHEN 翻译成功写入时，THE System SHALL 将 `translated_at` 更新为当前 UTC 时间戳

---

### 需求 2：API 降级输出

**用户故事：** 作为前端开发者，我希望 API 在中文译名缺失时自动降级返回英文原名，以便在翻译未完成时前端仍能正常展示数据。

#### 验收标准

1. WHEN `name_zh` 不为 null 时，THE DepartmentResource SHALL 在响应中输出 `name_zh` 的值作为 `name_zh` 字段
2. WHEN `name_zh` 为 null 时，THE DepartmentResource SHALL 在响应中将 `name_zh` 字段输出为 null
3. THE DepartmentResource SHALL 始终在响应中同时输出原始 `name` 字段，保持不变
4. THE JobResource、THE KeywordResource、THE LanguageResource SHALL 遵循与 DepartmentResource 相同的 `name_zh` 输出规则
5. THE System SHALL 不修改原始 `name` 字段的值

---

### 需求 3：LLM 服务封装

**用户故事：** 作为开发者，我希望有一个统一封装 Ollama 调用的服务，以便上层业务代码不直接依赖 HTTP 细节。

#### 验收标准

1. THE LlmTranslationService SHALL 通过 HTTP POST 请求调用 Ollama `/api/chat` 接口，携带 `format: "json"` 参数
2. THE LlmTranslationService SHALL 在请求体中包含 system prompt，明确告知模型翻译任务为 TMDB 影视行业术语
3. THE LlmTranslationService SHALL 在 system prompt 中约束译文为词或短语，`keywords`、`departments`、`languages` 表的译文不超过 8 个汉字，`jobs` 表的译文不超过 12 个汉字
4. THE LlmTranslationService SHALL 在 system prompt 中包含正反例示范
5. THE LlmTranslationService SHALL 以 `{"task": "translate_to_chinese", "items": [{"id": <id>, "text": <name>}]}` 格式构造 user message
6. WHEN 翻译 `jobs` 表时，THE LlmTranslationService SHALL 在请求体中附加 `"context": "电影制作职位，所属部门：<department_name>"` 字段
7. THE LlmTranslationService SHALL 期望 Ollama 返回 `[{"id": <id>, "translation": <译文>}]` 格式的 JSON 数组

---

### 需求 4：JSON 响应容错解析

**用户故事：** 作为开发者，我希望系统能容忍小模型输出格式不稳定的情况，以便在 LLM 返回格式异常时仍能尽量提取有效翻译结果。

#### 验收标准

1. WHEN Ollama 返回的内容包含 markdown 代码块或其他文本污染时，THE LlmTranslationService SHALL 使用正则表达式提取第一个 `[...]` 或 `{...}` 块进行解析
2. WHEN JSON 解析失败时，THE LlmTranslationService SHALL 自动重试，最多重试 3 次
3. WHEN 重试时，THE LlmTranslationService SHALL 按批次大小递减策略重新请求：第 1 次重试批次大小为 5，第 2 次重试批次大小为 1
4. WHEN 全部重试均失败时，THE LlmTranslationService SHALL 跳过该批次，不写入任何 `name_zh` 或 `translated_at`，并记录 warning 日志
5. FOR ALL 成功解析的响应，THE LlmTranslationService SHALL 通过 `id` 字段映射翻译结果，不依赖返回顺序

---

### 需求 5：翻译任务分发与上下文构建

**用户故事：** 作为开发者，我希望有一个服务层负责按表分发翻译任务并构建正确的上下文，以便各表的翻译逻辑集中管理。

#### 验收标准

1. THE TranslationService SHALL 支持对 `departments`、`jobs`、`keywords`、`languages` 四张表分别执行翻译
2. WHEN 翻译 `jobs` 表时，THE TranslationService SHALL 预加载每条 job 记录关联的 department 名称，并将其作为上下文传递给 LlmTranslationService
3. WHEN 翻译成功时，THE TranslationService SHALL 将 `name_zh` 和 `translated_at` 写入对应记录
4. WHEN 批次翻译被跳过时，THE TranslationService SHALL 不写入该批次任何记录的 `name_zh` 和 `translated_at`，保留其未翻译状态以供下次运行重新处理
5. THE TranslationService SHALL 按指定批次大小（batch size）分批查询并翻译记录

---

### 需求 6：断点续传

**用户故事：** 作为运维人员，我希望翻译任务在中断后能从未翻译的记录继续，以便对数据量大的表（如 keywords）无需重新翻译已完成的记录。

#### 验收标准

1. WHEN 执行 `keywords` 表翻译时，THE TranslationService SHALL 仅查询 `name_zh IS NULL` 的记录
2. WHEN 执行 `departments`、`jobs`、`languages` 表翻译时，THE TranslationService SHALL 查询全部记录（数据量小，无需断点续传）
3. WHEN 翻译任务被中断后重新运行时，THE TranslateNamesCommand SHALL 从上次未完成的记录继续，不重复翻译已有 `name_zh` 的记录

---

### 需求 7：Artisan Command

**用户故事：** 作为运维人员，我希望通过 Artisan 命令灵活控制翻译任务的执行范围和参数，以便按需执行全量翻译或针对特定表的翻译。

#### 验收标准

1. THE TranslateNamesCommand SHALL 注册为 `translate:names` Artisan 命令
2. THE TranslateNamesCommand SHALL 支持 `--table` 选项，接受值为 `departments`、`jobs`、`keywords`、`languages` 或 `all`
3. WHEN `--table=all` 时，THE TranslateNamesCommand SHALL 依次翻译全部四张表
4. THE TranslateNamesCommand SHALL 支持 `--batch-size` 选项，指定每批翻译条目数，默认值为 20
5. THE TranslateNamesCommand SHALL 支持 `--limit` 选项，限制本次运行最多处理的条目总数，不指定时处理全部未翻译记录
6. WHILE 翻译任务执行中，THE TranslateNamesCommand SHALL 显示进度条，反映当前处理进度
7. WHEN 翻译任务完成时，THE TranslateNamesCommand SHALL 输出成功条目数、失败条目数、跳过批次数的统计信息
8. IF `--table` 选项的值不在允许范围内，THEN THE TranslateNamesCommand SHALL 输出错误信息并以非零状态码退出

---

### 需求 8：配置管理

**用户故事：** 作为开发者，我希望 Ollama 的连接参数通过环境变量配置，以便在不同环境中灵活切换 LLM 服务地址和模型。

#### 验收标准

1. THE System SHALL 从环境变量 `OLLAMA_BASE_URL` 读取 Ollama 服务地址，默认值为 `http://localhost:11434`
2. THE System SHALL 从环境变量 `OLLAMA_MODEL` 读取使用的模型名称，默认值为 `qwen2.5:7b`
3. IF `OLLAMA_BASE_URL` 对应的服务不可达，THEN THE LlmTranslationService SHALL 抛出异常并记录 error 日志，包含服务地址和错误信息
