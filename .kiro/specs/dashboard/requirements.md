# 需求文档

## 简介

Dashboard 数据统计模块为 Filmly 管理后台提供两个只读聚合接口，用于监控数据采集系统的运行状态与数据质量。

- `GET /api/dashboard/stats`：单次返回所有核心统计指标（实体总量、异步关联完成率、翻译覆盖率、数据新鲜度、每日采集健康度）
- `GET /api/dashboard/trends`：返回近期各实体新增趋势数据，支持按天数和实体类型筛选

所有接口均为只读查询，加 `auth:api` middleware，统计结果使用 Redis 缓存（TTL 5–15 分钟）。大表（persons / tv_episodes）查询需注意性能。

---

## 术语表

- **Dashboard_Stats_API**：`GET /api/dashboard/stats` 接口，返回所有聚合统计指标
- **Dashboard_Trends_API**：`GET /api/dashboard/trends` 接口，返回各实体按天新增趋势
- **DashboardService**：负责聚合所有统计指标的 Service 层组件
- **DashboardRepository**：负责执行所有只读统计查询的 Repository 层组件
- **实体总量（Entity Count）**：指定数据表的当前总行数（COUNT(*)）
- **异步关联完成率（Reconcile Rate）**：关系表中 `person_id` 或 `movie_id` 非 NULL 的行数占总行数的比例
- **翻译覆盖率（Translation Coverage）**：参考数据表中 `translated_at IS NOT NULL` 的行数占总行数的比例
- **数据新鲜度（Data Freshness）**：各主要表的 `MAX(updated_at)`，超过 48 小时视为异常
- **每日采集健康度（Daily Snapshot Health）**：基于 `media_list_snapshots.snapshot_date`，检查最近 N 天内每天是否均有快照写入
- **新增趋势（Daily Trend）**：按 `created_at` 字段 GROUP BY DATE 统计各实体每天新增条数
- **Redis 缓存**：用于缓存统计结果，降低大表重复查询压力

---

## 需求列表

### 需求 1：认证与访问控制

**用户故事：** 作为系统管理员，我希望 Dashboard 接口受到认证保护，以确保统计数据不被未授权访问。

#### 验收标准

1. WHEN 请求 `GET /api/dashboard/stats` 时未携带有效 JWT Token，THE Dashboard_Stats_API SHALL 返回 `{"code": 401, "message": "未认证，请先登录", "data": null}`
2. WHEN 请求 `GET /api/dashboard/trends` 时未携带有效 JWT Token，THE Dashboard_Trends_API SHALL 返回 `{"code": 401, "message": "未认证，请先登录", "data": null}`
3. THE Dashboard_Stats_API SHALL 注册在 `auth:api` middleware 组内
4. THE Dashboard_Trends_API SHALL 注册在 `auth:api` middleware 组内

---

### 需求 2：各实体总量统计

**用户故事：** 作为系统管理员，我希望一次性获取所有主要实体的当前总条数，以便快速了解数据库规模。

#### 验收标准

1. WHEN 认证用户请求 `GET /api/dashboard/stats`，THE Dashboard_Stats_API SHALL 在响应的 `data.entity_counts` 字段中返回以下实体的总条数：`movies`、`tv_shows`、`persons`、`tv_seasons`、`tv_episodes`、`keywords`、`collections`、`tv_networks`、`production_companies`
2. THE DashboardRepository SHALL 通过 `COUNT(*)` 查询各表总行数，不附加任何 WHERE 条件
3. WHILE Redis 缓存有效（TTL 未过期），THE DashboardService SHALL 直接返回缓存结果，不重新查询数据库
4. WHEN Redis 缓存不存在或已过期，THE DashboardService SHALL 重新查询数据库并将结果写入 Redis，TTL 为 10 分钟
5. THE Dashboard_Stats_API SHALL 在响应中以整数类型返回各实体总量，字段名与表名保持一致（如 `movies`、`tv_shows`）

---

### 需求 3：近期新增趋势

**用户故事：** 作为系统管理员，我希望查看各实体近期每天的新增条数，以便通过折线图监控采集进度。

#### 验收标准

1. WHEN 认证用户请求 `GET /api/dashboard/trends`，THE Dashboard_Trends_API SHALL 接受 `days` 参数（整数，可选，默认 30，允许值：7、30、90）
2. WHEN 认证用户请求 `GET /api/dashboard/trends`，THE Dashboard_Trends_API SHALL 接受 `entities` 参数（逗号分隔字符串，可选，默认 `movies,tv_shows,persons`，允许值：`movies`、`tv_shows`、`persons`）
3. IF `days` 参数不在允许值 `[7, 30, 90]` 内，THEN THE Dashboard_Trends_API SHALL 返回 `{"code": 422, "message": "参数错误：days 只允许 7、30 或 90", "data": null}`
4. IF `entities` 参数包含不在允许值内的实体名，THEN THE Dashboard_Trends_API SHALL 返回 `{"code": 422, "message": "参数错误：entities 包含不支持的实体类型", "data": null}`
5. WHEN 请求合法，THE Dashboard_Trends_API SHALL 返回结构为 `{"data": {"dates": [...], "series": {"movies": [...], "tv_shows": [...], "persons": [...]}}}` 的响应，`dates` 为日期数组（`Y-m-d` 格式），`series` 中每个实体对应与 `dates` 等长的整数数组（无数据的日期填 0）
6. THE DashboardRepository SHALL 通过 `GROUP BY DATE(created_at)` 聚合各实体在指定日期范围内的每日新增条数
7. WHILE Redis 缓存有效，THE DashboardService SHALL 直接返回缓存结果；WHEN 缓存不存在或已过期，THE DashboardService SHALL 重新查询并写入 Redis，TTL 为 5 分钟
8. THE DashboardRepository SHALL 对 `persons` 表的趋势查询限定在 `created_at >= NOW() - INTERVAL days DAY` 范围内，利用 `created_at` 索引避免全表扫描

---

### 需求 4：异步关联完成率

**用户故事：** 作为系统管理员，我希望查看各关系表的 reconcile 进度，以便了解异步关联补填的完成情况。

#### 验收标准

1. WHEN 认证用户请求 `GET /api/dashboard/stats`，THE Dashboard_Stats_API SHALL 在响应的 `data.reconcile_rates` 字段中返回以下四个关系表的完成率：`movie_credits`、`tv_show_creators`、`tv_episode_credits`、`collection_movies`
2. THE DashboardRepository SHALL 对每个关系表执行以下查询：`total`（总行数）和 `resolved`（`person_id IS NOT NULL` 或 `movie_id IS NOT NULL` 的行数），计算 `rate = resolved / total`（total 为 0 时 rate 为 1.0）
3. THE Dashboard_Stats_API SHALL 以如下结构返回每个关系表的完成率数据：`{"total": 整数, "resolved": 整数, "rate": 0.00–1.00 的浮点数（保留4位小数）}`
4. WHILE Redis 缓存有效，THE DashboardService SHALL 直接返回缓存结果，不重新查询数据库

---

### 需求 5：翻译覆盖率

**用户故事：** 作为系统管理员，我希望查看各参考数据表的中文翻译覆盖情况，以便了解 LLM 翻译任务的进度。

#### 验收标准

1. WHEN 认证用户请求 `GET /api/dashboard/stats`，THE Dashboard_Stats_API SHALL 在响应的 `data.translation_coverage` 字段中返回以下四个表的翻译覆盖率：`departments`、`jobs`、`keywords`、`languages`
2. THE DashboardRepository SHALL 对每个表执行以下查询：`total`（总行数）和 `translated`（`translated_at IS NOT NULL` 的行数），计算 `rate = translated / total`（total 为 0 时 rate 为 1.0）
3. THE Dashboard_Stats_API SHALL 以如下结构返回每个表的翻译覆盖率数据：`{"total": 整数, "translated": 整数, "rate": 0.00–1.00 的浮点数（保留4位小数）}`
4. WHILE Redis 缓存有效，THE DashboardService SHALL 直接返回缓存结果，不重新查询数据库

---

### 需求 6：数据新鲜度

**用户故事：** 作为系统管理员，我希望查看各主要表的最后写入时间，以便发现采集任务是否长时间未运行。

#### 验收标准

1. WHEN 认证用户请求 `GET /api/dashboard/stats`，THE Dashboard_Stats_API SHALL 在响应的 `data.data_freshness` 字段中返回以下表的最后更新时间：`movies`、`tv_shows`、`persons`、`tv_seasons`、`tv_episodes`、`keywords`
2. THE DashboardRepository SHALL 对每个表执行 `SELECT MAX(updated_at)` 查询，返回最新的 `updated_at` 时间戳
3. THE Dashboard_Stats_API SHALL 以 ISO 8601 格式（UTC）返回 `last_updated_at` 字段，若表为空则返回 `null`
4. THE DashboardService SHALL 将当前时间与 `last_updated_at` 对比，若差值超过 48 小时则将该表的 `is_stale` 字段标记为 `true`，否则为 `false`
5. THE Dashboard_Stats_API SHALL 以如下结构返回每个表的新鲜度数据：`{"last_updated_at": "ISO8601字符串或null", "is_stale": true/false}`
6. WHILE Redis 缓存有效，THE DashboardService SHALL 直接返回缓存结果，不重新查询数据库

---

### 需求 7：每日采集健康度

**用户故事：** 作为系统管理员，我希望查看最近 N 天内每天是否都有快照写入，以便发现采集任务缺失的日期。

#### 验收标准

1. WHEN 认证用户请求 `GET /api/dashboard/stats`，THE Dashboard_Stats_API SHALL 在响应的 `data.snapshot_health` 字段中返回最近 30 天的每日快照健康状态
2. THE DashboardRepository SHALL 查询 `media_list_snapshots` 表中最近 30 天内出现过的所有 `snapshot_date` 的去重集合，利用 `(list_type, snapshot_date, rank)` 索引避免全表扫描
3. THE DashboardService SHALL 将查询结果与最近 30 天的完整日期序列对比，生成每天是否有快照的布尔映射
4. THE Dashboard_Stats_API SHALL 以如下结构返回快照健康数据：`{"checked_days": 30, "healthy_days": 整数, "missing_dates": ["Y-m-d", ...]}` ，`missing_dates` 为缺失快照的日期列表（升序排列）
5. WHILE Redis 缓存有效，THE DashboardService SHALL 直接返回缓存结果，不重新查询数据库

---

### 需求 8：响应结构完整性

**用户故事：** 作为前端开发者，我希望 `GET /api/dashboard/stats` 一次返回所有统计指标，以减少请求次数。

#### 验收标准

1. WHEN 认证用户请求 `GET /api/dashboard/stats`，THE Dashboard_Stats_API SHALL 在单次响应中同时包含 `entity_counts`、`reconcile_rates`、`translation_coverage`、`data_freshness`、`snapshot_health` 五个顶层字段
2. THE Dashboard_Stats_API SHALL 遵循项目信封格式，返回 `{"code": 0, "message": "success", "data": {...}}`
3. THE Dashboard_Trends_API SHALL 遵循项目信封格式，返回 `{"code": 0, "message": "success", "data": {"dates": [...], "series": {...}}}`
4. IF 任意统计子项查询失败，THEN THE DashboardService SHALL 记录 error 级别日志并在对应字段返回 `null`，不影响其他子项的正常返回
