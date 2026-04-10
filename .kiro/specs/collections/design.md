# 技术设计文档：Collections 模块

## 概述

为 Filmly Management Backend 新增 Collections（合集）API 模块，提供合集分页列表和合集详情两个只读接口。

数据来源于 TMDB，由采集项目写入 `collections` 表和 `collection_movies` 表，本模块严格只读。`collection_movies.movie_id` 采用异步关联模式，初始为 NULL，API 层需做 null 安全处理，输出 `resolved` 字段标识关联状态。

本模块完全参照 `production-companies` 模块的实现模式，遵循项目既有的分层架构规范。

---

## 架构

遵循项目标准分层架构：

```
Request
  └── routes/api.php（auth:api middleware）
        └── ListCollectionRequest / int $id
              └── CollectionController
                    └── CollectionService
                          └── CollectionRepositoryInterface
                                └── CollectionRepository
                                      └── Collection / CollectionMovie（Eloquent Model）
```

数据流向：
- 列表接口：`CollectionRepository::paginateWithFilters()` → `CollectionService::getList()` → `CollectionController::index()` → `CollectionListResource`
- 详情接口：`CollectionRepository::findByIdWithMovies()` → `CollectionService::findById()` → `CollectionController::show()` → `CollectionResource`

关联预加载：详情接口在 Repository 层通过 `with('movies')` 预加载 `collection_movies`，Resource 层使用 `$this->whenLoaded()` 安全访问，禁止触发额外查询。

---

## 组件与接口

### 路由

在 `routes/api.php` 的 `auth:api` middleware 组内注册：

```php
Route::get('collections', [CollectionController::class, 'index']);
Route::get('collections/{id}', [CollectionController::class, 'show']);
```

### CollectionController

继承 `BaseController`，注入 `CollectionService`。

```php
public function index(ListCollectionRequest $request): JsonResponse
// 调用 $this->paginate()，使用 CollectionListResource

public function show(int $id): JsonResponse
// 调用 $this->success()，使用 CollectionResource
```

### ListCollectionRequest

验证规则：

| 参数 | 规则 |
|------|------|
| `q` | nullable, string, max:100 |
| `order` | nullable, string, in:asc,desc |
| `page` | nullable, integer, min:1, max:1000 |
| `per_page` | nullable, integer, min:1, max:100 |

`messages()` 提供中文错误提示。

### CollectionService

```php
public function getList(array $filters): LengthAwarePaginator
// 委托 Repository，无额外业务逻辑

public function findById(int $id): Collection
// 找不到时抛出 AppException('合集不存在', 404)
```

### CollectionRepositoryInterface

```php
public function paginateWithFilters(array $filters): LengthAwarePaginator;
public function findByIdWithMovies(int $id): ?Collection;
```

### CollectionRepository

继承 `BaseRepository`，实现接口：

- `paginateWithFilters`：支持 `q`（LIKE %q%）、`order`（默认 asc）过滤，按 `id` 排序
- `findByIdWithMovies`：`Collection::with('movies')->find($id)`，预加载关联电影

### AppServiceProvider 绑定

```php
$this->app->bind(CollectionRepositoryInterface::class, CollectionRepository::class);
```

---

## 数据模型

### Collection Model

对应 `collections` 表（只读，无 timestamps）：

```php
protected $table = 'collections';
protected $fillable = [];
public $timestamps = false;
```

关联关系：

```php
public function movies(): HasMany
{
    return $this->hasMany(CollectionMovie::class, 'collection_id');
}
```

### CollectionMovie Model

对应 `collection_movies` 表（只读，无 timestamps）：

```php
protected $table = 'collection_movies';
protected $fillable = [];
public $timestamps = false;
```

关键字段：

| 字段 | 类型 | 说明 |
|------|------|------|
| `collection_id` | bigint | 所属合集 |
| `movie_tmdb_id` | uint | 同步时填充，始终非 NULL |
| `movie_id` | bigint NULL | reconcile 后填充，初始 NULL |

### CollectionListResource

列表响应字段：

| 字段 | 来源 | 说明 |
|------|------|------|
| `id` | `collections.id` | 本地主键 |
| `tmdb_id` | `collections.tmdb_id` | TMDB ID |
| `name` | `collections.name` | 合集名称 |
| `poster_url` | `ImageHelper::url($poster_path, 'w342')` | 海报，null 安全 |
| `backdrop_url` | `ImageHelper::url($backdrop_path, 'w780')` | 背景图，null 安全 |

### CollectionResource

详情响应字段：

| 字段 | 来源 | 说明 |
|------|------|------|
| `id` | `collections.id` | |
| `tmdb_id` | `collections.tmdb_id` | |
| `name` | `collections.name` | |
| `overview` | `collections.overview` | 可为 null |
| `poster_url` | `ImageHelper::url($poster_path, 'w500')` | null 安全 |
| `backdrop_url` | `ImageHelper::url($backdrop_path, 'original')` | null 安全 |
| `movies` | `CollectionMovieResource::collection(...)` | 关联电影列表 |

### CollectionMovieResource

关联电影记录字段（null 安全处理）：

```php
[
    'movie_tmdb_id' => $this->movie_tmdb_id,
    'movie_id'      => $this->movie_id,           // null 时输出 null
    'resolved'      => $this->movie_id !== null,  // null 时输出 false
]
```

---

## 正确性属性

*属性（Property）是在系统所有有效执行中都应成立的特征或行为——本质上是对系统应做什么的形式化陈述。属性是人类可读规范与机器可验证正确性保证之间的桥梁。*

### 属性 1：列表响应字段完整性

*对于任意* Collection 对象，`CollectionListResource` 的输出必须包含 `id`、`tmdb_id`、`name`、`poster_url`、`backdrop_url` 五个字段。

**验证：需求 1.9**

### 属性 2：列表图片 URL 格式正确性

*对于任意* 非 null 的 `poster_path` 或 `backdrop_path`，`CollectionListResource` 输出的 `poster_url` 必须使用 `w342` 尺寸，`backdrop_url` 必须使用 `w780` 尺寸；路径为 null 时对应字段输出 null。

**验证：需求 1.10、1.11**

### 属性 3：详情响应字段完整性

*对于任意* Collection 对象（含预加载的 movies 关联），`CollectionResource` 的输出必须包含 `id`、`tmdb_id`、`name`、`overview`、`poster_url`、`backdrop_url`、`movies` 七个字段。

**验证：需求 2.5**

### 属性 4：详情图片 URL 格式正确性

*对于任意* 非 null 的 `poster_path` 或 `backdrop_path`，`CollectionResource` 输出的 `poster_url` 必须使用 `w500` 尺寸，`backdrop_url` 必须使用 `original` 尺寸；路径为 null 时对应字段输出 null。

**验证：需求 2.6、2.7**

### 属性 5：关联电影记录字段完整性与 resolved 语义

*对于任意* CollectionMovie 记录，`CollectionMovieResource` 的输出必须包含 `movie_tmdb_id`、`movie_id`、`resolved` 三个字段；当 `movie_id` 为 null 时 `resolved` 必须为 `false`，当 `movie_id` 不为 null 时 `resolved` 必须为 `true`，且不得抛出异常。

**验证：需求 2.8、2.9、2.10**

---

## 错误处理

| 场景 | 处理方式 | 响应 |
|------|---------|------|
| 未携带 / 无效 Token | `auth:api` middleware 拦截 | `code: 401` |
| 参数验证失败 | `ListCollectionRequest` 验证不通过 | `code: 422`，中文错误信息 |
| 合集不存在 | `CollectionService::findById` 抛出 `AppException('合集不存在', 404)` | `code: 404` |
| `movie_id` 为 NULL | Resource 层 null 安全处理，`resolved=false` | 正常返回，不报错 |
| 未捕获异常 | 全局异常处理器 | `code: 500` |

---

## 测试策略

本模块为只读 API，测试使用 mock Service 策略，不依赖真实数据库。

### Feature Test（主要）

位置：`tests/Feature/Collections/`

文件：
- `CollectionListTest.php`：列表接口测试
- `CollectionDetailTest.php`：详情接口测试

必须覆盖的场景：

| 场景 | 测试方法 |
|------|---------|
| 未认证请求返回 401 | `test_unauthenticated_request_returns_401` |
| 认证后返回 code:0 和分页结构 | `test_returns_paginated_collection_list` |
| page 超过 1000 返回 422 | `test_page_over_limit_returns_422` |
| per_page 超过 100 返回 422 | `test_per_page_over_limit_returns_422` |
| 非整数参数返回 422 | `test_non_integer_params_return_422` |
| 非法 order 值返回 422 | `test_invalid_order_returns_422` |
| q 超过 100 字符返回 422 | `test_q_too_long_returns_422` |
| 合集不存在返回 404 | `test_returns_404_when_collection_not_found` |
| 详情返回正确结构 | `test_returns_collection_detail_with_movies` |

所有只读接口测试使用 `$this->mock(CollectionService::class, ...)` mock Service，不依赖真实数据库。

### Unit Test（按需）

位置：`tests/Unit/Resources/`

文件：`CollectionResourceTest.php`

覆盖属性 1-5 的 Resource 层逻辑：

- 验证 `CollectionListResource` 输出字段完整性（属性 1）
- 验证列表图片 URL 使用正确尺寸（属性 2）
- 验证 `CollectionResource` 输出字段完整性（属性 3）
- 验证详情图片 URL 使用正确尺寸（属性 4）
- 验证 `CollectionMovieResource` 的 `resolved` 语义（属性 5）：
  - `movie_id = null` → `resolved = false`，不抛异常
  - `movie_id = 123` → `resolved = true`

测试使用 `Illuminate\Database\Eloquent\Model` 的匿名实例或 mock 对象，不依赖数据库。
