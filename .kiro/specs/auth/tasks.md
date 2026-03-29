# 实现计划：认证 API（auth）

## 任务列表

- [x] 1. 配置 JWT 黑名单
  - 在 `config/jwt.php` 中确认 `blacklist_enabled = true`，`blacklist_grace_period = 0`
  - 在 `.env` 和 `.env.example` 中补充 `JWT_BLACKLIST_ENABLED=true`
  - _需求：3.1、3.2_

- [x] 2. 补充环境变量
  - 在 `.env` 和 `.env.example` 中新增 `ADMIN_EMAIL` 和 `ADMIN_PASSWORD` 占位配置
  - _需求：1.2_

- [x] 3. 创建 AdminSeeder
  - 创建 `database/seeders/AdminSeeder.php`
  - 从 `env('ADMIN_EMAIL')` / `env('ADMIN_PASSWORD')` 读取账号信息
  - 使用 `User::firstOrCreate()` 保证幂等，账号已存在时跳过
  - 在 `database/seeders/DatabaseSeeder.php` 中调用 `AdminSeeder`
  - _需求：1.2、1.3_

- [x] 4. 创建 LoginRequest
  - 创建 `app/Http/Requests/LoginRequest.php`
  - 验证规则：`email`（required、email），`password`（required、string）
  - 提供中文 `messages()`
  - _需求：2.2、2.3_

- [x] 5. 创建 UserResource
  - 创建 `app/Http/Resources/UserResource.php`
  - 只输出 `id`、`name`、`email`、`created_at`，不输出 `password`、`remember_token`
  - _需求：5.1、5.2_

- [x] 6. 更新 AuthController
  - 登录方法改用 `LoginRequest` 替代手动 `validate()`
  - `me()` 方法返回值改用 `UserResource` 包装
  - _需求：2.1、2.2、5.1_

- [x] 7. 更新路由配置
  - 登录路由加 `throttle:10,1` middleware
  - 确认 logout / refresh / me 在 `auth:api` middleware 组内
  - _需求：2.5、3.3、4.2、5.3_

- [x] 8. 编写 Feature Test
  - 创建 `tests/Feature/Auth/AuthTest.php`，使用 `RefreshDatabase`
  - 覆盖以下场景：
    - 正确凭据登录返回 token（需求 2.1）
    - 密码错误返回 401（需求 2.4）
    - 缺少字段返回 422（需求 2.2）
    - 邮箱格式错误返回 422（需求 2.3）
    - 登出后 token 失效返回 401（需求 3.1、3.2）
    - 未携带 token 登出返回 401（需求 3.3）
    - 刷新返回新 token（需求 4.1）
    - 未携带 token 刷新返回 401（需求 4.2）
    - me 接口返回正确字段且无敏感信息（需求 5.1、5.2）
    - 未携带 token 访问 me 返回 401（需求 5.3）
  - _需求：2.1–2.4、3.1–3.3、4.1–4.2、5.1–5.3_

- [x] 9. 运行测试，确认全部通过
  - 执行 `php artisan test tests/Feature/Auth/AuthTest.php`
  - 确认无报错后提交
