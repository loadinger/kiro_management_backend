# 安全规范

## 认证与授权

### JWT Token 策略

- Token 有效期：60 分钟（`JWT_TTL=60`）
- 刷新窗口：2 周内可用 refresh token 换新 token（`JWT_REFRESH_TTL=20160`）
- 过期 token 请求返回 `code: 401`，前端统一跳转登录页
- Token 存储：前端存 localStorage 或 httpOnly Cookie，后端不存储 token 状态（无状态 JWT）
- 登出时调用 `auth('api')->logout()` 将 token 加入黑名单（需开启 JWT 黑名单，`JWT_BLACKLIST_ENABLED=true`）

### 路由认证规则

- **所有业务接口**必须加 `auth:api` middleware，无例外
- 仅以下接口无需认证：
  - `POST /api/auth/login`
- 新增路由时默认放在 `auth:api` 组内，需要公开时显式说明原因

### 密码安全

- 密码存储使用 Laravel 默认 `bcrypt`，`BCRYPT_ROUNDS=12`
- 禁止在日志、响应、异常信息中输出密码明文
- 密码字段在 User Model 的 `$hidden` 中声明，禁止出现在任何 API 响应

---

## 输入安全

### SQL 注入防护

- 所有查询必须通过 Eloquent ORM 或 Laravel Query Builder 的参数绑定
- 禁止拼接原始 SQL 字符串，如需原始查询使用 `DB::select('... WHERE id = ?', [$id])`
- 禁止将用户输入直接传入 `orderBy()`、`groupBy()` 等方法，排序字段必须白名单校验

```php
// 禁止
$query->orderBy($request->sort_field);

// 正确
$allowedSorts = ['popularity', 'release_date', 'vote_average'];
$sortField = in_array($request->sort, $allowedSorts) ? $request->sort : 'popularity';
$query->orderBy($sortField);
```

### 参数验证

- 所有请求参数必须经过 FormRequest 验证，未声明的参数通过 `$request->validated()` 自动过滤
- 整数参数必须验证范围（如 `per_page` 最大 100，`page` 最大 1000）
- 枚举类型参数使用 `Rule::in()` 或 `Rule::enum()` 验证，禁止直接传入数据库

### 响应数据过滤

- 只读实体的 Resource 只输出明确声明的字段，禁止使用 `$this->resource->toArray()` 全量输出
- 以下字段禁止出现在任何 API 响应中：
  - `password`、`remember_token`（users 表）
  - 任何 `_token` 后缀字段

---

## CORS 配置

前端为独立 React 应用，需配置 CORS：

允许的 Origin：
- 开发环境：`http://localhost:5173`（Vite dev server）
- 生产环境：配置在 `config/cors.php` 的 `allowed_origins`，不使用通配符 `*`

```php
// config/cors.php
'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173')),
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
'exposed_headers' => [],
'max_age' => 86400,
'supports_credentials' => false,
```

`.env` 中配置：
```
CORS_ALLOWED_ORIGINS=http://localhost:5173
```

---

## 敏感信息保护

### 环境变量

- `.env` 文件禁止提交到 Git（已在 `.gitignore` 中）
- 生产环境密钥（`APP_KEY`、`JWT_SECRET`、数据库密码）通过环境变量注入，不写入代码
- `.env.example` 只包含键名和示例值，不包含真实密钥

### 日志安全

- 禁止在日志中输出：密码、JWT token、数据库连接字符串、用户隐私信息
- 生产环境 `LOG_LEVEL=warning`，不输出 debug 信息
- 异常堆栈信息只在 `APP_DEBUG=true`（开发环境）时对外暴露，生产环境返回通用错误信息

### API 响应安全

- 生产环境（`APP_ENV=production`）异常响应不暴露堆栈信息
- 500 错误统一返回 `{"code": 500, "message": "服务器内部错误", "data": null}`
- 不在错误响应中暴露数据库表名、字段名、文件路径等内部信息

---

## 请求频率限制

管理后台并发低，暂不做严格限流，但以下接口加基础保护：

- `POST /api/auth/login`：限制 10 次/分钟/IP，防止暴力破解

在 `routes/api.php` 中配置：
```php
Route::middleware('throttle:10,1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
});
```

---

## 安全检查清单

新增接口时必须确认：

- [ ] 路由已加 `auth:api` middleware
- [ ] 排序/筛选字段有白名单校验
- [ ] 响应 Resource 未输出敏感字段
- [ ] 参数验证覆盖所有用户输入
- [ ] 无原始 SQL 字符串拼接
