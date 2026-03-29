# 设计文档：认证 API（auth）

## 概述

基于已有的项目骨架（`AuthController`、`BaseController`、JWT guard 配置）进行完善，补充 Seeder、限流配置和完整测试覆盖。

---

## 涉及文件清单

### 新增
- `database/seeders/AdminSeeder.php` — 初始管理员账号
- `app/Http/Requests/LoginRequest.php` — 登录参数验证
- `app/Http/Resources/UserResource.php` — 用户信息序列化
- `tests/Feature/Auth/AuthTest.php` — 认证接口测试

### 修改
- `app/Http/Controllers/Api/AuthController.php` — 使用 LoginRequest、UserResource
- `routes/api.php` — 登录接口加限流 middleware
- `database/seeders/DatabaseSeeder.php` — 注册 AdminSeeder
- `.env` / `.env.example` — 新增 `ADMIN_EMAIL`、`ADMIN_PASSWORD`

### 已有（无需修改）
- `app/Models/User.php` — 已实现 JWTSubject
- `config/auth.php` — 已配置 `api` guard
- `config/jwt.php` — 已发布
- `database/migrations/0001_01_01_000000_create_users_table.php` — Laravel 默认已有

---

## 数据流

```
POST /api/auth/login
  └── throttle:10,1
        └── LoginRequest（验证 email/password）
              └── AuthController::login()
                    └── auth('api')->attempt()
                          └── 成功 → tokenPayload() → success()
                          └── 失败 → error(401)

POST /api/auth/logout
  └── auth:api
        └── AuthController::logout()
              └── JWTGuard::logout()（加入黑名单）
                    └── success(null, '已退出登录')

POST /api/auth/refresh
  └── auth:api
        └── AuthController::refresh()
              └── JWTGuard::refresh()（旧 token 失效，返回新 token）
                    └── tokenPayload() → success()

GET /api/auth/me
  └── auth:api
        └── AuthController::me()
              └── auth('api')->user()
                    └── UserResource → success()
```

---

## 各组件设计

### LoginRequest

```php
rules(): [
    'email'    => ['required', 'email'],
    'password' => ['required', 'string', 'min:1'],
]
```

### UserResource

输出字段：`id`、`name`、`email`、`created_at`

明确排除：`password`、`remember_token`（不在 `toArray()` 中声明即可）

### AdminSeeder

- 从 `env('ADMIN_EMAIL')` 和 `env('ADMIN_PASSWORD')` 读取
- 使用 `User::firstOrCreate(['email' => $email], [...])` 保证幂等
- 密码使用 `bcrypt()`，rounds 由 Laravel 从 `BCRYPT_ROUNDS` 自动读取

### 限流配置

在 `routes/api.php` 中，登录路由单独包裹：

```php
Route::middleware('throttle:10,1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
});
```

其余 auth 路由保持在 `auth:api` middleware 组内。

### JWT 黑名单

`config/jwt.php` 中确认以下配置：
- `blacklist_enabled` = `true`（登出时 token 加入黑名单）
- `blacklist_grace_period` = `0`（无宽限期）

---

## 路由结构

```
POST /api/auth/login     # throttle:10,1，无需认证
POST /api/auth/logout    # auth:api
POST /api/auth/refresh   # auth:api
GET  /api/auth/me        # auth:api
```

---

## 错误处理

| 场景 | code | message |
|------|------|---------|
| email/password 缺失或格式错误 | 422 | FormRequest 自动返回首条错误信息 |
| 邮箱或密码不匹配 | 401 | 邮箱或密码错误 |
| Token 缺失/无效/已登出 | 401 | 未认证，请先登录 |
| 超出限流 | HTTP 429 | （Laravel 默认） |

---

## 测试设计

文件：`tests/Feature/Auth/AuthTest.php`，使用 `RefreshDatabase`。

| 测试方法 | 验证点 |
|---------|--------|
| `test_login_with_valid_credentials` | 返回 code:0，含 access_token / token_type / expires_in |
| `test_login_with_wrong_password` | 返回 code:401 |
| `test_login_with_missing_fields` | 返回 code:422 |
| `test_login_with_invalid_email_format` | 返回 code:422 |
| `test_logout_invalidates_token` | 登出后再请求返回 code:401 |
| `test_logout_without_token` | 返回 code:401 |
| `test_refresh_returns_new_token` | 返回新 access_token，code:0 |
| `test_refresh_without_token` | 返回 code:401 |
| `test_me_returns_user_info` | 返回 id/name/email/created_at，无 password |
| `test_me_without_token` | 返回 code:401 |
