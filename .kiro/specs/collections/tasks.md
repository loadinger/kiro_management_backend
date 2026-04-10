# 实现计划：Collections 模块

## 概述

按照项目标准分层架构，依次实现 Model、Repository、Service、FormRequest、Resource、Controller，最后注册路由。测试分为 Feature Test（mock Service）和 Unit Test（Resource 层属性验证）。

## 任务

- [x] 1. 创建 Eloquent Model
  - 创建 `app/Models/Collection.php`，定义 `$table = 'collections'`、`$fillable = []`、`$timestamps = false`，以及 `movies(): HasMany` 关联方法（关联 `CollectionMovie`，外键 `collection_id`）
  - 创建 `app/Models/CollectionMovie.php`，定义 `$table = 'collection_movies'`、`$fillable = []`、`$timestamps = false`
  - _需求：2.8、2.9、2.10_

- [x] 2. 创建 Repository 接口与实现
  - [x] 2.1 创建 `app/Repositories/Contracts/CollectionRepositoryInterface.php`，声明 `paginateWithFilters(array $filters): LengthAwarePaginator` 和 `findByIdWithMovies(int $id): ?Collection`
    - _需求：1.1、2.1_
  - [x] 2.2 创建 `app/Repositories/CollectionRepository.php`，继承 `BaseRepository`，实现接口
    - `paginateWithFilters`：支持 `q`（`LIKE %q%` 匹配 `name`）、`order`（默认 `asc`），按 `id` 排序，返回 `LengthAwarePaginator`
    - `findByIdWithMovies`：`Collection::with('movies')->find($id)`，预加载关联电影
    - _需求：1.4、1.5、1.6、1.13、2.1、2.11_

- [x] 3. 在 AppServiceProvider 注册绑定
  - 在 `app/Providers/AppServiceProvider.php` 的 `register()` 方法中添加 `CollectionRepositoryInterface::class → CollectionRepository::class` 绑定
  - _需求：3.1_

- [x] 4. 创建 CollectionService
  - 创建 `app/Services/CollectionService.php`，注入 `CollectionRepositoryInterface`
  - 实现 `getList(array $filters): LengthAwarePaginator`，委托 Repository
  - 实现 `findById(int $id): Collection`，找不到时抛出 `AppException('合集不存在', 404)`
  - _需求：2.4、3.1_

- [x] 5. 创建 ListCollectionRequest
  - 创建 `app/Http/Requests/ListCollectionRequest.php`
  - `authorize()` 返回 `true`
  - `rules()` 定义：`q`（nullable, string, max:100）、`order`（nullable, string, in:asc,desc）、`page`（nullable, integer, min:1, max:1000）、`per_page`（nullable, integer, min:1, max:100）
  - `messages()` 提供中文错误提示
  - _需求：1.5、1.6、1.7、1.8、3.2、3.3、3.4、3.5_

- [x] 6. 创建 API Resource
  - [x] 6.1 创建 `app/Http/Resources/CollectionListResource.php`
    - 输出字段：`id`、`tmdb_id`、`name`、`poster_url`（`ImageHelper::url($poster_path, 'w342')`）、`backdrop_url`（`ImageHelper::url($backdrop_path, 'w780')`）
    - _需求：1.9、1.10、1.11_
  - [x] 6.2 创建 `app/Http/Resources/CollectionMovieResource.php`
    - 输出字段：`movie_tmdb_id`、`movie_id`（null 安全）、`resolved`（`$this->movie_id !== null`）
    - _需求：2.8、2.9、2.10_
  - [x] 6.3 创建 `app/Http/Resources/CollectionResource.php`
    - 输出字段：`id`、`tmdb_id`、`name`、`overview`、`poster_url`（`ImageHelper::url($poster_path, 'w500')`）、`backdrop_url`（`ImageHelper::url($backdrop_path, 'original')`）、`movies`（`CollectionMovieResource::collection($this->whenLoaded('movies'))`）
    - _需求：2.5、2.6、2.7、2.11_

- [x] 7. 创建 CollectionController
  - 创建 `app/Http/Controllers/Api/CollectionController.php`，继承 `BaseController`，注入 `CollectionService`
  - `index(ListCollectionRequest $request): JsonResponse`：调用 `$this->paginate()` + `CollectionListResource::class`
  - `show(int $id): JsonResponse`：调用 `$this->success()` + `new CollectionResource(...)`
  - _需求：1.1、1.2、1.12、2.1、2.2_

- [x] 8. 注册路由
  - 在 `routes/api.php` 的 `auth:api` middleware 组内添加：
    - `Route::get('collections', [CollectionController::class, 'index'])`
    - `Route::get('collections/{id}', [CollectionController::class, 'show'])`
  - _需求：1.1、2.1、3.1_

- [x] 9. 检查点 - 确认核心实现完整
  - 确认所有文件已创建，运行 `./vendor/bin/pint` 格式化代码，确保无语法错误，如有问题请告知。

- [x] 10. 编写 Feature Test
  - [x] 10.1 创建 `tests/Feature/Collections/CollectionListTest.php`，使用 `$this->mock(CollectionService::class, ...)` mock Service
    - `test_unauthenticated_request_returns_401`
    - `test_returns_paginated_collection_list`（验证 `code:0` 及 `data.list` / `data.pagination` 结构）
    - `test_page_over_limit_returns_422`
    - `test_per_page_over_limit_returns_422`
    - `test_non_integer_params_return_422`
    - `test_invalid_order_returns_422`
    - `test_q_too_long_returns_422`
    - _需求：1.2、1.3、1.5、1.6、1.7、1.8、1.12、3.3、3.4、3.5_
  - [x] 10.2 创建 `tests/Feature/Collections/CollectionDetailTest.php`，使用 `$this->mock(CollectionService::class, ...)` mock Service
    - `test_unauthenticated_request_returns_401`
    - `test_returns_collection_detail_with_movies`（验证 `code:0` 及完整字段结构）
    - `test_returns_404_when_collection_not_found`
    - _需求：2.2、2.3、2.4、2.5_

- [x] 11. 编写 Unit Test（Resource 层属性验证）
  - [ ]* 11.1 创建 `tests/Unit/Resources/CollectionResourceTest.php`
    - 验证 `CollectionListResource` 输出包含 `id`、`tmdb_id`、`name`、`poster_url`、`backdrop_url` 五个字段（属性 1）
    - 验证列表 `poster_url` 使用 `w342`，`backdrop_url` 使用 `w780`；路径为 null 时输出 null（属性 2）
    - 验证 `CollectionResource` 输出包含 `id`、`tmdb_id`、`name`、`overview`、`poster_url`、`backdrop_url`、`movies` 七个字段（属性 3）
    - 验证详情 `poster_url` 使用 `w500`，`backdrop_url` 使用 `original`；路径为 null 时输出 null（属性 4）
    - 验证 `CollectionMovieResource`：`movie_id = null` 时 `resolved = false` 且不抛异常；`movie_id` 非 null 时 `resolved = true`（属性 5）
    - _需求：1.9、1.10、1.11、2.5、2.6、2.7、2.8、2.9、2.10_

- [x] 12. 最终检查点
  - 运行 `php artisan test tests/Feature/Collections/` 确认所有 Feature Test 通过，如有问题请告知。

## 备注

- 标有 `*` 的子任务为可选项，可跳过以加快 MVP 进度
- Feature Test 全部使用 mock Service，不依赖真实数据库
- Unit Test 使用匿名 Model 实例，不依赖数据库
- `collection_movies.movie_id` 的 null 安全处理是本模块的关键点，需在 Resource 和测试中重点验证
