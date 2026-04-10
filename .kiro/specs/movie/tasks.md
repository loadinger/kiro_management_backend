# 实现计划：Movie 模块 API

## 概述

按照项目标准分层（Controller → Service → Repository → Model），依次实现 7 类只读接口。所有接口加 `auth:api` middleware，测试使用 mock Service 策略。

## 任务

- [x] 1. 创建 Enum 和基础 Model
  - [x] 1.1 创建 `app/Enums/CreditType.php`
    - 定义 `enum CreditType: string`，值为 `cast` / `crew`
    - _需求：3.4_
  - [x] 1.2 创建 `app/Models/Movie.php`
    - 定义 `$fillable = []`、`$casts`（含 date、boolean、array、float、integer 字段）
    - _需求：1.12, 2.4_
  - [x] 1.3 创建 `app/Models/MovieCredit.php`
    - 定义 `$casts`（含 `CreditType` enum）、`person()` BelongsTo 关联
    - _需求：3.5, 3.6_
  - [x] 1.4 创建 `app/Models/MovieImage.php`
    - 定义 `$timestamps = false`、`$casts`（width、height、vote_average、vote_count）
    - _需求：4.5_
  - [x] 1.5 创建 `app/Models/Person.php`
    - 只需 `id`、`tmdb_id`、`name`、`profile_path` 字段用于 credit 关联输出
    - _需求：3.6_

- [x] 2. 创建 Repository 接口与实现
  - [x] 2.1 创建 `MovieRepositoryInterface` 和 `MovieRepository`
    - 实现 `paginateWithFilters(array $filters): LengthAwarePaginator`（含 q/genre_id/status/release_year/adult/sort/order 筛选）
    - 实现 `findById(int $id): ?Movie`
    - 注意：`page` 最大 1000，排序字段白名单校验
    - _需求：1.1, 1.6–1.11, 2.1_
  - [x] 2.2 创建 `MovieCreditRepositoryInterface` 和 `MovieCreditRepository`
    - 实现 `paginateByMovieId(int $movieId, array $filters): LengthAwarePaginator`
    - 预加载 `person`（`with('person')`），支持 `credit_type` 筛选
    - _需求：3.1, 3.4_
  - [x] 2.3 创建 `MovieImageRepositoryInterface` 和 `MovieImageRepository`
    - 实现 `paginateByMovieId(int $movieId, array $filters): LengthAwarePaginator`，支持 `image_type` 筛选
    - _需求：4.1, 4.4_
  - [x] 2.4 创建 `MovieGenreRepositoryInterface` 和 `MovieGenreRepository`
    - 实现 `getByMovieId(int $movieId): Collection`（JOIN movie_genres → genres）
    - _需求：5.1_
  - [x] 2.5 创建 `MovieKeywordRepositoryInterface` 和 `MovieKeywordRepository`
    - 实现 `getByMovieId(int $movieId): Collection`（JOIN movie_keywords → keywords）
    - _需求：6.1_
  - [x] 2.6 创建 `MovieProductionCompanyRepositoryInterface` 和 `MovieProductionCompanyRepository`
    - 实现 `getByMovieId(int $movieId): Collection`（JOIN movie_production_companies → production_companies）
    - _需求：7.1_

- [x] 3. 在 AppServiceProvider 注册 Repository 绑定
  - 绑定全部 6 个 Repository 接口到实现类
  - _需求：1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1_

- [x] 4. 创建 Service 层
  - [x] 4.1 创建 `MovieService`
    - 实现 `getList(array $filters): LengthAwarePaginator`
    - 实现 `findById(int $id): Movie`（不存在时抛出 `AppException('电影不存在', 404)`）
    - _需求：1.1, 2.3_
  - [x] 4.2 创建 `MovieCreditService`、`MovieImageService`
    - 各实现 `getList(int $movieId, array $filters): LengthAwarePaginator`
    - _需求：3.1, 4.1_
  - [x] 4.3 创建 `MovieGenreService`、`MovieKeywordService`、`MovieProductionCompanyService`
    - 各实现 `getByMovieId(int $movieId): Collection`
    - _需求：5.1, 6.1, 7.1_

- [x] 5. 创建 FormRequest 验证类
  - [x] 5.1 创建 `ListMovieRequest`
    - 验证规则：q/genre_id/status/release_year/adult/sort/order/page（max:1000）/per_page（max:100）
    - _需求：1.3, 1.4, 1.5_
  - [x] 5.2 创建 `ListMovieCreditRequest`
    - 验证规则：movie_id（required, min:1）/credit_type（in:cast,crew）/page（max:1000）/per_page（max:100）
    - _需求：3.3_
  - [x] 5.3 创建 `ListMovieImageRequest`
    - 验证规则：movie_id（required, min:1）/image_type（in:poster,backdrop,logo）/page（max:1000）/per_page（max:100）
    - _需求：4.3_
  - [x] 5.4 创建 `ListMovieGenreRequest`、`ListMovieKeywordRequest`、`ListMovieProductionCompanyRequest`
    - 各含 movie_id（required, integer, min:1）
    - _需求：5.3, 6.3, 7.3_

- [x] 6. 创建 API Resource 层
  - [x] 6.1 创建 `MovieListResource`（列表用，字段较少）
    - 输出：id/tmdb_id/title/original_title/original_language/status/release_date/runtime/popularity/vote_average/vote_count/adult/poster_path（w342）/backdrop_path（w780）
    - _需求：1.12_
  - [x] 6.2 创建 `MovieResource`（详情用，全字段）
    - 输出：id/tmdb_id/imdb_id/title/original_title/original_language/overview/tagline/status/release_date/runtime/budget/revenue/popularity/vote_average/vote_count/adult/video/poster_path（w500）/backdrop_path（original）/homepage/spoken_language_codes/production_country_codes/created_at/updated_at
    - _需求：2.4_
  - [x] 6.3 创建 `MovieCreditResource`
    - 输出：id/movie_id/person_tmdb_id/person_id/credit_type/character/cast_order/department_id/job_id/person（null 安全处理）
    - person_id 为 NULL 时 person 字段输出 null，不报错
    - _需求：3.5, 3.6_
  - [x] 6.4 创建 `MovieImageResource`
    - 输出：id/movie_id/image_type/file_path（backdrop→w780，其余→w342）/width/height/vote_average/vote_count
    - _需求：4.5_
  - [x] 6.5 创建 `MovieGenreResource`、`MovieKeywordResource`、`MovieProductionCompanyResource`
    - Genre：id/tmdb_id/name/type；Keyword：id/tmdb_id/name；ProductionCompany：id/tmdb_id/name/origin_country/logo_path（w185）
    - _需求：5.4, 6.4, 7.4_

- [x] 7. 创建 Controller 层
  - [x] 7.1 创建 `MovieController`
    - `index(ListMovieRequest)` → `$this->paginate(..., MovieListResource::class)`
    - `show(int $id)` → `$this->success(new MovieResource(...))`
    - _需求：1.2, 2.1_
  - [x] 7.2 创建 `MovieCreditController`、`MovieImageController`
    - 各实现 `index` 方法，调用对应 Service，返回分页响应
    - _需求：3.1, 4.1_
  - [x] 7.3 创建 `MovieGenreController`、`MovieKeywordController`、`MovieProductionCompanyController`
    - 各实现 `index` 方法，调用 Service，使用 `$this->listing()` 返回不分页列表
    - _需求：5.1, 6.1, 7.1_

- [x] 8. 注册路由
  - 在 `routes/api.php` 的 `auth:api` 组内新增全部 7 条路由
  - _需求：1.2, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1_

- [x] 9. 检查点 — 运行代码格式化与基础验证
  - 运行 `./vendor/bin/pint` 确保代码风格通过
  - 确认所有类文件顶部有 `declare(strict_types=1)`
  - 确认所有方法有完整类型声明

- [x] 10. 编写 Feature Test
  - [x] 10.1 创建 `tests/Feature/Movies/MovieListTest.php`
    - 覆盖：未认证返回 401、page>1000 返回 422、per_page>100 返回 422、正常请求返回正确分页结构（mock MovieService）
    - _需求：1.2, 1.3, 1.4, 1.5_
  - [x] 10.2 创建 `tests/Feature/Movies/MovieDetailTest.php`
    - 覆盖：未认证返回 401、电影不存在返回 404、正常请求返回正确结构（mock MovieService）
    - _需求：2.1, 2.2, 2.3_
  - [x] 10.3 创建 `tests/Feature/Movies/MovieCreditListTest.php`
    - 覆盖：未认证返回 401、movie_id 缺失返回 422、person_id 为 null 时 person 字段为 null、正常请求返回正确结构
    - _需求：3.2, 3.3, 3.5_
  - [ ]* 10.4 创建 `tests/Feature/Movies/MovieImageListTest.php`
    - 覆盖：未认证返回 401、movie_id 缺失返回 422、正常请求返回正确结构
    - _需求：4.2, 4.3_
  - [ ]* 10.5 创建 `tests/Feature/Movies/MovieGenreListTest.php`、`MovieKeywordListTest.php`、`MovieProductionCompanyListTest.php`
    - 各覆盖：未认证返回 401、movie_id 缺失返回 422、正常请求返回不分页列表结构
    - _需求：5.2, 5.3, 6.2, 6.3, 7.2, 7.3_

- [x] 11. 最终检查点 — 确保所有测试通过
  - 运行 `php artisan test tests/Feature/Movies/`，确保全部通过
  - 如有问题，排查后修复再提交

## 备注

- 标有 `*` 的子任务为可选项，可跳过以加快 MVP 进度
- 测试全部使用 mock Service 策略，不依赖真实数据库（核心业务表无 migration）
- 图片 URL 统一通过 `ImageHelper::url()` 在 Resource 层拼接
- `movie_credits.person_id` 异步关联，Resource 层必须做 null 安全处理
