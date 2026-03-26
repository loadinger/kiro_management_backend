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
    "items": [...],
    "meta": {
      "total": 1000000,
      "page": 1,
      "per_page": 20,
      "last_page": 50000
    }
  }
}
```

### 错误响应

```json
{
  "code": 422,
  "message": "参数错误：title 不能为空",
  "data": null
}
```

### 常用 code 值

| code | 含义 |
|------|------|
| 0 | 成功 |
| 401 | 未认证 / Token 无效 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 422 | 参数验证失败 |
| 500 | 服务器内部错误 |

## 分页规范

- 参数：`page`（默认 1）、`per_page`（默认 20，最大 100）
- 大表（movies、persons、tv_episodes 等）限制 `page` 最大 1000，超出返回 422
- 响应中必须包含 `meta.total`、`meta.page`、`meta.per_page`、`meta.last_page`

## 路由命名规范

```
GET    /api/movies              # 列表
GET    /api/movies/{id}         # 详情
POST   /api/movies              # 创建（仅 CRUD 资源）
PUT    /api/movies/{id}         # 更新（仅 CRUD 资源）
DELETE /api/movies/{id}         # 删除（仅 CRUD 资源）
```

- 资源名用复数、小写、连字符：`tv-shows`、`tv-seasons`
- 版本前缀暂不加，后续有需要再引入 `/api/v2/`

## 认证

- 登录：`POST /api/auth/login`，返回 JWT token
- 刷新：`POST /api/auth/refresh`
- 登出：`POST /api/auth/logout`
- 受保护路由统一加 `auth:api` middleware
- 请求头：`Authorization: Bearer {token}`

## 图片 URL

数据库中存储的是 TMDB 相对路径（如 `/abc123.jpg`），API 输出时由 Resource 层统一拼接完整 URL：

```
https://image.tmdb.org/t/p/{size}{path}
```

size 常用值：`w185`、`w342`、`w500`、`original`

Resource 中统一处理，Controller 不做拼接。

## 命名规范

- Controller 方法：`index`、`show`、`store`、`update`、`destroy`
- Service 方法：动词开头，如 `getMovieList`、`findMovieById`
- Repository 方法：`findById`、`paginate`、`findByTmdbId`
- FormRequest 类名：`{Action}{Resource}Request`，如 `ListMovieRequest`、`StoreArticleRequest`
- Resource 类名：`{Resource}Resource`、`{Resource}Collection`
