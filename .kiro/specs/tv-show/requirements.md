# 需求文档：TV Show 模块 API

## 简介

本模块为 Filmly Management Backend 实现完整的电视剧（TV Show）只读 API，覆盖电视剧主资源、季、集及其所有关联子资源。

TV Show 模块与已实现的 Movie 模块在架构上高度对称，但数据结构更复杂：TV Show 拥有三级层次结构（tv_show → tv_season → tv_episode），演职人员挂载在集级别（tv_episode_credits），并有电视网络（tv_networks）和创作者（tv_show_creators）等 Movie 没有的关联。

所有接口均为只读（GET），需要 `auth:api` 认证，遵循项目统一的信封响应格式。

---

## 词汇表

- **TV_Show_API**：处理电视剧主资源列表与详情的 API 系统
- **TV_Season_API**：处理电视剧季列表与详情的 API 系统
- **TV_Episode_API**：处理电视剧集列表与详情的 API 系统
- **TvShow**：电视剧主实体，对应数据库 `tv_shows` 表
- **TvSeason**：电视剧季实体，对应数据库 `tv_seasons` 表（100 万+ 条）
- **TvEpisode**：电视剧集实体，对应数据库 `tv_episodes` 表（2000 万+ 条）
- **TvEpisodeCredit**：集级别演职人员，对应数据库 `tv_episode_credits` 表（极大）
- **TvShowCreator**：电视剧创作者，对应数据库 `tv_show_creators` 表（异步关联）
- **TvNetwork**：电视网络，对应数据库 `tv_networks` 表
- **异步关联**：`person_id` 初始为 NULL，由采集项目 reconcile 步骤批量补填，API 层不报错
- **大表约束**：查询大表时必须携带父资源 ID 条件，防止全表扫描

---

## 需求

### 需求 1：电视剧列表

**用户故事：** 作为管理员，我希望能够分页浏览电视剧列表并按多种条件筛选，以便快速定位目标电视剧。

#### 验收标准

1. THE TV_Show_API SHALL 提供 `GET /api/tv-shows` 接口，返回分页的电视剧列表。
2. WHEN 请求携带有效的 `auth:api` token，THE TV_Show_API SHALL 返回 `code: 0` 的成功响应。
3. IF 请求未携带 token 或 token 无效，THEN THE TV_Show_API SHALL 返回 `code: 401` 的错误响应。
4. WHEN 请求携带 `q` 参数，THE TV_Show_API SHALL 对 `name` 和 `original_name` 字段执行前缀匹配（`LIKE q%`）过滤。
5. WHEN 请求携带 `genre_id` 参数，THE TV_Show_API SHALL 通过 `tv_show_genres` 关联表过滤指定类型的电视剧。
6. WHEN 请求携带 `status` 参数，THE TV_Show_API SHALL 对 `status` 字段执行精确匹配过滤。
7. WHEN 请求携带 `first_air_year` 参数，THE TV_Show_API SHALL 对 `first_air_date` 字段的年份部分执行精确匹配过滤。
8. WHEN 请求携带 `in_production` 参数（值为 `0` 或 `1`），THE TV_Show_API SHALL 对 `in_production` 字段执行布尔过滤。
9. WHEN 请求携带 `adult` 参数（值为 `0` 或 `1`），THE TV_Show_API SHALL 对 `adult` 字段执行布尔过滤。
10. WHEN 请求携带 `sort` 参数，THE TV_Show_API SHALL 仅允许 `popularity`、`first_air_date`、`vote_average`、`vote_count`、`id` 作为合法排序字段，其他值返回 422。
11. WHEN 请求携带 `order` 参数，THE TV_Show_API SHALL 仅允许 `asc` 或 `desc`，默认为 `desc`。
12. THE TV_Show_API SHALL 支持 `page`（最大 1000）和 `per_page`（最大 100）分页参数。
13. IF `page` 超过 1000，THEN THE TV_Show_API SHALL 返回 `code: 422` 的参数验证错误。
14. THE TV_Show_API SHALL 在列表响应中为每条记录输出 `id`、`tmdb_id`、`name`、`original_name`、`original_language`、`status`、`first_air_date`、`number_of_seasons`、`number_of_episodes`、`in_production`、`popularity`、`vote_average`、`vote_count`、`adult`、`poster_path`、`backdrop_path` 字段。
15. THE TV_Show_API SHALL 通过 `ImageHelper::url()` 将 `poster_path` 拼接为 `w342` 尺寸的完整 URL，将 `backdrop_path` 拼接为 `w780` 尺寸的完整 URL。

---

### 需求 2：电视剧详情

**用户故事：** 作为管理员，我希望能够查看单部电视剧的完整信息，以便了解该剧的所有元数据。

#### 验收标准

1. THE TV_Show_API SHALL 提供 `GET /api/tv-shows/{id}` 接口，返回指定电视剧的完整详情。
2. WHEN 指定 `id` 的电视剧存在，THE TV_Show_API SHALL 返回包含所有字段的详情响应。
3. IF 指定 `id` 的电视剧不存在，THEN THE TV_Show_API SHALL 返回 `code: 404` 的错误响应。
4. THE TV_Show_API SHALL 在详情响应中输出 `id`、`tmdb_id`、`name`、`original_name`、`original_language`、`overview`、`tagline`、`status`、`type`、`first_air_date`、`last_air_date`、`number_of_seasons`、`number_of_episodes`、`episode_run_time`、`popularity`、`vote_average`、`vote_count`、`adult`、`in_production`、`poster_path`、`backdrop_path`、`homepage`、`origin_country_codes`、`spoken_language_codes`、`language_codes`、`production_country_codes`、`last_episode_to_air`、`next_episode_to_air`、`created_at`、`updated_at` 字段。
5. THE TV_Show_API SHALL 通过 `ImageHelper::url()` 将 `poster_path` 拼接为 `w500` 尺寸的完整 URL，将 `backdrop_path` 拼接为 `original` 尺寸的完整 URL。

---

### 需求 3：电视剧关联子资源（类型、关键词、制作公司、电视网络）

**用户故事：** 作为管理员，我希望能够查看某部电视剧关联的类型、关键词、制作公司和电视网络，以便了解该剧的分类和制作信息。

#### 验收标准

1. THE TV_Show_API SHALL 提供 `GET /api/tv-show-genres?tv_show_id=xxx` 接口，返回指定电视剧关联的所有类型。
2. THE TV_Show_API SHALL 提供 `GET /api/tv-show-keywords?tv_show_id=xxx` 接口，返回指定电视剧关联的所有关键词。
3. THE TV_Show_API SHALL 提供 `GET /api/tv-show-production-companies?tv_show_id=xxx` 接口，返回指定电视剧关联的所有制作公司。
4. THE TV_Show_API SHALL 提供 `GET /api/tv-show-networks?tv_show_id=xxx` 接口，返回指定电视剧关联的所有电视网络。
5. WHEN 请求上述接口时，`tv_show_id` 参数为必填项，IF 缺少 `tv_show_id`，THEN THE TV_Show_API SHALL 返回 `code: 422` 的参数验证错误。
6. THE TV_Show_API SHALL 对上述四个接口返回不分页的全量列表（使用 `listing()` 方法）。
7. THE TV_Show_API SHALL 在类型响应中输出 `id`、`tmdb_id`、`name`、`type` 字段。
8. THE TV_Show_API SHALL 在关键词响应中输出 `id`、`tmdb_id`、`name` 字段。
9. THE TV_Show_API SHALL 在制作公司响应中输出 `id`、`tmdb_id`、`name`、`origin_country`、`logo_path` 字段，`logo_path` 使用 `w185` 尺寸。
10. THE TV_Show_API SHALL 在电视网络响应中输出 `id`、`tmdb_id`、`name`、`origin_country`、`logo_path` 字段，`logo_path` 使用 `w185` 尺寸。

---

### 需求 4：电视剧图片

**用户故事：** 作为管理员，我希望能够查看某部电视剧的所有图片，以便管理该剧的视觉素材。

#### 验收标准

1. THE TV_Show_API SHALL 提供 `GET /api/tv-show-images?tv_show_id=xxx` 接口，返回指定电视剧的分页图片列表。
2. WHEN 请求时，`tv_show_id` 参数为必填项，IF 缺少 `tv_show_id`，THEN THE TV_Show_API SHALL 返回 `code: 422` 的参数验证错误。
3. WHEN 请求携带 `image_type` 参数，THE TV_Show_API SHALL 仅允许 `poster`、`backdrop`、`logo` 作为合法值，其他值返回 422。
4. THE TV_Show_API SHALL 支持 `page`（最大 1000）和 `per_page`（最大 100）分页参数。
5. THE TV_Show_API SHALL 在图片响应中输出 `id`、`tv_show_id`、`image_type`、`file_path`、`width`、`height`、`vote_average`、`vote_count` 字段。
6. THE TV_Show_API SHALL 通过 `ImageHelper::url()` 将 `file_path` 拼接为完整 URL：`backdrop` 类型使用 `w780`，其他类型使用 `w342`。

---

### 需求 5：电视剧创作者

**用户故事：** 作为管理员，我希望能够查看某部电视剧的创作者列表，以便了解该剧的主创团队。

#### 验收标准

1. THE TV_Show_API SHALL 提供 `GET /api/tv-show-creators?tv_show_id=xxx` 接口，返回指定电视剧的不分页创作者列表。
2. WHEN 请求时，`tv_show_id` 参数为必填项，IF 缺少 `tv_show_id`，THEN THE TV_Show_API SHALL 返回 `code: 422` 的参数验证错误。
3. THE TV_Show_API SHALL 在创作者响应中输出 `tv_show_id`、`person_tmdb_id`、`person_id`、`person` 字段。
4. WHILE `person_id` 为 NULL（异步关联尚未完成），THE TV_Show_API SHALL 将 `person` 字段输出为 `null`，不报错，不过滤该条记录。
5. WHILE `person_id` 不为 NULL，THE TV_Show_API SHALL 在 `person` 字段中输出关联人物的 `id`、`tmdb_id`、`name`、`profile_path` 信息，`profile_path` 使用 `w185` 尺寸。

---

### 需求 6：电视剧季列表

**用户故事：** 作为管理员，我希望能够查看某部电视剧的所有季，以便了解该剧的季度结构。

#### 验收标准

1. THE TV_Season_API SHALL 提供 `GET /api/tv-seasons?tv_show_id=xxx` 接口，返回指定电视剧的分页季列表。
2. WHEN 请求时，`tv_show_id` 参数为必填项，IF 缺少 `tv_show_id`，THEN THE TV_Season_API SHALL 返回 `code: 422` 的参数验证错误。
3. THE TV_Season_API SHALL 在所有 `tv_seasons` 查询中强制携带 `tv_show_id` 条件（大表约束：100 万+ 条）。
4. WHEN 请求携带 `sort` 参数，THE TV_Season_API SHALL 仅允许 `season_number`、`air_date`、`vote_average`、`id` 作为合法排序字段。
5. THE TV_Season_API SHALL 支持 `page`（最大 1000）和 `per_page`（最大 100）分页参数。
6. THE TV_Season_API SHALL 在季列表响应中输出 `id`、`tv_show_id`、`tmdb_id`、`season_number`、`name`、`air_date`、`episode_count`、`vote_average`、`poster_path` 字段。
7. THE TV_Season_API SHALL 通过 `ImageHelper::url()` 将 `poster_path` 拼接为 `w342` 尺寸的完整 URL。

---

### 需求 7：电视剧季详情

**用户故事：** 作为管理员，我希望能够查看某一季的完整信息，以便了解该季的所有元数据。

#### 验收标准

1. THE TV_Season_API SHALL 提供 `GET /api/tv-seasons/{id}` 接口，返回指定季的完整详情。
2. WHEN 指定 `id` 的季存在，THE TV_Season_API SHALL 返回包含所有字段的详情响应。
3. IF 指定 `id` 的季不存在，THEN THE TV_Season_API SHALL 返回 `code: 404` 的错误响应。
4. THE TV_Season_API SHALL 在季详情响应中输出 `id`、`tv_show_id`、`tmdb_id`、`season_number`、`name`、`overview`、`air_date`、`episode_count`、`vote_average`、`poster_path` 字段。
5. THE TV_Season_API SHALL 通过 `ImageHelper::url()` 将 `poster_path` 拼接为 `w500` 尺寸的完整 URL。

---

### 需求 8：电视剧季图片

**用户故事：** 作为管理员，我希望能够查看某一季的图片，以便管理该季的视觉素材。

#### 验收标准

1. THE TV_Season_API SHALL 提供 `GET /api/tv-season-images?tv_season_id=xxx` 接口，返回指定季的分页图片列表。
2. WHEN 请求时，`tv_season_id` 参数为必填项，IF 缺少 `tv_season_id`，THEN THE TV_Season_API SHALL 返回 `code: 422` 的参数验证错误。
3. THE TV_Season_API SHALL 支持 `page`（最大 1000）和 `per_page`（最大 100）分页参数。
4. THE TV_Season_API SHALL 在季图片响应中输出 `id`、`tv_season_id`、`image_type`、`file_path`、`width`、`height`、`vote_average`、`vote_count` 字段。
5. THE TV_Season_API SHALL 通过 `ImageHelper::url()` 将 `file_path` 拼接为 `w342` 尺寸的完整 URL（季图片仅有 `poster` 类型）。

---

### 需求 9：电视剧集列表

**用户故事：** 作为管理员，我希望能够查看某一季的所有集，以便了解该季的集数结构。

#### 验收标准

1. THE TV_Episode_API SHALL 提供 `GET /api/tv-episodes?tv_season_id=xxx` 接口，返回指定季的分页集列表。
2. WHEN 请求时，`tv_season_id` 参数为必填项，IF 缺少 `tv_season_id`，THEN THE TV_Episode_API SHALL 返回 `code: 422` 的参数验证错误。
3. THE TV_Episode_API SHALL 在所有 `tv_episodes` 查询中强制携带 `tv_season_id` 条件（大表约束：2000 万+ 条）。
4. WHEN 请求携带 `sort` 参数，THE TV_Episode_API SHALL 仅允许 `episode_number`、`air_date`、`vote_average`、`id` 作为合法排序字段。
5. THE TV_Episode_API SHALL 支持 `page`（最大 1000）和 `per_page`（最大 100）分页参数。
6. THE TV_Episode_API SHALL 在集列表响应中输出 `id`、`tv_show_id`、`tv_season_id`、`tmdb_id`、`season_number`、`episode_number`、`episode_type`、`name`、`air_date`、`runtime`、`vote_average`、`vote_count`、`still_path` 字段。
7. THE TV_Episode_API SHALL 通过 `ImageHelper::url()` 将 `still_path` 拼接为 `w300` 尺寸的完整 URL。

---

### 需求 10：电视剧集详情

**用户故事：** 作为管理员，我希望能够查看某一集的完整信息，以便了解该集的所有元数据。

#### 验收标准

1. THE TV_Episode_API SHALL 提供 `GET /api/tv-episodes/{id}` 接口，返回指定集的完整详情。
2. WHEN 指定 `id` 的集存在，THE TV_Episode_API SHALL 返回包含所有字段的详情响应。
3. IF 指定 `id` 的集不存在，THEN THE TV_Episode_API SHALL 返回 `code: 404` 的错误响应。
4. THE TV_Episode_API SHALL 在集详情响应中输出 `id`、`tv_show_id`、`tv_season_id`、`tmdb_id`、`season_number`、`episode_number`、`episode_type`、`production_code`、`name`、`overview`、`air_date`、`runtime`、`vote_average`、`vote_count`、`still_path` 字段。
5. THE TV_Episode_API SHALL 通过 `ImageHelper::url()` 将 `still_path` 拼接为 `w780` 尺寸的完整 URL。

---

### 需求 11：电视剧集演职人员

**用户故事：** 作为管理员，我希望能够查看某一集的演职人员列表，以便了解该集的参演和制作人员。

#### 验收标准

1. THE TV_Episode_API SHALL 提供 `GET /api/tv-episode-credits?tv_episode_id=xxx` 接口，返回指定集的分页演职人员列表。
2. WHEN 请求时，`tv_episode_id` 参数为必填项，IF 缺少 `tv_episode_id`，THEN THE TV_Episode_API SHALL 返回 `code: 422` 的参数验证错误。
3. THE TV_Episode_API SHALL 在所有 `tv_episode_credits` 查询中强制携带 `tv_episode_id` 条件（大表约束：极大）。
4. WHEN 请求携带 `credit_type` 参数，THE TV_Episode_API SHALL 仅允许 `cast` 或 `crew` 作为合法值，其他值返回 422。
5. THE TV_Episode_API SHALL 支持 `page`（最大 1000）和 `per_page`（最大 100）分页参数。
6. THE TV_Episode_API SHALL 在演职人员响应中输出 `id`、`tv_episode_id`、`person_tmdb_id`、`person_id`、`credit_type`、`character`、`cast_order`、`department_id`、`job_id`、`person` 字段。
7. WHILE `person_id` 为 NULL（异步关联尚未完成），THE TV_Episode_API SHALL 将 `person` 字段输出为 `null`，不报错，不过滤该条记录。
8. WHILE `person_id` 不为 NULL，THE TV_Episode_API SHALL 在 `person` 字段中输出关联人物的 `id`、`tmdb_id`、`name`、`profile_path` 信息，`profile_path` 使用 `w185` 尺寸。

---

### 需求 12：电视剧集图片

**用户故事：** 作为管理员，我希望能够查看某一集的图片，以便管理该集的视觉素材。

#### 验收标准

1. THE TV_Episode_API SHALL 提供 `GET /api/tv-episode-images?tv_episode_id=xxx` 接口，返回指定集的分页图片列表。
2. WHEN 请求时，`tv_episode_id` 参数为必填项，IF 缺少 `tv_episode_id`，THEN THE TV_Episode_API SHALL 返回 `code: 422` 的参数验证错误。
3. THE TV_Episode_API SHALL 支持 `page`（最大 1000）和 `per_page`（最大 100）分页参数。
4. THE TV_Episode_API SHALL 在集图片响应中输出 `id`、`tv_episode_id`、`image_type`、`file_path`、`width`、`height`、`vote_average`、`vote_count` 字段。
5. THE TV_Episode_API SHALL 通过 `ImageHelper::url()` 将 `file_path` 拼接为 `w300` 尺寸的完整 URL（集图片仅有 `still` 类型）。

---

### 需求 13：认证与安全

**用户故事：** 作为系统管理员，我希望所有 TV Show 相关接口都受到认证保护，以防止未授权访问。

#### 验收标准

1. THE TV_Show_API SHALL 要求所有接口（包括 tv-shows、tv-seasons、tv-episodes 及其所有子资源接口）携带有效的 `auth:api` JWT token。
2. IF 任意接口收到无效或缺失的 token，THEN THE TV_Show_API SHALL 返回 `code: 401` 的错误响应。
3. THE TV_Show_API SHALL 对所有排序字段进行白名单校验，禁止将用户输入直接传入 `orderBy()`。
4. THE TV_Show_API SHALL 通过 FormRequest 验证所有请求参数，未声明的参数通过 `validated()` 自动过滤。
5. THE TV_Show_API SHALL 在 API Resource 中仅输出明确声明的字段，禁止全量输出数据库记录。
