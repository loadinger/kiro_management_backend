# 开发工作流

## 本地环境启动

```bash
# 1. 安装依赖（首次或 composer.lock 变更后）
composer install

# 2. 复制环境配置
cp .env.example .env

# 3. 填入数据库连接信息（云端 MySQL，见团队共享配置）
# 编辑 .env 中的 DB_HOST / DB_DATABASE / DB_USERNAME / DB_PASSWORD

# 4. 生成 APP_KEY 和 JWT_SECRET（首次）
php artisan key:generate
php artisan jwt:secret

# 5. 配置 nginx（参考 nginx.conf.example）
# 将 server_name 指向 filmly-api.test，root 指向 public/ 目录
# /etc/hosts 添加：127.0.0.1 filmly-api.test

# 6. 确认 PHP-FPM 已启动
php-fpm -v
```

## 数据库 Migration 策略

**本项目不维护业务数据表的 migration。**

原因：movies / tv_shows / persons 等核心表由数据采集项目创建和维护，本项目直接连接同一数据库只读使用。

**本项目只维护以下表的 migration：**
- `users`（管理员账号，Laravel 默认已有）
- 未来新增的纯管理后台业务表（如专题文章）

运行 migration：
```bash
# 只跑本项目自己的 migration（不影响采集项目的表）
php artisan migrate
```

**禁止**对采集项目的表执行 `migrate:fresh` / `migrate:rollback`。

---

## 新增一个 API 模块的标准流程

以新增 `movies` 模块为例，严格按以下顺序执行：

### 1. Model
创建 `app/Models/Movie.php`
- 定义 `$fillable`（只读表可设为空数组）
- 定义 `$casts`（json、boolean、date 字段）
- 定义关联关系方法

### 2. Repository Interface + Implementation
创建 `app/Repositories/Contracts/MovieRepositoryInterface.php`
创建 `app/Repositories/MovieRepository.php`（继承 BaseRepository）
- 实现列表查询（带筛选、分页）
- 实现详情查询（带关联预加载）

### 3. 在 AppServiceProvider 注册绑定
```php
$this->app->bind(MovieRepositoryInterface::class, MovieRepository::class);
```

### 4. Service
创建 `app/Services/MovieService.php`
- 注入 Repository
- 实现业务方法，返回强类型

### 5. FormRequest
创建 `app/Http/Requests/ListMovieRequest.php`（列表参数验证）
创建 `app/Http/Requests/ShowMovieRequest.php`（详情参数验证，如有）

### 6. API Resource
创建 `app/Http/Resources/MovieResource.php`
- 定义输出字段
- 图片路径用 `ImageHelper::url()` 拼接
- 关联数据用 `$this->whenLoaded()` 按需输出

### 7. Controller
创建 `app/Http/Controllers/Api/MovieController.php`（继承 BaseController）
- 注入 Service
- 实现 `index` / `show` 方法

### 8. 注册路由
在 `routes/api.php` 的 `auth:api` middleware 组内添加路由

### 9. 代码格式化
```bash
./vendor/bin/pint
```

### 10. 提交
```bash
git add -A
git commit -m "feat: add movie list and detail API"
```

---

## 目录结构速查

```
app/
├── Exceptions/
│   └── AppException.php
├── Helpers/
│   └── ImageHelper.php          # 图片 URL 拼接工具
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── BaseController.php
│   │       ├── AuthController.php
│   │       └── {Resource}Controller.php
│   ├── Requests/
│   │   └── {Action}{Resource}Request.php
│   └── Resources/
│       └── {Resource}Resource.php
├── Models/
│   └── {Resource}.php
├── Repositories/
│   ├── BaseRepository.php
│   ├── Contracts/
│   │   └── {Resource}RepositoryInterface.php
│   └── {Resource}Repository.php
└── Services/
    └── {Resource}Service.php
routes/
└── api.php
```

---

## 常用 Artisan 命令

```bash
# 查看所有路由
php artisan route:list --path=api

# 清除缓存（配置/路由/视图）
php artisan optimize:clear

# 进入 Tinker 调试
php artisan tinker

# 代码格式化
./vendor/bin/pint

# 运行测试
php artisan test
```

---

## 代码审查检查清单

提交 PR 前自查：

- [ ] `declare(strict_types=1)` 已声明
- [ ] 所有方法有类型声明
- [ ] Controller 无业务逻辑、无 DB 查询
- [ ] 大表查询有必要的 WHERE 条件
- [ ] 图片路径通过 `ImageHelper::url()` 输出
- [ ] 异步关联字段做了 null 安全处理
- [ ] `./vendor/bin/pint` 无报错
- [ ] 新增路由已加 `auth:api` middleware
