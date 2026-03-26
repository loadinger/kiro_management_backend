# 编码规范

## 基本原则

- 严格遵循 PSR-12 代码风格
- 所有 PHP 文件顶部声明 `declare(strict_types=1);`
- 所有类、方法、属性必须有完整类型声明，禁止 `mixed` 滥用
- 积极使用 PHP 8.x 特性：`readonly`、`match`、`enum`、`named arguments`、`nullsafe operator`
- 禁止魔法字符串，状态值、类型值统一用 `enum` 定义

---

## 分层架构与职责边界

### 层级调用规则

```
Route → FormRequest → Controller → Service → Repository → Model
```

- 只能向下调用，禁止跨层（Controller 不能直接调 Model）
- Service 之间可以互相调用，但要避免循环依赖
- Repository 只操作单一 Model，跨表聚合逻辑放 Service

### Controller

- 继承 `App\Http\Controllers\Api\BaseController`
- 方法只做三件事：接收请求 → 调用 Service → 返回响应
- 禁止写任何业务逻辑、DB 查询、条件判断
- 参数验证必须用 FormRequest，禁止在 Controller 中手动 `validate()`
- 构造函数注入 Service 依赖

```php
public function __construct(private readonly MovieService $movieService) {}

public function index(ListMovieRequest $request): JsonResponse
{
    return $this->paginate(
        $this->movieService->getList($request->validated()),
        MovieResource::class
    );
}
```

### Service

- 构造函数注入 Repository 依赖（通过 Laravel 容器自动解析）
- 方法返回值使用具体类型：`LengthAwarePaginator`、`Model`、`Collection`、DTO，禁止返回裸 `array`
- 一个 Service 方法只做一件事，复杂逻辑拆分为 `private` 方法
- 不处理 HTTP 层概念（不引用 Request/Response）

### Repository

- 继承 `App\Repositories\BaseRepository`
- 封装所有 Eloquent/DB 查询，Service 层禁止直接调用 Model 静态方法
- 方法命名语义化：`findById`、`findByTmdbId`、`paginateWithFilters`、`existsByTmdbId`
- 分页统一返回 `LengthAwarePaginator`
- 复杂查询用 Query Builder，避免 N+1（善用 `with()`、`withCount()`）

### Model

- 明确定义 `$fillable`（禁止用 `$guarded = []`）
- `$casts` 必须声明所有非字符串字段：json、boolean、date、enum 等
- 定义所有关联关系方法，关联方法名用驼峰单数/复数
- 禁止在 Model 中写业务逻辑，`boot()` 只用于注册 Observer
- 只读数据表的 Model 加 `public $timestamps = false` 或按实际情况设置

### FormRequest

- `authorize()` 统一返回 `true`（认证由 middleware 处理）
- `rules()` 定义完整验证规则，不留空
- `messages()` 提供中文错误信息
- `prepareForValidation()` 做数据预处理（如类型转换、trim）

### API Resource

- 继承 `Illuminate\Http\Resources\Json\JsonResource`
- 图片路径在此层统一拼接完整 URL，使用 `ImageHelper::url($path, $size)`
- 异步关联字段（`person_id` 为 NULL）做 null 安全处理，不报错
- 列表接口用 `JsonResource::collection()`，详情用单个 Resource
- 禁止在 Resource 中触发额外查询（数据应由 Service 层预加载好）

---

## 依赖注入规范

- 统一使用构造函数注入，禁止 `app()` / `resolve()` 在业务代码中使用
- 依赖声明为 `private readonly`
- 接口绑定在 `AppServiceProvider` 中注册，业务代码依赖接口而非具体实现（Repository 层适用）

```php
// AppServiceProvider::register()
$this->app->bind(MovieRepositoryInterface::class, MovieRepository::class);
```

---

## 异常处理规范

- 自定义业务异常继承 `App\Exceptions\AppException`
- `AppException` 构造函数接收 `$message` 和 `$errorCode`（对应信封格式的 code）
- 全局异常处理在 `bootstrap/app.php` 的 `withExceptions` 中统一转换为信封格式
- 禁止在 Controller / Service 中 `try-catch` 普通业务异常，让全局处理器接管
- 只在需要做资源清理、事务回滚时才用 `try-catch`，catch 后必须重新 throw

```php
// 抛出业务异常
throw new AppException('电影不存在', 404);

// 需要事务时
DB::transaction(function () {
    // ...
});
// 或手动控制
try {
    DB::beginTransaction();
    // ...
    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    throw $e; // 必须重新抛出
}
```

---

## 命名约定

| 类型 | 规范 | 示例 |
|------|------|------|
| Controller | `{Resource}Controller` | `MovieController` |
| Service | `{Resource}Service` | `MovieService` |
| Repository | `{Resource}Repository` | `MovieRepository` |
| Repository 接口 | `{Resource}RepositoryInterface` | `MovieRepositoryInterface` |
| Model | 单数大驼峰 | `Movie`、`TvShow` |
| FormRequest | `{Action}{Resource}Request` | `ListMovieRequest`、`StoreArticleRequest` |
| Resource | `{Resource}Resource` | `MovieResource` |
| Exception | `{描述}Exception` | `MovieNotFoundException` |
| Enum | 大驼峰，值用小写下划线 | `CreditType::Cast` |
| 路由参数 | 小写下划线 | `{tv_show_id}` |
| 数据库字段 | 小写下划线（与 DB 保持一致） | `tmdb_id`、`poster_path` |
| 方法名 | 动词开头驼峰 | `getList`、`findById`、`isPublished` |

---

## 日志规范

- 使用 Laravel 内置 `Log` Facade，禁止 `echo` / `var_dump` 调试
- 日志级别使用规范：
  - `debug`：开发调试，生产环境不输出
  - `info`：关键业务操作记录（如用户登录）
  - `warning`：非预期但可恢复的情况
  - `error`：需要人工介入的错误
- 日志必须带上下文数组，禁止把变量拼进字符串

```php
// 正确
Log::info('用户登录', ['user_id' => $user->id, 'ip' => $request->ip()]);

// 错误
Log::info('用户 ' . $user->id . ' 登录');
```

- 敏感信息（密码、token）禁止写入日志

---

## 注释规范

- 类和公共方法必须有 PHPDoc 注释
- 注释说明"为什么"，而不是"做了什么"（代码本身说明做了什么）
- 复杂业务逻辑、非直觉的实现必须加行内注释
- TODO 格式：`// TODO: 描述 @author`
- 禁止无意义注释：`// 获取用户` 这种和代码重复的注释

```php
/**
 * 获取电影列表，支持按类型、年份、语言筛选。
 * 大表限制最大翻页深度为 1000 页，防止慢查询。
 */
public function getList(array $filters): LengthAwarePaginator
```

---

## 代码风格工具

- 使用 Laravel Pint 做代码格式化，配置文件 `pint.json`
- 运行：`./vendor/bin/pint`
- 提交前必须通过 Pint 检查

---

## Git 提交规范

遵循 Conventional Commits：

| 前缀 | 用途 |
|------|------|
| `feat` | 新功能 |
| `fix` | 修复 bug |
| `refactor` | 重构（不改变行为） |
| `chore` | 构建/工具/依赖变更 |
| `docs` | 文档 |
| `test` | 测试 |
| `perf` | 性能优化 |

示例：`feat: add movie list API with genre filter`
