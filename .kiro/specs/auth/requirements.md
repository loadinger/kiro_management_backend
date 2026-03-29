# 需求文档：认证 API（auth）

## 简介

本功能为 Filmly 管理后台实现完整的 JWT 认证流程，包括管理员账号的数据库初始化、登录、登出、Token 刷新和当前用户信息查询。

认证基于 `tymon/jwt-auth` 实现，所有业务接口均需有效 JWT Token 才能访问。本 Spec 同时覆盖 users 表的 migration 执行和初始管理员账号的 Seeder。

---

## 词汇表

- **Auth_API**：本功能涉及的所有认证接口的统称
- **User**：管理员账号，对应 `users` 表
- **JWT**：JSON Web Token，项目使用 `tymon/jwt-auth` 实现的无状态认证机制
- **access_token**：登录或刷新后返回的 Bearer Token
- **信封格式**：统一响应结构 `{ code, message, data }`
- **黑名单**：登出时 JWT 将 token 加入黑名单，防止已登出 token 继续使用

---

## 需求列表

### 需求 1：数据库初始化

**用户故事：** 作为系统管理员，我希望项目能通过 migration 创建 users 表，并通过 Seeder 创建初始管理员账号，以便首次部署后可以直接登录。

#### 验收标准

1. WHEN 运行 `php artisan migrate` 时，THE Auth_API SHALL 成功创建 `users` 表，包含 `id`、`name`、`email`（唯一）、`password`、`remember_token`、`timestamps` 字段
2. WHEN 运行 `php artisan db:seed` 时，THE Auth_API SHALL 创建一个初始管理员账号，email 和密码从 `.env` 中读取（`ADMIN_EMAIL` / `ADMIN_PASSWORD`），若账号已存在则跳过（幂等）
3. THE Auth_API SHALL 使用 `bcrypt` 存储密码，`BCRYPT_ROUNDS` 从环境变量读取，默认 12

---

### 需求 2：登录

**用户故事：** 作为系统管理员，我希望通过邮箱和密码登录，获取 JWT Token，以便访问受保护的接口。

#### 验收标准

1. WHEN 发送 `POST /api/auth/login` 请求，携带正确的 `email` 和 `password` 时，THE Auth_API SHALL 返回包含 `access_token`、`token_type: "bearer"`、`expires_in`（秒）的响应，`code` 为 0
2. WHEN `email` 或 `password` 字段缺失时，THE Auth_API SHALL 返回 `code: 422` 的参数验证错误
3. WHEN `email` 格式不合法时，THE Auth_API SHALL 返回 `code: 422` 的参数验证错误
4. WHEN `email` 或 `password` 不匹配时，THE Auth_API SHALL 返回 `code: 401`、`message: "邮箱或密码错误"` 的响应
5. THE Auth_API SHALL 对 `POST /api/auth/login` 接口限制每 IP 每分钟最多 10 次请求，超出时返回 HTTP 429

---

### 需求 3：登出

**用户故事：** 作为系统管理员，我希望能主动登出，使当前 Token 失效，以确保账号安全。

#### 验收标准

1. WHEN 发送 `POST /api/auth/logout` 请求，携带有效 JWT Token 时，THE Auth_API SHALL 将该 Token 加入黑名单并返回 `code: 0`、`message: "已退出登录"` 的响应
2. WHEN 使用已登出的 Token 访问受保护接口时，THE Auth_API SHALL 返回 `code: 401` 的响应
3. WHEN 发送 `POST /api/auth/logout` 请求时未携带 Token，THE Auth_API SHALL 返回 `code: 401` 的响应

---

### 需求 4：Token 刷新

**用户故事：** 作为系统管理员，我希望在 Token 过期前能刷新获取新 Token，以保持登录状态不中断。

#### 验收标准

1. WHEN 发送 `POST /api/auth/refresh` 请求，携带有效（未过期）JWT Token 时，THE Auth_API SHALL 返回新的 `access_token`，旧 Token 同时失效
2. WHEN 发送 `POST /api/auth/refresh` 请求时未携带 Token，THE Auth_API SHALL 返回 `code: 401` 的响应
3. WHEN 发送 `POST /api/auth/refresh` 请求，携带已过期超过刷新窗口（`JWT_REFRESH_TTL`）的 Token 时，THE Auth_API SHALL 返回 `code: 401` 的响应

---

### 需求 5：当前用户信息

**用户故事：** 作为系统管理员，我希望能查询当前登录账号的基本信息，以便前端展示用户名等信息。

#### 验收标准

1. WHEN 发送 `GET /api/auth/me` 请求，携带有效 JWT Token 时，THE Auth_API SHALL 返回当前用户的 `id`、`name`、`email`、`created_at` 字段，`code` 为 0
2. THE Auth_API SHALL 确保响应中不包含 `password`、`remember_token` 等敏感字段
3. WHEN 发送 `GET /api/auth/me` 请求时未携带 Token，THE Auth_API SHALL 返回 `code: 401` 的响应

---

### 需求 6：统一响应格式

**用户故事：** 作为前端开发者，我希望认证接口的响应格式与其他接口保持一致，以便统一处理。

#### 验收标准

1. THE Auth_API SHALL 对所有请求统一返回 HTTP 状态码 200（429 限流除外），业务状态通过响应体中的 `code` 字段区分
2. WHEN 请求成功时，THE Auth_API SHALL 返回包含 `code: 0`、`message: "success"` 和 `data` 字段的响应体
3. WHEN 参数验证失败时，THE Auth_API SHALL 返回包含 `code: 422` 和具体错误描述的响应体，`data` 为 null
4. WHEN Token 缺失或无效时，THE Auth_API SHALL 返回包含 `code: 401`、`message: "未认证，请先登录"` 的响应体，`data` 为 null
