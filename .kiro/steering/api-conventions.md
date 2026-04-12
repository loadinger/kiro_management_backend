# API 规范

## 响应格式（信封格式）

所有接口统一返回 HTTP 200，业务状态通过 `code` 字段区分。

### 成功响应

```json
{
  "code": 0,
  "message": "success",
  "data": { ... }
}
```

### 列表响应（带分页）

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [...],
    "pagination": {
      "total": 1000000,
      "page": 1,
      "per_page": 20,
      "last_page": 50000
    }
  }
}
```

### 全量列表响应（不分页）

适用于数据量小、前端需要一次性获取全部数据的场景（如 genres、languages、countries 等参考数据）。

路由约定：`GET /api/{resources}/all`

```json
{
  "code": 0,
  "message": "success",
  "data": [...]
}
```

- `data` 直接为数组，不包含 `list` 或 `pagination` 节点
- 仅限小表使用，禁止对 movies、persons 等大表提供 `/all` 接口
- Controller 调用 `BaseController::listing()`，Service 调用 `getAll()`，Repository 实现 `getAll(): Collection`
- 路由注册时 `/all` 必须放在 `/{id}` 之前，防止被当作详情路由匹配

### 错误响应

```json
{
  "code": 422,
  "message": "参数错误：title 不能为空",
  "data": null
}
```

### code 值定义

| code | 含义 | 触发场景 |
|------|------|---------|
| 0 | 成功 | 正常响应 |
| 401 | 未认证 | Token 缺失、无效、过期 |
| 403 | 无权限 | 已认证但无操作权限（暂未使用） |
| 404 | 资源不存在 | findById 返回 null |
| 422 | 参数验证失败 | FormRequest 验证不通过 |
| 500 | 服务器内部错误 | 未捕获异常 |

---

## 分页规范

### 请求参数

| 参数 | 类型 | 默认值 | 限制 | 说明 |
|------|------|--------|------|------|
| `page` | int | 1 | 最大 1000（大表） | 页码 |
| `per_page` | int | 20 | 最大 100 | 每页条数 |

大表（`movies`、`persons`、`tv_episodes` 等）`page` 超过 1000 时返回 422。

### 响应结构

```json
"data": {
  "list": [...],
  "pagination": {
    "total": 1000000,
    "page": 1,
    "per_page": 20,
    "last_page": 50000
  }
}
```

---

## 筛选与排序参数规范

### 筛选参数

- 参数名与字段名保持一致，使用小写下划线
- 多值筛选用逗号分隔字符串：`?genre_ids=1,2,3`
- 范围筛选用 `_min` / `_max` 后缀：`?release_date_min=2020-01-01&release_date_max=2023-12-31`
- 布尔筛选用 `0` / `1`：`?adult=0`

### 排序参数

- 参数名：`sort`（字段）+ `order`（方向，`asc` / `desc`，默认 `desc`）
- 排序字段必须白名单校验，禁止直接传入数据库
- 每个接口在 FormRequest 中声明允许的排序字段

```
GET /api/movies?sort=popularity&order=desc
GET /api/movies?sort=release_date&order=asc
```

---

## 路由规范

### RESTful 路由结构

```
GET    /api/{resources}          # 列表
GET    /api/{resources}/{id}     # 详情
POST   /api/{resources}          # 创建（仅 CRUD 资源）
PUT    /api/{resources}/{id}     # 全量更新（仅 CRUD 资源）
PATCH  /api/{resources}/{id}     # 部分更新（仅 CRUD 资源）
DELETE /api/{resources}/{id}     # 删除（仅 CRUD 资源）
```

### 子资源路由（独立路由 + 参数过滤）

子资源不使用嵌套路由，统一用独立路由 + 参数过滤：

```
# 电影相关子资源
GET /api/movie-credits?movie_id=123
GET /api/movie-images?movie_id=123
GET /api/movie-genres?movie_id=123
GET /api/movie-keywords?movie_id=123
GET /api/movie-production-companies?movie_id=123

# TV 相关子资源
GET /api/tv-seasons?tv_show_id=456
GET /api/tv-episodes?tv_season_id=789
GET /api/tv-show-creators?tv_show_id=456
GET /api/tv-show-images?tv_show_id=456
GET /api/tv-episode-credits?tv_episode_id=101
GET /api/tv-episode-images?tv_episode_id=101
```

父资源 ID 参数在 FormRequest 中为必填项，缺少时返回 422。

### 命名规范

- 资源名：复数、小写、连字符，如 `tv-shows`、`tv-seasons`、`production-companies`
- 路由参数：小写下划线，如 `{tv_show_id}`、`{season_number}`
- 版本前缀：暂不加，后续引入 `/api/v2/`

---

## 认证规范

- 登录：`POST /api/auth/login`（唯一无需认证的接口）
- 刷新：`POST /api/auth/refresh`
- 登出：`POST /api/auth/logout`
- 当前用户：`GET /api/auth/me`
- 所有业务接口加 `auth:api` middleware
- 请求头：`Authorization: Bearer {token}`
- Token 过期返回 `{"code": 401, "message": "未认证，请先登录", "data": null}`

---

## 图片路径规范

数据库存储 TMDB 相对路径（如 `/abc123.jpg`），API 输出时由 Resource 层统一通过 `ImageHelper::url()` 处理。

- 实现：`App\Helpers\ImageHelper::url(?string $path, string $size): ?string`
- 输出相对路径（如 `/abc123.jpg`），**不拼接域名和 size 前缀**，由前端自行维护 TMDB base URL
- `$size` 参数保留，用于标注推荐尺寸，便于前端按场景选择
- path 为 null 时返回 null
- 禁止在 Controller 层处理图片路径
- 图片字段命名保持与数据库一致（`poster_path`、`profile_path` 等），不使用 `_url` 后缀

各实体推荐 size 见 `.kiro/steering/data-flow.md`。

---

## 命名规范

| 类型 | 规范 | 示例 |
|------|------|------|
| Controller 方法 | RESTful 标准动词 | `index`、`show`、`store`、`update`、`destroy` |
| Service 方法 | 动词开头驼峰 | `getList`、`findById`、`findMovieById` |
| Repository 方法 | 语义化动词 | `findById`、`paginate`、`findByTmdbId`、`paginateWithFilters` |
| FormRequest 类 | `{Action}{Resource}Request` | `ListMovieRequest`、`StoreArticleRequest` |
| Resource 类 | `{Resource}Resource` | `MovieResource`、`TvShowResource` |

---

## 全局搜索规范

接口：`GET /api/search?q={keyword}`

- `q` 参数必填，最短 1 字符，最长 100 字符
- 并发查询 movies（title / original_title）、tv_shows（name / original_name）、persons（name）三张主表
- 每表最多返回 10 条，走 `LIKE keyword%` 前缀匹配
- 不分页，不支持排序
- 响应结构：

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "movies":   [...],
    "tv_shows": [...],
    "persons":  [...]
  }
}
```

---

- 日期字段（`date` 类型）：`Y-m-d`，如 `"release_date": "2023-07-12"`，Resource 层用 `$this->release_date?->format('Y-m-d')` 输出，禁止直接输出 Carbon 对象（会序列化为完整 datetime 字符串）
- 时间戳字段（`timestamp` 类型）：ISO 8601，不含微秒，如 `"created_at": "2023-07-12T10:30:00Z"`
- 时区：统一 UTC
- Resource 层时间戳统一用 `$this->created_at?->format('Y-m-d\TH:i:s\Z')` 输出，禁止直接输出 Carbon 对象（会带 `.000000Z` 微秒后缀）
