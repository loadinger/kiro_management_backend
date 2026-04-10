# 需求文档

## 简介

为 Filmly Management Backend 新增 Collections（合集）API 模块。合集数据来源于 TMDB，由采集项目写入 `collections` 表和 `collection_movies` 表，本模块只负责只读查询输出。

合集是将多部相关电影归组的概念（如"复仇者联盟系列"），每个合集包含若干电影。`collection_movies` 表采用异步关联模式，`movie_id` 初始为 NULL，由 reconcile 步骤补填，API 层需做 null 安全处理。

本模块提供两个接口：合集分页列表和合集详情（含关联电影列表）。

---

## 词汇表

- **Collection**：合集，将多部相关电影归组的实体，对应 `collections` 表
- **CollectionMovie**：合集电影关联记录，对应 `collection_movies` 表，含异步关联字段 `movie_id`
- **Collection_API**：本模块提供的 HTTP API 服务
- **ImageHelper**：图片 URL 拼接工具类，`ImageHelper::url($path, $size)` 将相对路径转为完整 TMDB 图片 URL
- **信封格式**：统一响应结构 `{code, message, data}`，code 为 0 表示成功
- **异步关联**：`collection_movies.movie_id` 初始为 NULL，由采集项目 reconcile 步骤批量补填，API 层不触发写入

---

## 需求

### 需求 1：合集分页列表接口

**用户故事：** 作为管理后台用户，我希望能分页浏览所有合集，并支持按名称搜索，以便快速定位目标合集。

#### 验收标准

1. THE Collection_API SHALL 提供 `GET /api/collections` 接口，返回合集分页列表。
2. WHEN 请求携带有效的 `auth:api` Bearer Token，THE Collection_API SHALL 返回 `code: 0` 的成功响应。
3. IF 请求未携带 Token 或 Token 无效，THEN THE Collection_API SHALL 返回 `code: 401` 的错误响应。
4. WHEN 请求包含 `q` 参数，THE Collection_API SHALL 对 `collections.name` 字段执行模糊匹配（`LIKE %q%`）过滤结果。
5. THE Collection_API SHALL 支持 `page` 参数（整数，最小值 1，最大值 1000，默认 1）控制页码。
6. THE Collection_API SHALL 支持 `per_page` 参数（整数，最小值 1，最大值 100，默认 20）控制每页条数。
7. IF `page` 参数超过 1000，THEN THE Collection_API SHALL 返回 `code: 422` 的参数验证错误响应。
8. IF `per_page` 参数超过 100，THEN THE Collection_API SHALL 返回 `code: 422` 的参数验证错误响应。
9. THE Collection_API SHALL 在列表响应的 `data.list` 中，每条记录包含以下字段：`id`、`tmdb_id`、`name`、`poster_url`、`backdrop_url`。
10. THE Collection_API SHALL 使用 `ImageHelper::url($poster_path, 'w342')` 拼接列表中的 `poster_url`，路径为 NULL 时输出 `null`。
11. THE Collection_API SHALL 使用 `ImageHelper::url($backdrop_path, 'w780')` 拼接列表中的 `backdrop_url`，路径为 NULL 时输出 `null`。
12. THE Collection_API SHALL 在响应的 `data.pagination` 中包含 `total`、`page`、`per_page`、`last_page` 字段。
13. THE Collection_API SHALL 默认按 `id` 升序排列列表结果。

---

### 需求 2：合集详情接口

**用户故事：** 作为管理后台用户，我希望能查看单个合集的完整信息及其包含的电影列表，以便了解合集的详细内容。

#### 验收标准

1. THE Collection_API SHALL 提供 `GET /api/collections/{id}` 接口，返回指定合集的详情。
2. WHEN 请求携带有效的 `auth:api` Bearer Token，THE Collection_API SHALL 返回 `code: 0` 的成功响应。
3. IF 请求未携带 Token 或 Token 无效，THEN THE Collection_API SHALL 返回 `code: 401` 的错误响应。
4. IF 指定 `id` 对应的合集记录不存在，THEN THE Collection_API SHALL 返回 `code: 404` 的错误响应。
5. THE Collection_API SHALL 在详情响应的 `data` 中包含以下字段：`id`、`tmdb_id`、`name`、`overview`、`poster_url`、`backdrop_url`、`movies`。
6. THE Collection_API SHALL 使用 `ImageHelper::url($poster_path, 'w500')` 拼接详情中的 `poster_url`，路径为 NULL 时输出 `null`。
7. THE Collection_API SHALL 使用 `ImageHelper::url($backdrop_path, 'original')` 拼接详情中的 `backdrop_url`，路径为 NULL 时输出 `null`。
8. THE Collection_API SHALL 在 `data.movies` 数组中返回该合集关联的所有电影记录，每条记录包含：`movie_tmdb_id`、`movie_id`、`resolved`。
9. WHILE `collection_movies.movie_id` 为 NULL，THE Collection_API SHALL 在对应电影记录中将 `resolved` 字段输出为 `false`，`movie_id` 输出为 `null`，不报错。
10. WHILE `collection_movies.movie_id` 不为 NULL，THE Collection_API SHALL 在对应电影记录中将 `resolved` 字段输出为 `true`，`movie_id` 输出为实际值。
11. THE Collection_API SHALL 禁止在 Resource 层触发额外的数据库查询，关联数据由 Service 层预加载。

---

### 需求 3：接口安全与参数验证

**用户故事：** 作为系统管理员，我希望所有合集接口都有完整的认证保护和参数验证，以确保数据安全和接口健壮性。

#### 验收标准

1. THE Collection_API SHALL 将所有路由注册在 `auth:api` middleware 组内。
2. WHEN `q` 参数存在，THE Collection_API SHALL 限制 `q` 参数长度不超过 100 个字符。
3. IF `page` 或 `per_page` 参数为非整数类型，THEN THE Collection_API SHALL 返回 `code: 422` 的参数验证错误响应。
4. THE Collection_API SHALL 对排序方向参数 `order` 仅接受 `asc` 或 `desc` 两个值，其他值返回 `code: 422`。
5. THE Collection_API SHALL 在 FormRequest 的 `messages()` 方法中提供中文错误提示信息。
