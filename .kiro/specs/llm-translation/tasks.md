# 实现计划：LLM 翻译模块

## 概述

按照分层架构逐步实现：数据库结构扩展 → Model 更新 → LLM 服务封装 → 翻译任务分发 → Artisan Command → API Resource 修改 → 测试。

## 任务

- [x] 1. 数据库结构扩展与 Model 更新
  - [x] 1.1 创建 migration 文件，为四张参考数据表新增翻译字段
    - 创建 `database/migrations/xxxx_add_translation_fields_to_reference_tables.php`
    - 在同一 migration 中为 `departments`、`jobs`、`keywords`、`languages` 各新增 `name_zh`（nullable varchar(255)）和 `translated_at`（nullable timestamp）
    - _需求：1.1、1.2、1.3、1.4_

  - [x] 1.2 更新四个 Model，声明新增字段
    - 修改 `app/Models/Department.php`、`Job.php`、`Keyword.php`、`Language.php`
    - 将 `name_zh`、`translated_at` 加入 `$fillable`
    - 在 `$casts` 中声明 `translated_at` 为 `datetime`
    - _需求：1.1–1.4_

- [x] 2. 配置管理
  - [x] 2.1 在 `config/services.php` 新增 `ollama` 配置键
    - 添加 `base_url`（读取 `OLLAMA_BASE_URL`，默认 `http://localhost:11434`）和 `model`（读取 `OLLAMA_MODEL`，默认 `qwen2.5:7b`）
    - 在 `.env.example` 新增对应环境变量示例
    - _需求：8.1、8.2_

- [x] 3. 实现 LlmTranslationService
  - [x] 3.1 创建 `app/Services/LlmTranslationService.php`，实现 Ollama HTTP 调用与 prompt 构造
    - 实现 `translateBatch(array $items, string $tableType, ?string $context = null): array`
    - 通过 Laravel HTTP Client 调用 `{base_url}/api/chat`，携带 `format: "json"` 参数
    - 构造 system prompt：领域锚定、字数约束（keywords/departments/languages ≤ 8 字，jobs ≤ 12 字）、正反例示范
    - 构造 user message：`{"task": "translate_to_chinese", "items": [...]}` 格式，jobs 表附加 `context` 字段
    - Ollama 不可达时记录 `Log::error`（含 base_url 和错误信息）并重新抛出异常
    - _需求：3.1–3.7、8.3_

  - [x] 3.2 在 LlmTranslationService 中实现 JSON 容错解析与重试逻辑
    - 实现正则提取响应中第一个 `[...]` 或 `{...}` 块的私有方法
    - 实现重试策略：原始批次 → 5 条 → 1 条，最多 3 次
    - 全部失败时返回空数组并记录 `Log::warning`（含原始响应截断至 500 字符）
    - 解析成功后通过 `id` 字段映射结果，不依赖顺序
    - _需求：4.1–4.5_

  - [ ]* 3.3 为 LlmTranslationService 编写单元测试（`tests/Unit/Services/LlmTranslationServiceTest.php`）
    - 使用 `Http::fake()` 模拟 Ollama 响应，不连接真实服务
    - **属性 8：JSON 容错解析——从污染响应中提取有效 JSON**（验证需求：4.1）
      - `test_extracts_json_from_markdown_code_block`
      - `test_extracts_json_from_prefixed_text_response`
      - `test_extracts_json_from_mixed_text_and_json`
    - **属性 4：翻译结果通过 id 映射，与响应顺序无关**（验证需求：4.5）
      - `test_maps_translations_by_id_regardless_of_order`
      - `test_maps_translations_with_sparse_ids`
    - **属性 5：批次跳过时不写入任何记录**（验证需求：4.4）
      - `test_returns_empty_array_when_all_retries_fail`
      - `test_retries_with_smaller_batch_on_parse_failure`
    - **属性 9：Ollama 不可达时抛出异常并记录日志**（验证需求：8.3）
      - `test_throws_exception_when_ollama_unreachable`
      - `test_logs_error_with_base_url_when_connection_fails`

- [x] 4. 检查点——确认 LlmTranslationService 核心逻辑正确
  - 确保所有测试通过，如有疑问请告知。

- [x] 5. 实现 TranslationService
  - [x] 5.1 创建 `app/Services/TranslationService.php`，实现按表分发与写入逻辑
    - 实现 `translateTable(string $table, int $batchSize = 20, ?int $limit = null, ?callable $onProgress = null): array`
    - `keywords` 表使用 `WHERE name_zh IS NULL` 查询（断点续传），其余三张表查询全部记录
    - `jobs` 表预加载关联 `department.name`，作为上下文传给 `LlmTranslationService`
    - 翻译成功后使用 `DB::transaction` 批量更新 `name_zh` 和 `translated_at`（当前 UTC 时间）
    - 批次被跳过（`LlmTranslationService` 返回空数组）时不写入任何字段
    - 按 `$batchSize` 分批查询，支持 `$limit` 限制总处理条目数
    - 调用 `$onProgress` 回调更新进度
    - 返回统计数组：`['success' => int, 'skipped_batches' => int]`
    - _需求：5.1–5.5、6.1–6.3_

  - [ ]* 5.2 为 TranslationService 编写单元测试（`tests/Unit/Services/TranslationServiceTest.php`）
    - Mock `LlmTranslationService`，使用 SQLite in-memory + `RefreshDatabase`
    - 需为四张参考表创建测试用 migration（`tests/` 目录下或通过 `RefreshDatabase` 加载）
    - **属性 1：翻译成功后 translated_at 必须非空**（验证需求：1.6、5.3）
      - `test_writes_name_zh_and_translated_at_on_success`
      - `test_translated_at_is_set_to_current_timestamp`
    - **属性 3：翻译操作不修改原始 name 字段**（验证需求：2.3、2.5）
      - `test_name_field_unchanged_after_translation`
    - **属性 5：批次跳过时不写入任何记录**（验证需求：4.4、5.4）
      - `test_does_not_write_when_batch_is_skipped`
      - `test_skipped_records_remain_null_after_failed_batch`
    - **属性 6：keywords 断点续传**（验证需求：6.1、6.3）
      - `test_keywords_only_queries_untranslated_records`
      - `test_already_translated_keywords_are_not_reprocessed`

- [x] 6. 实现 TranslateNamesCommand
  - [x] 6.1 创建 `app/Console/Commands/TranslateNamesCommand.php`
    - 注册命令签名 `translate:names`，定义 `--table`（默认 `all`）、`--batch-size`（默认 20）、`--limit` 三个选项
    - `--table` 不在白名单时输出错误信息并以非零状态码退出（不执行任何翻译）
    - `--table=all` 时依次处理四张表
    - 使用 Laravel `ProgressBar` 显示进度，通过 `$onProgress` 回调驱动
    - 任务完成后输出成功条目数、失败批次数、跳过批次数统计
    - _需求：7.1–7.8_

  - [ ]* 6.2 为 TranslateNamesCommand 编写 Feature 测试（`tests/Feature/Translation/TranslateNamesCommandTest.php`）
    - Mock `TranslationService`，不依赖真实数据库写入
    - **属性 7：--table 参数白名单校验**（验证需求：7.8）
      - `test_exits_with_nonzero_code_for_invalid_table_option`
      - `test_invalid_table_does_not_trigger_any_translation`
      - `test_accepts_all_valid_table_options`
    - 具体例子测试：
      - `test_table_all_processes_all_four_tables`
      - `test_outputs_statistics_after_completion`
      - `test_respects_limit_option`

- [x] 7. 检查点——确认命令行流程端到端正确
  - 确保所有测试通过，如有疑问请告知。

- [x] 8. 修改 API Resource 层，输出 name_zh 字段
  - [x] 8.1 修改 `DepartmentResource`、`JobResource`、`KeywordResource`、`LanguageResource`
    - 在 `toArray()` 中新增 `'name_zh' => $this->name_zh` 字段输出
    - 保持原有 `name` 字段不变
    - `name_zh` 为 null 时直接输出 null（不降级为英文 name）
    - _需求：2.1–2.5_

  - [ ]* 8.2 为 Resource 层编写 Feature 测试（`tests/Feature/Translation/ResourceTranslationTest.php`）
    - 使用 SQLite in-memory + `RefreshDatabase`，通过 mock Service 注入测试数据
    - **属性 2：Resource 输出的 name_zh 与数据库值一致**（验证需求：2.1、2.2、2.4）
      - `test_department_resource_outputs_null_name_zh_when_not_translated`
      - `test_department_resource_outputs_name_zh_value_when_translated`
      - `test_job_resource_follows_same_name_zh_output_rules`
      - `test_keyword_resource_follows_same_name_zh_output_rules`
      - `test_language_resource_follows_same_name_zh_output_rules`
    - **属性 3：翻译操作不修改原始 name 字段**（验证需求：2.3、2.5）
      - `test_original_name_field_always_present_in_response`

- [x] 9. 最终检查点——确保所有测试通过
  - 运行 `php artisan test`，确保全部测试通过，如有疑问请告知。

## 备注

- 标有 `*` 的子任务为可选测试任务，可跳过以加快 MVP 进度
- 每个任务引用了具体需求条款，便于追溯
- 测试均使用 SQLite in-memory，不连接云端数据库
- `LlmTranslationService` 测试通过 `Http::fake()` 模拟，不依赖真实 Ollama 服务
