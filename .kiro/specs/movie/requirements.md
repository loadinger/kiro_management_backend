# 需求文档：Movie 模块 API

## 简介

本模块为 Filmly Management Backend 提供电影（Movie）相关的只读 API 接口。数据来源于 `movies` 表及其关联表，由独立采集项目从 TMDB 写入，本项目只负责查询输出。

模块包含以下接口：
- 电影列表（支持筛选、排序、搜索、分页）
- 电影详情
- 电影演职人员列表（`movie_credits`）
- 电影图片列表（`movie_images`）
- 电影类型列表（`movie_genres`）
- 电影关键词列表（`movie_keywords`）
- 电影制作公司列表（`movie_production_companies`）

所有接口均需 `auth:api` 认证，遵循项目信封格式响应规范。

---

## 词汇表

- **Movie**：电影实体，对应 `movies` 表，数据量 100 万+，只读
- **MovieCredit**：电影演职人员记录，对应 `movie_credits` 表，含 `cast`（演员）和 `crew`（幕后）两类，`person_id` 存在异步关联
- **MovieImage**：电影图片记录，对应 `movie_images` 表，`image_type` 为 `poster` / `backdrop` / `logo`
- **MovieGenre**：电影与类型的关联，对应 `movie_genres` 表
- **MovieKeyword**：电影与关键词的关联，对应 `movie_keywords` 表
- **MovieProductionCompany**：电影与制作公司的关联，对应 `movie_production_companies` 表
- **MovieService**：电影业务逻辑层
- **MovieRepository**：电影数据访问层
- **ImageHelper**：图片 URL 拼接工具类，将 TMDB 相对路径转换为完整 URL
- **CreditType**：演职人员类型枚举，值为 `cast` 或 `crew`
- **大表约束**：`movies` 表数据量 100 万+，`page` 参数最大值为 1000，防止深翻页慢查询

---

## 需求

### 需求 1：电影列表接口

**用户故事：** 作为管理员，我希望获取电影分页列表，并支持按标题搜索、按类型/语言/状态筛选、按多种字段排序，以便快速定位目标电影。

#### 验收标准

1. THE **MovieService** SHALL 提供电影列表查询方法，返回 `LengthAwarePaginator`
2. WHEN 请求 `GET /api/movies`，THE **MovieController** SHALL 返回信封格式的分页响应，`data` 包含 `list` 和 `pagination` 节点
3. WHEN 请求未携带有效 JWT Token，THE **MovieController** SHALL 返回 `code: 401` 的错误响应
4. WHEN `page` 参数超过 1000，THE **MovieController** SHALL 返回 `code: 422` 的参数验证错误响应
5. WHEN `per_page` 参数超过 100，THE **MovieController** SHALL 返回 `code: 422` 的参数验证错误响应
6. WHEN 请求携带 `q` 参数，THE **MovieRepository** SHALL 对 `title` 和 `original_title` 字段执行前缀匹配查询（`LIKE q%`）
7. WHEN 请求携带 `genre_id` 参数（单个整数），THE **MovieRepository** SHALL 筛选包含指定类型的电影
8. WHEN 请求携带 `status` 参数，THE **MovieRepository** SHALL 筛选指定发行状态的电影
9. WHEN 请求携带 `release_year` 参数（四位整数年份），THE **MovieRepository** SHALL 筛选 `release_date` 在该年份内的电影
10. WHEN 请求携带 `adult` 参数（`0` 或 `1`），THE **MovieRepository** SHALL 筛选对应成人内容标记的电影
11. WHEN 请求携带 `sort` 参数，THE **MovieRepository** SHALL 按白名单字段（`popularity`、`release_date`、`vote_average`、`vote_count`、`id`）排序，默认按 `id` 降序
12. THE **MovieListResource** SHALL 输出字段：`id`、`tmdb_id`、`title`、`original_title`、`original_language`、`status`、`release_date`、`runtime`、`popularity`、`vote_average`、`vote_count`、`adult`、`poster_path`（完整 URL，size `w342`）、`backdrop_path`（完整 URL，size `w780`）

### 需求 2：电影详情接口

**用户故事：** 作为管理员，我希望查看单部电影的完整信息，以便了解电影的详细数据。

#### 验收标准

1. WHEN 请求 `GET /api/movies/{id}`，THE **MovieController** SHALL 返回信封格式的单条电影数据
2. WHEN 请求未携带有效 JWT Token，THE **MovieController** SHALL 返回 `code: 401` 的错误响应
3. WHEN 指定 `id` 的电影不存在，THE **MovieService** SHALL 抛出 `AppException`，THE **MovieController** SHALL 返回 `code: 404` 的错误响应
4. THE **MovieResource** SHALL 输出字段：`id`、`tmdb_id`、`imdb_id`、`title`、`original_title`、`original_language`、`overview`、`tagline`、`status`、`release_date`、`runtime`、`budget`、`revenue`、`popularity`、`vote_average`、`vote_count`、`adult`、`video`、`poster_path`（完整 URL，size `w500`）、`backdrop_path`（完整 URL，size `original`）、`homepage`、`spoken_language_codes`、`production_country_codes`、`created_at`、`updated_at`

### 需求 3：电影演职人员列表接口

**用户故事：** 作为管理员，我希望查看指定电影的演职人员列表，以便了解电影的演员和幕后团队。

#### 验收标准

1. WHEN 请求 `GET /api/movie-credits?movie_id={id}`，THE **MovieCreditController** SHALL 返回信封格式的分页演职人员列表
2. WHEN 请求未携带有效 JWT Token，THE **MovieCreditController** SHALL 返回 `code: 401` 的错误响应
3. WHEN `movie_id` 参数缺失，THE **MovieCreditController** SHALL 返回 `code: 422` 的参数验证错误响应
4. WHEN 请求携带 `credit_type` 参数（`cast` 或 `crew`），THE **MovieCreditRepository** SHALL 筛选指定类型的演职人员记录
5. WHEN `movie_credits` 记录的 `person_id` 为 NULL，THE **MovieCreditResource** SHALL 输出 `person` 字段为 `null`，不报错，不过滤该条记录
6. THE **MovieCreditResource** SHALL 输出字段：`id`、`movie_id`、`person_tmdb_id`、`person_id`、`credit_type`、`character`、`cast_order`、`department_id`、`job_id`，以及 `person`（当 `person_id` 非 NULL 时输出关联人物的 `id`、`tmdb_id`、`name`、`profile_path`（完整 URL，size `w185`））

### 需求 4：电影图片列表接口

**用户故事：** 作为管理员，我希望查看指定电影的所有图片，以便管理电影的视觉素材。

#### 验收标准

1. WHEN 请求 `GET /api/movie-images?movie_id={id}`，THE **MovieImageController** SHALL 返回信封格式的分页图片列表
2. WHEN 请求未携带有效 JWT Token，THE **MovieImageController** SHALL 返回 `code: 401` 的错误响应
3. WHEN `movie_id` 参数缺失，THE **MovieImageController** SHALL 返回 `code: 422` 的参数验证错误响应
4. WHEN 请求携带 `image_type` 参数（`poster`、`backdrop` 或 `logo`），THE **MovieImageRepository** SHALL 筛选指定类型的图片记录
5. THE **MovieImageResource** SHALL 输出字段：`id`、`movie_id`、`image_type`、`file_path`（完整 URL，poster/logo 使用 size `w342`，backdrop 使用 size `w780`）、`width`、`height`、`vote_average`、`vote_count`

### 需求 5：电影类型列表接口

**用户故事：** 作为管理员，我希望查看指定电影关联的所有类型，以便了解电影的分类信息。

#### 验收标准

1. WHEN 请求 `GET /api/movie-genres?movie_id={id}`，THE **MovieGenreController** SHALL 返回信封格式的类型列表（不分页）
2. WHEN 请求未携带有效 JWT Token，THE **MovieGenreController** SHALL 返回 `code: 401` 的错误响应
3. WHEN `movie_id` 参数缺失，THE **MovieGenreController** SHALL 返回 `code: 422` 的参数验证错误响应
4. THE **MovieGenreResource** SHALL 输出字段：`id`、`tmdb_id`、`name`、`type`

### 需求 6：电影关键词列表接口

**用户故事：** 作为管理员，我希望查看指定电影关联的所有关键词，以便了解电影的标签信息。

#### 验收标准

1. WHEN 请求 `GET /api/movie-keywords?movie_id={id}`，THE **MovieKeywordController** SHALL 返回信封格式的关键词列表（不分页）
2. WHEN 请求未携带有效 JWT Token，THE **MovieKeywordController** SHALL 返回 `code: 401` 的错误响应
3. WHEN `movie_id` 参数缺失，THE **MovieKeywordController** SHALL 返回 `code: 422` 的参数验证错误响应
4. THE **MovieKeywordResource** SHALL 输出字段：`id`、`tmdb_id`、`name`

### 需求 7：电影制作公司列表接口

**用户故事：** 作为管理员，我希望查看指定电影关联的所有制作公司，以便了解电影的出品方信息。

#### 验收标准

1. WHEN 请求 `GET /api/movie-production-companies?movie_id={id}`，THE **MovieProductionCompanyController** SHALL 返回信封格式的制作公司列表（不分页）
2. WHEN 请求未携带有效 JWT Token，THE **MovieProductionCompanyController** SHALL 返回 `code: 401` 的错误响应
3. WHEN `movie_id` 参数缺失，THE **MovieProductionCompanyController** SHALL 返回 `code: 422` 的参数验证错误响应
4. THE **MovieProductionCompanyResource** SHALL 输出字段：`id`、`tmdb_id`、`name`、`origin_country`、`logo_path`（完整 URL，size `w185`）
