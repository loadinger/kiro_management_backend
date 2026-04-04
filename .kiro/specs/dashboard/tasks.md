# 实现计划：Dashboard 数据统计模块

## 概述

按依赖顺序逐层实现：Repository 接口与实现 → Service Provider 绑定 → Service 层 → HTTP 层（Request / Controller / 路由）→ 测试。每一步均可独立验证，最终通过测试确认整体正确性。

## 任务列表

- [x] 1. 实现 DashboardRepositoryInterface
  - 在 `app/Repositories/Contracts/DashboardRepositoryInterface.php` 中定义接口
  - 声明 6 个方法：`getEntityCounts(): array`、`getReconcileRates(): array`、`getTranslationCoverage(): array`、`getDataFreshness(): array`、`getSnapshotDates(int $days): array`、`getTrendRows(int $days, array $entities): array`
  - 所有方法加 PHPDoc，说明返回数组结构
  - _需求：2.1、2.2、4.1、5.1、6.1、6.2、7.2、3.6_

- [x] 2. 实现 DashboardRepository
  - 在 `app/Repositories/DashboardRepository.php` 中实现接口，不继承 BaseRepository
  - 实现 `getEntityCounts()`：对 9 张表各执行 `DB::table($table)->count()`
  - 实现 `getReconcileRates()`：对 4 张关系表查询 total 和 resolved（`person_id IS NOT NULL` 或 `movie_id IS NOT NULL`）
  - 实现 `getTranslationCoverage()`：对 4 张参考表查询 total 和 `translated_at IS NOT NULL` 的 translated
  - 实现 `getDataFreshness()`：对 6 张表执行 `SELECT MAX(updated_at)`
  - 实现 `getSnapshotDates(int $days)`：查询 `media_list_snapshots` 最近 N 天内的 `DISTINCT snapshot_date`
  - 实现 `getTrendRows(int $days, array $entities)`：对每个实体按 `GROUP BY DATE(created_at)` 聚合，`persons` 表强制加 `WHERE created_at >= NOW() - INTERVAL ? DAY`
  - _需求：2.2、4.1、5.1、6.2、7.2、3.6、3.8_

- [x] 3. 在 AppServiceProvider 注册绑定
  - 在 `app/Providers/AppServiceProvider.php` 的 `register()` 方法中添加：
    `$this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);`
  - 添加对应 use 语句
  - _需求：1.3、1.4_

- [x] 4. 实现 DashboardService
  - 在 `app/Services/DashboardService.php` 中实现 Service 层
  - 构造函数注入 `DashboardRepositoryInterface`（`private readonly`）
  - 实现 `getStats(): array`：
    - 使用 `Cache::remember('dashboard:stats', 600, ...)` 缓存
    - 对每个子项（entity_counts / reconcile_rates / translation_coverage / data_freshness / snapshot_health）独立 try-catch，失败时 `Log::error(...)` 并返回 null
    - 调用 `private computeStaleStatus(array $freshnessRows): array` 计算 is_stale（null 或超 48 小时为 true）
    - 调用 `private computeSnapshotHealth(array $presentDates, int $days): array` 生成 missing_dates
    - rate 计算：total=0 时 rate=1.0，否则 rate=round(resolved/total, 4)
  - 实现 `getTrends(int $days, array $entities): array`：
    - 缓存键：`dashboard:trends:{$days}:` + implode(',', sorted $entities)，TTL 300
    - 调用 `private buildTrendSeries(array $rows, array $dates, array $entities): array` 填充等长数组，缺失日期填 0
  - _需求：2.3、2.4、3.7、4.2、4.4、5.2、5.4、6.4、6.6、7.3、7.5、8.4_

  - [x] 4.1 为 rate 计算编写属性测试
    - **属性 5：rate 计算正确性**
    - **验证：需求 4.2、5.2**

  - [ ]* 4.2 为 is_stale 计算编写属性测试
    - **属性 7：is_stale 计算正确性**
    - **验证：需求 6.4**

  - [ ]* 4.3 为 missing_dates 计算编写属性测试
    - **属性 8：missing_dates 计算正确性**
    - **验证：需求 7.3、7.4**

  - [ ]* 4.4 为 trends series 填充编写属性测试
    - **属性 10：trends series 填充**
    - **验证：需求 3.5、8.3**

- [x] 5. 实现 GetTrendsRequest
  - 在 `app/Http/Requests/GetTrendsRequest.php` 中实现 FormRequest
  - `authorize()` 返回 true
  - `prepareForValidation()`：将 `entities` 字符串按逗号拆分为数组
  - `rules()`：`days` 为 `nullable|integer|in:7,30,90`，`entities.*` 为 `in:movies,tv_shows,persons`
  - `messages()` 提供中文错误信息
  - 默认值处理：`days` 缺省时使用 30，`entities` 缺省时使用 `['movies','tv_shows','persons']`
  - _需求：3.1、3.2、3.3、3.4_

- [x] 6. 实现 DashboardController
  - 在 `app/Http/Controllers/Api/DashboardController.php` 中实现 Controller
  - 继承 `BaseController`，构造函数注入 `DashboardService`（`private readonly`）
  - `stats()` 方法：调用 `$this->dashboardService->getStats()`，返回 `$this->success($data)`
  - `trends(GetTrendsRequest $request)` 方法：从 validated 数据取 days 和 entities，调用 `getTrends()`，返回 `$this->success($data)`
  - _需求：1.3、1.4、8.1、8.2、8.3_

- [x] 7. 注册路由
  - 在 `routes/api.php` 的 `auth:api` middleware 组内添加：
    ```php
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('dashboard/trends', [DashboardController::class, 'trends']);
    ```
  - 添加对应 use 语句
  - _需求：1.1、1.2、1.3、1.4_

- [x] 8. 检查点 — 确认基础结构完整
  - 确认所有文件已创建，接口绑定正确，路由可访问，ask the user if questions arise.

- [x] 9. 编写 Feature Test：DashboardStatsTest
  - 在 `tests/Feature/Dashboard/DashboardStatsTest.php` 中实现
  - 使用 `RefreshDatabase` trait（仅用于创建测试 User）
  - 所有测试用 `$this->mock(DashboardService::class, ...)` mock Service
  - 覆盖以下场景：
    - `test_unauthenticated_request_returns_401`（属性 1，需求 1.1）
    - `test_stats_response_contains_all_top_level_fields`（属性 2，需求 8.1、8.2）
    - `test_entity_counts_contains_nine_entities_with_non_negative_integers`（属性 3，需求 2.1、2.5）
    - `test_reconcile_rates_structure_is_complete`（属性 4，需求 4.1、4.3）
    - `test_translation_coverage_structure_is_complete`（属性 4，需求 5.1、5.3）
    - `test_data_freshness_structure_is_complete`（属性 6，需求 6.1、6.3、6.5）
    - `test_snapshot_health_structure_is_complete`（属性 8，需求 7.1、7.4）
    - `test_failed_subquery_returns_null_for_that_field_only`（属性 11，需求 8.4）
  - _需求：1.1、2.1、2.5、4.1、4.3、5.1、5.3、6.1、6.3、6.5、7.1、7.4、8.1、8.2、8.4_

- [x] 10. 编写 Feature Test：DashboardTrendsTest
  - 在 `tests/Feature/Dashboard/DashboardTrendsTest.php` 中实现
  - 使用 `RefreshDatabase` trait，mock `DashboardService`
  - 覆盖以下场景：
    - `test_unauthenticated_request_returns_401`（属性 1，需求 1.2）
    - `test_invalid_days_parameter_returns_422`（属性 9，需求 3.3）
    - `test_invalid_entities_parameter_returns_422`（属性 9，需求 3.4）
    - `test_dates_array_length_equals_days`（属性 10，需求 3.5、8.3）
    - `test_series_arrays_have_same_length_as_dates`（属性 10，需求 3.5、8.3）
    - `test_default_parameters_are_applied_when_omitted`（需求 3.1、3.2）
  - _需求：1.2、3.1、3.2、3.3、3.4、3.5、8.3_

- [x] 11. 编写 Unit Test：DashboardServiceTest
  - 在 `tests/Unit/Dashboard/DashboardServiceTest.php` 中实现
  - mock `DashboardRepositoryInterface`，直接测试 Service 层业务逻辑
  - 覆盖以下边界场景：
    - `test_is_stale_is_true_when_last_updated_at_is_null`（需求 6.4）
    - `test_is_stale_is_false_when_exactly_48_hours`（需求 6.4，边界值）
    - `test_is_stale_is_true_when_over_48_hours`（需求 6.4）
    - `test_missing_dates_when_all_30_days_missing`（需求 7.3、7.4）
    - `test_missing_dates_when_all_30_days_present`（需求 7.3、7.4）
    - `test_trend_series_fills_zero_for_missing_dates`（需求 3.5）
    - `test_rate_is_1_when_total_is_zero`（需求 4.2、5.2）
  - _需求：3.5、4.2、5.2、6.4、7.3、7.4_

- [x] 12. 编写 Property-Based Test：DashboardPropertyTest
  - 在 `tests/Unit/Dashboard/DashboardPropertyTest.php` 中实现，使用 PHPUnit（项目内置）
  - 每个属性测试在方法内循环运行 100 次随机输入（`for ($i = 0; $i < 100; $i++)`）
  - 每个测试方法注释标注 `// Feature: dashboard, Property {N}: {属性标题}`

  - [ ]* 12.1 属性 5：rate 计算正确性
    - 循环 100 次，每次用 `random_int` 生成随机 total（0–10000000）和 value（0–total）
    - 验证：total=0 时 rate=1.0；total>0 时 rate=round(value/total,4)；结果始终在 [0.0, 1.0]
    - **属性 5：rate 计算正确性**
    - **验证：需求 4.2、5.2**

  - [ ]* 12.2 属性 7：is_stale 计算正确性
    - 循环 100 次，每次用 `random_int(0, 200)` 生成随机小时偏移，构造 last_updated_at 时间戳（含 null 情况）
    - 验证：null 时 is_stale=true；差值>48h 时 is_stale=true；否则 is_stale=false
    - **属性 7：is_stale 计算正确性**
    - **验证：需求 6.4**

  - [ ]* 12.3 属性 8：missing_dates 计算正确性
    - 循环 100 次，每次随机生成最近 30 天的任意 present_dates 子集
    - 验证：missing_dates = 完整 30 天序列 - present_dates，且结果升序排列
    - **属性 8：missing_dates 计算正确性**
    - **验证：需求 7.3、7.4**

  - [ ]* 12.4 属性 10：trends series 填充
    - 循环 100 次，每次随机选取 days（7/30/90）和稀疏数据行
    - 验证：输出 dates 数组长度等于 days，series 中每个实体数组长度与 dates 相同，缺失日期填 0
    - **属性 10：trends series 填充**
    - **验证：需求 3.5、8.3**

- [x] 13. 最终检查点 — 确认所有测试通过
  - 确认所有测试通过，ask the user if questions arise.

## 备注

- 标注 `*` 的子任务为可选项，可跳过以加快 MVP 交付
- 属性测试使用 PHPUnit（项目内置），通过循环随机输入模拟属性测试行为，无需额外依赖
- DashboardRepository 不继承 BaseRepository，直接使用 `DB::table()`
- Feature Test 中 `RefreshDatabase` 仅用于创建测试 User，不依赖业务表数据
