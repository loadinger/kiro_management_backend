# 编码规范

## PHP / Laravel 规范

### 基本原则
- 严格遵循 PSR-12 代码风格
- 所有类、方法必须有类型声明（PHP 8.x 严格类型）
- 文件顶部声明 `declare(strict_types=1);`
- 使用 PHP 8.x 特性：readonly、match、named arguments、enum

### Controller
- 继承 `App\Http\Controllers\Api\BaseController`
- 方法只做三件事：接收请求 → 调用 Service → 返回响应
- 禁止在 Controller 中写 DB 查询或业务逻辑
- 使用 FormRequest 做参数验证，不在 Controller 中手动 validate

```php
public function index(ListMovieRequest $request): JsonResponse
{
    $result = $this->movieService->getList($request->validated());
    return $this->success($result);
}
```

### Service
- 构造函数注入 Repository 依赖
- 方法返回值使用具体类型或 DTO，避免返回裸数组
- 复杂业务逻辑拆分为私有方法

### Repository
- 继承 `App\Repositories\BaseRepository`
- 封装所有 Eloquent 查询，Service 层不直接调用 Model
- 分页统一使用 `paginate()` 或 `simplePaginate()`，返回 LengthAwarePaginator

### Model
- 明确定义 `$fillable` 或 `$guarded`
- 定义所有关联关系方法
- 使用 `$casts` 声明类型转换（json 字段、boolean、date 等）
- 不在 Model 中写业务逻辑

### FormRequest
- 继承 `Illuminate\Foundation\Http\FormRequest`
- `authorize()` 返回 `true`（认证由 middleware 处理）
- `rules()` 中定义完整验证规则
- 可重写 `messages()` 提供中文错误信息

### API Resource
- 继承 `Illuminate\Http\Resources\Json\JsonResource`
- 图片路径在此层统一拼接完整 URL
- 异步关联字段（person_id 为 NULL）做 null 安全处理
- 列表用 `ResourceCollection`，详情用单个 `Resource`

## 响应辅助方法

BaseController 提供统一响应方法：

```php
$this->success($data, $message = 'success');
$this->error($message, $code = 500, $data = null);
$this->paginate($paginator, $resourceClass);
```

## 异常处理

- 自定义异常继承 `App\Exceptions\AppException`
- 在 `bootstrap/app.php` 的 `withExceptions` 中统一捕获，转换为信封格式响应
- 不在 Controller/Service 中 try-catch 普通业务异常，让全局处理器接管

## 代码风格工具

- 使用 Laravel Pint（已内置）做代码格式化
- 运行：`./vendor/bin/pint`
- 提交前确保 Pint 无报错

## Git 提交规范

遵循 Conventional Commits：

```
feat: 新功能
fix: 修复 bug
refactor: 重构
chore: 构建/工具变更
docs: 文档
test: 测试
```

示例：`feat: add movie list API with pagination`
