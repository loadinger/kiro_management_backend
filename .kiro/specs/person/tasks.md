# 实现计划：Person 人物模块

## 概述

按项目标准分层架构（Model → Repository → Service → FormRequest → Resource → Controller → 路由）分三组实现：PersonController（列表+详情）、PersonMovieController（参演电影）、PersonTvShowController（参演电视剧）。测试使用 mock Service/Repository，不依赖真实数据库。

## 任务

- [x] 1. 实现 Person 基础层（Model + Repository）
  - [x] 1.1 创建 `app/Models/Person.php`
    - 定义 `$fillable = []`（只读表）
    - 定义 `$casts`：`gender` → `integer`，`adult` → `boolean`，`birthday`/`deathday` → `date`，`popularity` → `double`，`also_known_as` → `array`
    - 保留 `$timestamps = true`（表有 created_at/updated_at）
    - _需求：1.12、2.4、4.1、4.2_
  - [x] 1.2 创建 `app/Repositories/Contracts/PersonRepositoryInterface.php`
    - 声明 `paginateWithFilters(array $filters): LengthAwarePaginator`
    - 声明 `findById(int $id): ?Person`
    - 声明 `existsById(int $id): bool`
    - _需求：1、2、3.1_
  - [x] 1.3 创建 `app/Repositories/PersonRepository.php`（继承 BaseRepository）
    - 实现 `paginateWithFilters`：支持 `gender`/`adult`/`known_for_department`/`q`（LIKE q%）筛选，白名单排序字段 `['id','popularity','updated_at','created_at']`，默认 `id DESC`
    - 方法注释标注大表约束：`per_page ≤ 50`，禁止无条件全表扫描
    - 实现 `findById` 和 `existsById`
    - _需求：1.8、1.9、1.10、1.11、3.1、3.3_
  - [ ]* 1.4 为 PersonRepository 编写 Feature Test（mock 验证）
    - 验证排序白名单：传入非法 sort 字段时 FormRequest 拦截返回 422
    - _需求：3.3_

- [x] 2. 注册 Person Repository 绑定 + 实现 PersonService
  - [x] 2.1 在 `app/Providers/AppServiceProvider.php` 注册绑定
    - `$this->app->bind(PersonRepositoryInterface::class, PersonRepository::class)`
    - _需求：1、2_
  - [x] 2.2 创建 `app/Services/PersonService.php`
    - 注入 `PersonRepositoryInterface`
    - 实现 `getList(array $filters): LengthAwarePaginator`
    - 实现 `findById(int $id): Person`，不存在时 `throw new AppException('人物不存在', 404)`
    - _需求：1.2、2.2、2.3_

- [x] 3. 实现 PersonController（列表 + 详情）
  - [x] 3.1 创建 `app/Http/Requests/ListPersonRequest.php`
    - `per_page`：integer，默认 20，最大 50（超过返回 422）
    - `page`：integer，默认 1，最大 1000
    - `gender`：integer，`Rule::in([0,1,2,3])`
    - `adult`：integer，`Rule::in([0,1])`
    - `known_for_department`：string，max:100
    - `q`：string，max:100
    - `sort`：string，`Rule::in(['id','popularity','updated_at','created_at'])`
    - `order`：string，`Rule::in(['asc','desc'])`，默认 `desc`
    - _需求：1.3、1.4、1.5、1.6、1.7、1.8、1.9、1.10_
  - [x] 3.2 创建 `app/Http/Resources/PersonListResource.php`
    - 输出字段：`id`、`tmdb_id`、`name`、`gender`、`adult`、`known_for_department`、`popularity`、`profile_path`（`ImageHelper::url($this->profile_path, 'w185')`）、`created_at`（ISO 8601 UTC）、`updated_at`（ISO 8601 UTC）
    - _需求：1.12、4.2_
  - [x] 3.3 创建 `app/Http/Resources/PersonResource.php`（详情）
    - 输出字段：`id`、`tmdb_id`、`name`、`gender`、`adult`、`biography`、`birthday`（`Y-m-d`，null 时 null）、`deathday`（`Y-m-d`，null 时 null）、`place_of_birth`、`known_for_department`、`popularity`、`homepage`、`imdb_id`、`also_known_as`、`profile_path`（`ImageHelper::url($this->profile_path, 'w342')`）、`created_at`（ISO 8601 UTC）、`updated_at`（ISO 8601 UTC）
    - _需求：2.4、4.1、4.2_
  - [x] 3.4 创建 `app/Http/Controllers/Api/PersonController.php`（继承 BaseController）
    - 注入 `PersonService`
    - `index(ListPersonRequest $request): JsonResponse` → `$this->paginate(..., PersonListResource::class)`
    - `show(int $id): JsonResponse` → `$this->success(new PersonResource(...))`
    - _需求：1.2、2.2_
  - [x] 3.5 在 `routes/api.php` 的 `auth:api` 组内注册路由
    - `Route::get('persons', [PersonController::class, 'index'])`
    - `Route::get('persons/{id}', [PersonController::class, 'show'])`
    - _需求：1.1、2.1_

- [ ] 4. 测试 PersonController
  - [ ]* 4.1 创建 `tests/Feature/Persons/PersonListTest.php`（mock PersonService）
    - `test_unauthenticated_request_returns_401`
    - `test_returns_paginated_person_list`（验证 list + pagination 结构）
    - `test_per_page_exceeding_50_returns_422`
    - `test_page_exceeding_1000_returns_422`
    - `test_invalid_gender_returns_422`（gender=4）
    - `test_invalid_adult_returns_422`（adult=2）
    - `test_invalid_sort_field_returns_422`
    - `test_profile_path_contains_full_image_url`（验证包含 `image.tmdb.org/t/p/w185`）
    - `test_default_sort_is_id_desc`
    - _需求：1.1、1.2、1.3、1.4、1.5、1.6、1.9、1.12_
  - [ ]* 4.2 创建 `tests/Feature/Persons/PersonDetailTest.php`（mock PersonService）
    - `test_unauthenticated_request_returns_401`
    - `test_returns_person_detail`（验证所有输出字段存在）
    - `test_returns_404_when_person_not_found`
    - `test_birthday_deathday_format_is_y_m_d`
    - `test_timestamps_format_is_iso8601_utc`
    - `test_profile_path_contains_full_image_url`（验证包含 `image.tmdb.org/t/p/w342`）
    - _需求：2.1、2.2、2.3、2.4、4.1、4.2_

- [x] 5. 检查点 — 确保所有测试通过，如有疑问请告知。

- [x] 6. 实现 PersonMovie 层（MovieCredit Model 补全 + Repository）
  - [x] 6.1 在 `app/Models/MovieCredit.php` 补充 `movie()` 关联方法
    - `public function movie(): BelongsTo { return $this->belongsTo(Movie::class); }`
    - 确认 Movie Model 已存在（`app/Models/Movie.php`）
    - _需求：5.8_
  - [x] 6.2 创建 `app/Repositories/Contracts/PersonMovieRepositoryInterface.php`
    - 声明 `paginateByPersonId(int $personId, array $filters): LengthAwarePaginator`
    - _需求：5.4_
  - [x] 6.3 创建 `app/Repositories/PersonMovieRepository.php`（继承 BaseRepository）
    - 实现 `paginateByPersonId`：`WHERE movie_credits.person_id = $personId`（WHERE 条件自然过滤 NULL），`with('movie')` 预加载，默认 `id DESC`
    - _需求：5.4、5.8、5.9_

- [x] 7. 注册 PersonMovie 绑定 + 实现 PersonMovieService
  - [x] 7.1 在 `AppServiceProvider` 注册绑定
    - `$this->app->bind(PersonMovieRepositoryInterface::class, PersonMovieRepository::class)`
    - _需求：5_
  - [x] 7.2 创建 `app/Services/PersonMovieService.php`
    - 注入 `PersonRepositoryInterface` 和 `PersonMovieRepositoryInterface`
    - 实现 `getList(int $personId, array $filters): LengthAwarePaginator`
    - 先调用 `personRepository->existsById($personId)`，不存在时 `throw new AppException('人物不存在', 404)`
    - _需求：5.3、5.4_

- [x] 8. 实现 PersonMovieController
  - [x] 8.1 创建 `app/Http/Requests/ListPersonMovieRequest.php`
    - `person_id`：required integer
    - `per_page`：integer，默认 20，最大 100
    - `page`：integer，默认 1，最大 1000
    - _需求：5.2、5.5、5.6_
  - [x] 8.2 创建 `app/Http/Resources/PersonMovieResource.php`
    - 输出字段：`id`（movie_credits.id）、`movie_id`、`credit_type`、`character`、`cast_order`、`department_id`、`job_id`
    - 内嵌 `movie` 对象：`id`、`tmdb_id`、`title`、`original_title`、`release_date`（`Y-m-d`，null 时 null）、`poster_path`（`ImageHelper::url($this->movie->poster_path, 'w342')`）
    - _需求：5.8_
  - [x] 8.3 创建 `app/Http/Controllers/Api/PersonMovieController.php`（继承 BaseController）
    - 注入 `PersonMovieService`
    - `index(ListPersonMovieRequest $request): JsonResponse`
    - 从 `$request->validated()` 取出 `person_id`，调用 `service->getList($personId, $filters)`
    - _需求：5.2、5.7_
  - [x] 8.4 在 `routes/api.php` 注册路由
    - `Route::get('person-movies', [PersonMovieController::class, 'index'])`
    - _需求：5.1_
  - [ ]* 8.5 创建 `tests/Feature/Persons/PersonMovieTest.php`（mock PersonMovieService）
    - `test_unauthenticated_request_returns_401`
    - `test_person_id_required`
    - `test_returns_404_when_person_not_found`
    - `test_returns_paginated_movie_list`（验证 list + pagination 结构）
    - `test_per_page_exceeding_100_returns_422`
    - `test_movie_poster_path_contains_full_image_url`（验证包含 `image.tmdb.org/t/p/w342`）
    - `test_default_sort_is_id_desc`
    - _需求：5.1、5.2、5.3、5.5、5.7、5.8、5.9_

- [x] 9. 检查点 — 确保所有测试通过，如有疑问请告知。

- [x] 10. 实现 PersonTvShow 层（TvEpisodeCredit Model 确认 + Repository）
  - [x] 10.1 确认/补全 `app/Models/TvEpisodeCredit.php`
    - 确认已有 `$fillable = []`、`credit_type` cast、`tvEpisode()` 关联方法
    - 如缺少 `tvEpisode(): BelongsTo`，补充：`return $this->belongsTo(TvEpisode::class)`
    - _需求：6.4_
  - [x] 10.2 创建 `app/Repositories/Contracts/PersonTvShowRepositoryInterface.php`
    - 声明 `paginateByPersonId(int $personId, array $filters): LengthAwarePaginator`
    - 方法注释标注：关联路径 `tv_episode_credits → tv_episodes → tv_shows`，禁止 N+1
    - _需求：6.4、6.10_
  - [x] 10.3 创建 `app/Repositories/PersonTvShowRepository.php`（继承 BaseRepository）
    - 实现 `paginateByPersonId`：以 `TvShow` 为主表，JOIN `tv_episodes` 和 `tv_episode_credits`，`WHERE tv_episode_credits.person_id = $personId`，`->select('tv_shows.*')->distinct()`，默认 `tv_shows.id DESC`
    - _需求：6.4、6.5、6.9、6.10_

- [x] 11. 注册 PersonTvShow 绑定 + 实现 PersonTvShowService
  - [x] 11.1 在 `AppServiceProvider` 注册绑定
    - `$this->app->bind(PersonTvShowRepositoryInterface::class, PersonTvShowRepository::class)`
    - _需求：6_
  - [x] 11.2 创建 `app/Services/PersonTvShowService.php`
    - 注入 `PersonRepositoryInterface` 和 `PersonTvShowRepositoryInterface`
    - 实现 `getList(int $personId, array $filters): LengthAwarePaginator`
    - 先调用 `personRepository->existsById($personId)`，不存在时 `throw new AppException('人物不存在', 404)`
    - _需求：6.3、6.4_

- [x] 12. 实现 PersonTvShowController
  - [x] 12.1 创建 `app/Http/Requests/ListPersonTvShowRequest.php`
    - `person_id`：required integer
    - `per_page`：integer，默认 20，最大 100
    - `page`：integer，默认 1，最大 1000
    - _需求：6.2、6.6、6.7_
  - [x] 12.2 创建 `app/Http/Resources/PersonTvShowResource.php`
    - 输出字段：`id`、`tmdb_id`、`name`、`original_name`、`first_air_date`（`Y-m-d`，null 时 null）、`poster_path`（`ImageHelper::url($this->poster_path, 'w342')`）、`status`、`number_of_seasons`、`number_of_episodes`
    - _需求：6.9_
  - [x] 12.3 创建 `app/Http/Controllers/Api/PersonTvShowController.php`（继承 BaseController）
    - 注入 `PersonTvShowService`
    - `index(ListPersonTvShowRequest $request): JsonResponse`
    - 从 `$request->validated()` 取出 `person_id`，调用 `service->getList($personId, $filters)`
    - _需求：6.2、6.8_
  - [x] 12.4 在 `routes/api.php` 注册路由
    - `Route::get('person-tv-shows', [PersonTvShowController::class, 'index'])`
    - _需求：6.1_
  - [ ]* 12.5 创建 `tests/Feature/Persons/PersonTvShowTest.php`（mock PersonTvShowService）
    - `test_unauthenticated_request_returns_401`
    - `test_person_id_required`
    - `test_returns_404_when_person_not_found`
    - `test_returns_paginated_tv_show_list`（验证 list + pagination 结构）
    - `test_per_page_exceeding_100_returns_422`
    - `test_tv_show_poster_path_contains_full_image_url`（验证包含 `image.tmdb.org/t/p/w342`）
    - _需求：6.1、6.2、6.3、6.6、6.8、6.9_
  - [ ]* 12.6 创建 `tests/Unit/Repositories/PersonTvShowRepositoryTest.php`（SQLite in-memory）
    - 手动建 `tv_shows`、`tv_episodes`、`tv_episode_credits` 表
    - 插入同一 person 参演同一 tv_show 多集的数据
    - 验证属性 2：返回结果中 tv_show id 唯一，无重复（DISTINCT 去重正确）
    - _需求：6.4、6.5；设计属性 2_

- [x] 13. 最终检查点 — 运行 `./vendor/bin/pint` 格式化，确保所有测试通过，如有疑问请告知。

## 备注

- 标注 `*` 的子任务为可选测试任务，可跳过以加快 MVP 进度
- 每个任务引用具体需求编号以保证可追溯性
- 属性 2（tv_shows 去重）通过 Unit Test 12.6 验证，使用 SQLite in-memory 不依赖云端数据库
- `PersonMovieService` 和 `PersonTvShowService` 均需注入 `PersonRepositoryInterface` 用于 person 存在性校验
