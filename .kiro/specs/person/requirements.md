# 需求文档：Person 人物模块

## 简介

本模块为 Filmly Management Backend 提供人物（Person）数据的只读 API，包含列表接口和详情接口。

`persons` 表是只读大表（500 万+ 条），由数据采集项目从 TMDB 写入，本项目只做查询输出。列表接口支持按 `gender`、`adult`、`known_for_department` 筛选，支持按 `id`、`popularity`、`updated_at`、`created_at` 排序。所有接口需要 `auth:api` 认证。

---

## 词汇表

- **Person**：人物实体，对应数据库 `persons` 表，存储演员、导演等影视从业人员信息
- **PersonList**：人物列表接口，返回分页结果
- **PersonDetail**：人物详情接口，返回单条人物完整信息
- **known_for_department**：人物最擅长的部门名称，如 `Acting`、`Directing`，来自 TMDB 原始数据
- **gender**：性别编码，`0`=未知，`1`=女，`2`=男，`3`=非二元
- **大表约束**：`persons` 表 500 万+ 条，查询时必须有筛选条件或限制 `per_page ≤ 50`，禁止全表扫描

---

## 需求列表

### 需求 1：人物列表接口

**用户故事：** 作为管理员，我希望能分页浏览人物列表，并通过筛选和排序快速定位目标人物，以便高效管理影视从业人员数据。

#### 验收标准

1. THE PersonList 接口 SHALL 要求请求携带有效的 `auth:api` JWT Token，未携带或 Token 无效时返回 `code: 401`
2. WHEN 请求 `GET /api/persons` 时，THE PersonList 接口 SHALL 返回信封格式响应，`code` 为 `0`，`data` 包含 `list` 数组和 `pagination` 对象（含 `total`、`page`、`per_page`、`last_page`）
3. THE PersonList 接口 SHALL 将 `per_page` 默认值设为 `20`，最大值限制为 `50`，超过 `50` 时返回 `code: 422`
4. THE PersonList 接口 SHALL 将 `page` 默认值设为 `1`，最大值限制为 `1000`，超过 `1000` 时返回 `code: 422`
5. WHEN 请求包含 `gender` 参数时，THE PersonList 接口 SHALL 仅返回 `gender` 字段等于该值的人物，`gender` 合法值为 `0`、`1`、`2`、`3`，传入其他值时返回 `code: 422`
6. WHEN 请求包含 `adult` 参数时，THE PersonList 接口 SHALL 仅返回 `adult` 字段等于该值的人物，`adult` 合法值为 `0`（false）或 `1`（true），传入其他值时返回 `code: 422`
7. WHEN 请求包含 `known_for_department` 参数时，THE PersonList 接口 SHALL 仅返回 `known_for_department` 字段等于该值的人物，参数最大长度为 `100` 字符
8. WHEN 请求包含 `q` 参数时，THE PersonList 接口 SHALL 对 `name` 字段执行前缀匹配（`LIKE q%`），`q` 最大长度为 `100` 字符
9. WHEN 请求包含 `sort` 参数时，THE PersonList 接口 SHALL 按指定字段排序，合法排序字段为 `id`、`popularity`、`updated_at`、`created_at`，传入其他值时返回 `code: 422`
10. WHEN 请求包含 `order` 参数时，THE PersonList 接口 SHALL 按指定方向排序，合法值为 `asc` 或 `desc`，默认值为 `desc`
11. IF `sort` 参数未传入，THEN THE PersonList 接口 SHALL 默认按 `id` 降序排列
12. THE PersonList 接口 SHALL 在每条人物记录中输出以下字段：`id`、`tmdb_id`、`name`、`gender`、`adult`、`known_for_department`、`popularity`、`profile_path`（由 `ImageHelper::url($path, 'w185')` 拼接完整 URL，null 时输出 null）、`created_at`、`updated_at`
13. IF `per_page` 未超过 `50` 且无任何筛选条件，THEN THE PersonList 接口 SHALL 允许查询执行（依赖 `per_page ≤ 50` 约束防止全表扫描）

### 需求 2：人物详情接口

**用户故事：** 作为管理员，我希望能查看某个人物的完整信息，以便核查和展示影视从业人员的详细资料。

#### 验收标准

1. THE PersonDetail 接口 SHALL 要求请求携带有效的 `auth:api` JWT Token，未携带或 Token 无效时返回 `code: 401`
2. WHEN 请求 `GET /api/persons/{id}` 且人物存在时，THE PersonDetail 接口 SHALL 返回信封格式响应，`code` 为 `0`，`data` 包含该人物的完整字段
3. IF 路由参数 `{id}` 对应的人物在数据库中不存在，THEN THE PersonDetail 接口 SHALL 返回 `code: 404`，`message` 为 `"人物不存在"`
4. THE PersonDetail 接口 SHALL 输出以下字段：`id`、`tmdb_id`、`name`、`gender`、`adult`、`biography`、`birthday`（`Y-m-d` 格式，null 时输出 null）、`deathday`（`Y-m-d` 格式，null 时输出 null）、`place_of_birth`、`known_for_department`、`popularity`、`homepage`、`imdb_id`、`also_known_as`、`profile_path`（由 `ImageHelper::url($path, 'w342')` 拼接完整 URL，null 时输出 null）、`created_at`（ISO 8601 UTC 格式）、`updated_at`（ISO 8601 UTC 格式）

### 需求 3：大表查询安全约束

**用户故事：** 作为系统架构师，我希望 persons 大表的查询始终受到约束，以便防止全表扫描导致数据库性能问题。

#### 验收标准

1. THE PersonRepository SHALL 在 `paginateWithFilters` 方法的注释中标注大表约束：`per_page ≤ 50`，禁止无条件全表扫描
2. WHEN `per_page` 超过 `50` 时，THE PersonList 接口 SHALL 在 FormRequest 验证阶段拒绝请求并返回 `code: 422`，不将请求传递到 Repository 层
3. THE PersonList 接口 SHALL 对所有排序字段执行白名单校验，禁止将用户输入直接传入 `orderBy()` 方法

### 需求 4：响应格式与时间字段

**用户故事：** 作为前端开发者，我希望 API 响应格式统一，以便减少前端处理逻辑。

#### 验收标准

1. THE PersonDetail 接口 SHALL 将 `birthday` 和 `deathday` 字段以 `Y-m-d` 格式输出，字段为 null 时输出 null
2. THE PersonList 接口 和 THE PersonDetail 接口 SHALL 将 `created_at` 和 `updated_at` 字段以 ISO 8601 UTC 格式（`Y-m-d\TH:i:s\Z`）输出，禁止直接输出 Carbon 对象

### 需求 5：人物参演电影列表接口

**用户故事：** 作为管理员，我希望能查看某个人物参演的所有电影，以便了解该人物的电影作品履历。

#### 验收标准

1. THE PersonMovies 接口 SHALL 要求请求携带有效的 `auth:api` JWT Token，未携带或 Token 无效时返回 `code: 401`
2. WHEN 请求 `GET /api/person-movies` 时，THE PersonMovies 接口 SHALL 要求 `person_id` 参数为必填整数，缺少或非整数时返回 `code: 422`
3. WHEN `person_id` 对应的人物在 `persons` 表中不存在，THEN THE PersonMovies 接口 SHALL 返回 `code: 404`，`message` 为 `"人物不存在"`
4. WHEN 请求包含合法的 `person_id` 参数时，THE PersonMovies 接口 SHALL 通过 `movie_credits.person_id = persons.id` 关联查询，返回该人物参演的电影分页列表，`person_id` 为 NULL 的 `movie_credits` 记录直接跳过不返回
5. THE PersonMovies 接口 SHALL 将 `per_page` 默认值设为 `20`，最大值限制为 `100`，超过 `100` 时返回 `code: 422`
6. THE PersonMovies 接口 SHALL 将 `page` 默认值设为 `1`，最大值限制为 `1000`，超过 `1000` 时返回 `code: 422`
7. THE PersonMovies 接口 SHALL 返回信封格式响应，`code` 为 `0`，`data` 包含 `list` 数组和 `pagination` 对象（含 `total`、`page`、`per_page`、`last_page`）
8. THE PersonMovies 接口 SHALL 在每条记录中输出以下字段：`id`（movie_credits.id）、`movie_id`、`credit_type`、`character`、`cast_order`、`department_id`、`job_id`，以及关联电影信息 `movie`（含 `id`、`tmdb_id`、`title`、`original_title`、`release_date`（`Y-m-d` 格式，null 时输出 null）、`poster_path`（由 `ImageHelper::url($path, 'w342')` 拼接完整 URL，null 时输出 null））
9. THE PersonMovies 接口 SHALL 对查询结果按 `movie_credits.id` 降序排列作为默认排序

### 需求 6：人物参演电视剧列表接口

**用户故事：** 作为管理员，我希望能查看某个人物参演的所有电视剧，以便了解该人物的电视剧作品履历。

#### 验收标准

1. THE PersonTvShows 接口 SHALL 要求请求携带有效的 `auth:api` JWT Token，未携带或 Token 无效时返回 `code: 401`
2. WHEN 请求 `GET /api/person-tv-shows` 时，THE PersonTvShows 接口 SHALL 要求 `person_id` 参数为必填整数，缺少或非整数时返回 `code: 422`
3. WHEN `person_id` 对应的人物在 `persons` 表中不存在，THEN THE PersonTvShows 接口 SHALL 返回 `code: 404`，`message` 为 `"人物不存在"`
4. WHEN 请求包含合法的 `person_id` 参数时，THE PersonTvShows 接口 SHALL 通过以下关联路径查询该人物参演的电视剧去重列表：`tv_episode_credits.person_id = persons.id` → `tv_episode_credits.tv_episode_id` → `tv_episodes.tv_show_id` → `tv_shows`，`person_id` 为 NULL 的 `tv_episode_credits` 记录直接跳过不返回
5. THE PersonTvShows 接口 SHALL 对查询结果按 `tv_show_id` 去重，每部电视剧只返回一条记录
6. THE PersonTvShows 接口 SHALL 将 `per_page` 默认值设为 `20`，最大值限制为 `100`，超过 `100` 时返回 `code: 422`
7. THE PersonTvShows 接口 SHALL 将 `page` 默认值设为 `1`，最大值限制为 `1000`，超过 `1000` 时返回 `code: 422`
8. THE PersonTvShows 接口 SHALL 返回信封格式响应，`code` 为 `0`，`data` 包含 `list` 数组和 `pagination` 对象（含 `total`、`page`、`per_page`、`last_page`）
9. THE PersonTvShows 接口 SHALL 在每条记录中输出以下电视剧字段：`id`、`tmdb_id`、`name`、`original_name`、`first_air_date`（`Y-m-d` 格式，null 时输出 null）、`poster_path`（由 `ImageHelper::url($path, 'w342')` 拼接完整 URL，null 时输出 null）、`status`、`number_of_seasons`、`number_of_episodes`
10. THE PersonTvShows 接口 SHALL 在 Repository 层的查询方法注释中标注实现约束：关联路径为 `tv_episode_credits → tv_episodes → tv_shows`，需通过子查询或 JOIN 实现，禁止在应用层循环查询（N+1）
