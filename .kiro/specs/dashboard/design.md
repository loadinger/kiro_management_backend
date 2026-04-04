# 技术设计文档：Dashboard 数据统计模块

## 概述

Dashboard 数据统计模块为 Filmly 管理后台提供两个只读聚合接口，用于监控数据采集系统的运行状态与数据质量。

- `GET /api/dashboard/stats`：单次返回所有核心统计指标
- `GET /api/dashboard/trends`：返回近期各实体新增趋势，支持按天数和实体类型筛选

所有接口均受 `auth:api` middleware 保护，统计结果使用 Redis 缓存（stats TTL 10 分钟，trends TTL 5 分钟）。本模块无 Eloquent Model，全部通过 `DB::table()` 执行只读查询。

---

## 架构

遵循项目标准分层：

```
GET /api/dashboard/stats
GET /api/dashboard/trends
  └── routes/api.php（auth:api 组）
        └── GetTrendsRequest（FormRequest，仅 trends 接口需要参数验证）
              └── DashboardController（继承 BaseController）
                    └── DashboardService（聚合统计 + Redis 缓存逻辑）
                          └── DashboardRepositoryInterface
                                └── DashboardRepository（DB::table() 只读查询）
```

**设计决策：**

1. **无 Model 层**：Dashboard 模块跨多张表做聚合统计，不对应任何单一 Eloquent Model，直接使用 `DB::table()` 查询，符合项目对只读统计场景的约定。

2. **单一 Repository**：所有统计查询集中在 `DashboardRepository` 中，避免跨多个 Repository 分散管理，Service 层负责聚合和缓存逻辑。

3. **子项容错**：`getStats()` 内部对每个统计子项独立 try-catch，任意子项失败时记录 error 日志并返回 `null`，不影响其他子项（需求 8.4）。

4. **缓存键设计**：
   - stats 缓存键：`dashboard:stats`，TTL 600 秒（10 分钟）
   - trends 缓存键：`dashboard:trends:{days}:{sorted_entities}`，TTL 300 秒（5 分钟）
   - 使用 sorted entities 保证 `movies,tv_shows` 和 `tv_shows,movies` 命中同一缓存

---

## 组件与接口

### DashboardController

```
app/Http/Controllers/Api/DashboardController.php
```

| 方法 | 路由 | 说明 |
|------|------|------|
| `stats()` | `GET /api/dashboard/stats` | 返回所有聚合统计指标 |
| `trends(GetTrendsRequest $request)` | `GET /api/dashboard/trends` | 返回近期新增趋势 |

- 继承 `BaseController`，调用 `$this->success()` 返回信封格式响应
- 构造函数注入 `DashboardService`

### DashboardService

```
app/Services/DashboardService.php
```

| 方法 | 返回类型 | 说明 |
|------|---------|------|
| `getStats()` | `array` | 聚合所有统计子项，带 Redis 缓存（TTL 10 分钟） |
| `getTrends(int $days, array $entities)` | `array` | 返回趋势数据，带 Redis 缓存（TTL 5 分钟） |
| `private buildTrendSeries(array $rows, array $dates, array $entities)` | `array` | 将查询结果填充为等长日期数组 |
| `private computeStaleStatus(array $freshnessRows)` | `array` | 计算各表 is_stale 标记（超 48 小时） |
| `private computeSnapshotHealth(array $presentDates, int $days)` | `array` | 对比完整日期序列，生成缺失日期列表 |

**缓存策略：**

```php
// stats 缓存
Cache::remember('dashboard:stats', 600, fn() => $this->fetchStats());

// trends 缓存（键包含参数）
$cacheKey = 'dashboard:trends:' . $days . ':' . implode(',', $sortedEntities);
Cache::remember($cacheKey, 300, fn() => $this->fetchTrends($days, $entities));
```

### DashboardRepositoryInterface

```
app/Repositories/Contracts/DashboardRepositoryInterface.php
```

| 方法签名 | 说明 |
|---------|------|
| `getEntityCounts(): array` | 返回 9 张表的 COUNT(*) |
| `getReconcileRates(): array` | 返回 4 张关系表的 total/resolved |
| `getTranslationCoverage(): array` | 返回 4 张参考表的 total/translated |
| `getDataFreshness(): array` | 返回 6 张表的 MAX(updated_at) |
| `getSnapshotDates(int $days): array` | 返回最近 N 天内出现过的 snapshot_date 去重集合 |
| `getTrendRows(int $days, array $entities): array` | 返回各实体按天新增条数原始数据 |

### DashboardRepository

```
app/Repositories/DashboardRepository.php
```

不继承 `BaseRepository`（BaseRepository 依赖 Eloquent Model，本模块无 Model）。直接使用 `DB::table()` 执行查询。

**关键查询说明：**

- `getEntityCounts()`：对 9 张表各执行一次 `DB::table($table)->count()`，利用 MySQL InnoDB 的 COUNT(*) 优化
- `getSnapshotDates()`：`SELECT DISTINCT snapshot_date FROM media_list_snapshots WHERE snapshot_date >= ?`，利用 `(list_type, snapshot_date, rank)` 索引中的 `snapshot_date` 字段
- `getTrendRows()`：对每个实体执行 `GROUP BY DATE(created_at)`，`persons` 表强制加 `WHERE created_at >= NOW() - INTERVAL ? DAY` 利用索引

### GetTrendsRequest

```
app/Http/Requests/GetTrendsRequest.php
```

| 参数 | 类型 | 默认值 | 验证规则 |
|------|------|--------|---------|
| `days` | int | 30 | `nullable\|integer\|in:7,30,90` |
| `entities` | string | `movies,tv_shows,persons` | `nullable\|string` + 自定义规则校验每个值在白名单内 |

`prepareForValidation()` 将 `entities` 字符串拆分为数组后再验证各元素。

---

## 数据模型

本模块无 Eloquent Model，所有数据通过 `DB::table()` 查询后以 PHP 数组形式在层间传递。

### Stats 响应结构

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "entity_counts": {
      "movies": 1050000,
      "tv_shows": 210000,
      "persons": 5200000,
      "tv_seasons": 980000,
      "tv_episodes": 20000000,
      "keywords": 450000,
      "collections": 12000,
      "tv_networks": 3500,
      "production_companies": 180000
    },
    "reconcile_rates": {
      "movie_credits":      { "total": 50000000, "resolved": 48000000, "rate": 0.9600 },
      "tv_show_creators":   { "total": 120000,   "resolved": 115000,   "rate": 0.9583 },
      "tv_episode_credits": { "total": 80000000, "resolved": 75000000, "rate": 0.9375 },
      "collection_movies":  { "total": 60000,    "resolved": 58000,    "rate": 0.9667 }
    },
    "translation_coverage": {
      "departments": { "total": 20,    "translated": 20,    "rate": 1.0000 },
      "jobs":        { "total": 3000,  "translated": 2800,  "rate": 0.9333 },
      "keywords":    { "total": 450000,"translated": 200000,"rate": 0.4444 },
      "languages":   { "total": 180,   "translated": 180,   "rate": 1.0000 }
    },
    "data_freshness": {
      "movies":      { "last_updated_at": "2024-01-15T08:30:00Z", "is_stale": false },
      "tv_shows":    { "last_updated_at": "2024-01-15T08:30:00Z", "is_stale": false },
      "persons":     { "last_updated_at": "2024-01-14T06:00:00Z", "is_stale": false },
      "tv_seasons":  { "last_updated_at": "2024-01-15T08:30:00Z", "is_stale": false },
      "tv_episodes": { "last_updated_at": "2024-01-15T08:30:00Z", "is_stale": false },
      "keywords":    { "last_updated_at": null,                   "is_stale": true  }
    },
    "snapshot_health": {
      "checked_days": 30,
      "healthy_days": 28,
      "missing_dates": ["2024-01-10", "2024-01-11"]
    }
  }
}
```

### Trends 响应结构

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "dates": ["2024-01-01", "2024-01-02", "..."],
    "series": {
      "movies":   [120, 95, 0, 200, "..."],
      "tv_shows": [30,  25, 0, 40,  "..."],
      "persons":  [500, 480, 0, 600, "..."]
    }
  }
}
```

### 内部数据传递类型

Service 层方法返回 `array`（非裸 Model），各子项结构如下：

```php
// getEntityCounts() 返回
['movies' => 1050000, 'tv_shows' => 210000, ...]

// getReconcileRates() 返回（Repository 层）
[
  'movie_credits' => ['total' => 50000000, 'resolved' => 48000000],
  ...
]
// Service 层计算 rate 后追加
[
  'movie_credits' => ['total' => 50000000, 'resolved' => 48000000, 'rate' => 0.9600],
  ...
]

// getDataFreshness() 返回（Repository 层）
['movies' => '2024-01-15 08:30:00', 'tv_shows' => '2024-01-15 08:30:00', ...]
// Service 层追加 is_stale 后
['movies' => ['last_updated_at' => '2024-01-15T08:30:00Z', 'is_stale' => false], ...]
```

---

## 正确性属性

*属性（Property）是在系统所有合法执行路径上都应成立的特征或行为——本质上是对系统应做什么的形式化陈述。属性是人类可读规范与机器可验证正确性保证之间的桥梁。*

### 属性 1：未认证请求被拒绝

*对于任意* 未携带有效 JWT Token 的请求，访问 `/api/dashboard/stats` 或 `/api/dashboard/trends` 时，响应中的 `code` 字段应为 `401`。

**验证：需求 1.1、1.2**

### 属性 2：Stats 响应包含所有必需顶层字段

*对于任意* 已认证的 stats 请求，响应 `data` 对象应同时包含 `entity_counts`、`reconcile_rates`、`translation_coverage`、`data_freshness`、`snapshot_health` 五个字段，且均不缺失，整体 `code` 为 `0`。

**验证：需求 8.1、8.2**

### 属性 3：entity_counts 包含所有指定实体且值为非负整数

*对于任意* 已认证的 stats 请求，`data.entity_counts` 应包含 `movies`、`tv_shows`、`persons`、`tv_seasons`、`tv_episodes`、`keywords`、`collections`、`tv_networks`、`production_companies` 九个键，且每个值均为非负整数。

**验证：需求 2.1、2.5**

### 属性 4：reconcile_rates 与 translation_coverage 结构完整性

*对于任意* 已认证的 stats 请求，`data.reconcile_rates` 中每个关系表条目（`movie_credits`、`tv_show_creators`、`tv_episode_credits`、`collection_movies`）以及 `data.translation_coverage` 中每个参考表条目（`departments`、`jobs`、`keywords`、`languages`）均应包含 `total`（非负整数）、`resolved`/`translated`（非负整数，且 ≤ total）、`rate`（0.0–1.0 浮点数，保留 4 位小数）三个字段。

**验证：需求 4.1、4.3、5.1、5.3**

### 属性 5：rate 计算正确性

*对于任意* `total`（非负整数）和 `resolved`/`translated`（0 ≤ value ≤ total）的组合，计算出的 `rate` 应满足：`total = 0` 时 `rate = 1.0`；`total > 0` 时 `rate = value / total`（保留 4 位小数），且结果始终在 `[0.0, 1.0]` 范围内。

**验证：需求 4.2、5.2**

### 属性 6：data_freshness 结构完整性

*对于任意* 已认证的 stats 请求，`data.data_freshness` 中每个表条目（`movies`、`tv_shows`、`persons`、`tv_seasons`、`tv_episodes`、`keywords`）应包含 `last_updated_at`（ISO 8601 字符串或 null）和 `is_stale`（布尔值）两个字段。

**验证：需求 6.1、6.3、6.5**

### 属性 7：is_stale 计算正确性

*对于任意* `last_updated_at` 时间戳（包括 null），`is_stale` 的值应满足：`last_updated_at` 为 null 时 `is_stale = true`；`last_updated_at` 与当前时间差超过 48 小时时 `is_stale = true`；否则 `is_stale = false`。

**验证：需求 6.4**

### 属性 8：snapshot_health 结构与 missing_dates 计算正确性

*对于任意* 已认证的 stats 请求，`data.snapshot_health` 应包含 `checked_days`（值为 30）、`healthy_days`（非负整数，≤ 30）、`missing_dates`（升序日期字符串数组）三个字段；且 `missing_dates` 应等于最近 30 天完整日期序列与实际存在快照日期集合的差集。

**验证：需求 7.1、7.3、7.4**

### 属性 9：trends 非法参数返回 422

*对于任意* `days` 参数不在 `[7, 30, 90]` 内的请求，响应 `code` 应为 `422`；*对于任意* `entities` 参数包含不支持实体名的请求，响应 `code` 应为 `422`。

**验证：需求 3.3、3.4**

### 属性 10：trends 响应 dates 与 series 等长

*对于任意* 合法的 trends 请求，`data.dates` 数组长度应等于 `days` 参数值，且 `data.series` 中每个请求实体对应的数组长度与 `data.dates` 相同，无数据的日期填充 `0`。

**验证：需求 3.5、8.3**

### 属性 11：子项失败不影响其他子项

*对于任意* stats 请求，若某个统计子项查询抛出异常，该子项对应字段应为 `null`，其余子项应正常返回非 null 值，整体响应 `code` 仍为 `0`。

**验证：需求 8.4**

---

## 错误处理

### 参数验证错误（422）

`GetTrendsRequest` 验证失败时，Laravel 全局异常处理器统一转换为信封格式：

```json
{ "code": 422, "message": "参数错误：days 只允许 7、30 或 90", "data": null }
```

### 认证错误（401）

JWT middleware 拦截，返回：

```json
{ "code": 401, "message": "未认证，请先登录", "data": null }
```

### 统计子项查询失败（容错）

`DashboardService::getStats()` 对每个子项独立 try-catch：

```php
try {
    $entityCounts = $this->repository->getEntityCounts();
} catch (\Throwable $e) {
    Log::error('Dashboard entity counts query failed', ['error' => $e->getMessage()]);
    $entityCounts = null;
}
```

子项失败时对应字段返回 `null`，整体响应 `code` 仍为 `0`，不向上抛出异常。

### Redis 不可用

若 Redis 连接失败，`Cache::remember()` 会降级为直接执行回调（不缓存），查询仍可正常返回，不影响接口可用性。

---

## 测试策略

### 双轨测试方法

本模块采用 Feature Test（主）+ Unit Test（按需）的组合策略，两者互补：Feature Test 验证 HTTP 层行为，Unit Test 验证 Service 层复杂业务逻辑。

### Feature Test

位置：`tests/Feature/Dashboard/`

测试文件：
- `DashboardStatsTest.php`：测试 `/api/dashboard/stats` 接口
- `DashboardTrendsTest.php`：测试 `/api/dashboard/trends` 接口

所有 Feature Test 使用 `$this->mock(DashboardService::class, ...)` mock Service 层，不依赖真实数据库，使用 `RefreshDatabase` trait（仅用于创建测试用 User）。

**必须覆盖的场景：**

| 场景 | 对应属性 |
|------|---------|
| stats 未认证请求返回 code 401 | 属性 1 |
| trends 未认证请求返回 code 401 | 属性 1 |
| stats 响应包含五个顶层字段，code 为 0 | 属性 2 |
| entity_counts 包含 9 个实体键且值为整数 | 属性 3 |
| reconcile_rates 每项含 total/resolved/rate，resolved ≤ total | 属性 4 |
| translation_coverage 每项含 total/translated/rate，translated ≤ total | 属性 4 |
| data_freshness 每项含 last_updated_at/is_stale | 属性 6 |
| snapshot_health 含 checked_days/healthy_days/missing_dates | 属性 8 |
| trends days=999 返回 422 | 属性 9 |
| trends entities=invalid 返回 422 | 属性 9 |
| trends 合法请求 dates 数组长度等于 days | 属性 10 |
| trends series 中每个实体数组长度与 dates 相同 | 属性 10 |
| 子项失败时对应字段为 null，其余正常，code 为 0 | 属性 11 |

**Feature Test 示例：**

```php
// DashboardStatsTest.php
class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        // Feature: dashboard, Property 1: 未认证请求被拒绝
        $this->getJson('/api/dashboard/stats')
             ->assertJson(['code' => 401]);
    }

    public function test_stats_response_contains_all_top_level_fields(): void
    {
        // Feature: dashboard, Property 2: Stats 响应包含所有必需顶层字段
        $this->mock(DashboardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getStats')->once()->andReturn([
                'entity_counts'        => ['movies' => 100, 'tv_shows' => 50, 'persons' => 200,
                                           'tv_seasons' => 30, 'tv_episodes' => 500, 'keywords' => 1000,
                                           'collections' => 10, 'tv_networks' => 20, 'production_companies' => 80],
                'reconcile_rates'      => ['movie_credits' => ['total' => 100, 'resolved' => 90, 'rate' => 0.9000]],
                'translation_coverage' => ['departments' => ['total' => 20, 'translated' => 20, 'rate' => 1.0000]],
                'data_freshness'       => ['movies' => ['last_updated_at' => '2024-01-15T08:30:00Z', 'is_stale' => false]],
                'snapshot_health'      => ['checked_days' => 30, 'healthy_days' => 28, 'missing_dates' => ['2024-01-10']],
            ]);
        });

        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/dashboard/stats')
             ->assertJson(['code' => 0])
             ->assertJsonStructure(['data' => [
                 'entity_counts', 'reconcile_rates',
                 'translation_coverage', 'data_freshness', 'snapshot_health',
             ]]);
    }

    public function test_failed_subquery_returns_null_for_that_field_only(): void
    {
        // Feature: dashboard, Property 11: 子项失败不影响其他子项
        $this->mock(DashboardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getStats')->once()->andReturn([
                'entity_counts'        => null,  // 模拟该子项失败
                'reconcile_rates'      => ['movie_credits' => ['total' => 100, 'resolved' => 90, 'rate' => 0.9000]],
                'translation_coverage' => ['departments' => ['total' => 20, 'translated' => 20, 'rate' => 1.0000]],
                'data_freshness'       => ['movies' => ['last_updated_at' => '2024-01-15T08:30:00Z', 'is_stale' => false]],
                'snapshot_health'      => ['checked_days' => 30, 'healthy_days' => 30, 'missing_dates' => []],
            ]);
        });

        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/dashboard/stats')
             ->assertJson(['code' => 0, 'data' => ['entity_counts' => null]])
             ->assertJsonPath('data.reconcile_rates.movie_credits.total', 100);
    }
}
```

### Property-Based Testing

本模块使用 [eris/eris](https://github.com/giorgiosironi/eris)（PHP property-based testing 库）实现属性测试，每个属性测试最少运行 **100 次**随机输入。

测试文件位置：`tests/Unit/Dashboard/`

每个属性测试必须在注释中标注对应设计属性，格式：

```
// Feature: dashboard, Property {N}: {属性标题}
```

**属性测试覆盖：**

| 属性 | 测试方法 | 生成器策略 |
|------|---------|-----------|
| 属性 5：rate 计算正确性 | `test_rate_calculation_is_correct` | 生成随机 total（0–10000000）和 resolved（0–total），验证 rate 在 [0,1] 内，total=0 时 rate=1.0 |
| 属性 7：is_stale 计算正确性 | `test_is_stale_calculation_is_correct` | 生成随机时间戳（过去 0–200 小时），验证 is_stale 等于（差值 > 48h），null 时为 true |
| 属性 8：missing_dates 计算正确性 | `test_missing_dates_calculation_is_correct` | 生成随机 present_dates 子集，验证 missing_dates = 完整30天序列 - present_dates |
| 属性 10：trends series 填充 | `test_trend_series_fills_zero_for_missing_dates` | 生成随机日期范围和稀疏数据行，验证输出数组长度等于 days，缺失日期填 0 |

### Unit Test

位置：`tests/Unit/Dashboard/DashboardServiceTest.php`

覆盖 Service 层的复杂业务逻辑（mock Repository），重点测试边界情况：

- `is_stale` 边界：`last_updated_at` 为 null 时应为 `true`；差值恰好等于 48 小时时的边界行为（应为 `false`）；差值为 48 小时 + 1 秒时应为 `true`
- `missing_dates` 边界：全部 30 天缺失、全部 30 天存在、仅缺失第一天/最后一天
- trends series 填充：无数据日期填 0、所有日期均有数据、只有部分日期有数据

