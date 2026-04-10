# 实现计划：TV Show 模块

## 概述

按照分层架构（Model → Repository → Service → FormRequest → Resource → Controller → 路由 → 测试）逐步实现 TV Show 完整只读 API，共覆盖三级资源层次（tv_shows → tv_seasons → tv_episodes）及其所有子资源。

## 任务

- [x] 1. 实现 Models
  - [x] 1.1 创建 `app/Models/TvShow.php`
    - 定义 `$table = 'tv_shows'`，`$fillable = []`
    - `$casts` 声明所有非字符串字段：`first_air_date`/`last_air_date` 为 `date`，`adult`/`in_production` 为 `boolean`，`episode_run_time`/`last_episode_to_air`/`next_episode_to_air`/`origin_country_codes`/`spoken_language_codes`/`language_codes`/`production_country_codes` 为 `array`，`popularity`/`vote_average` 为 `float`，`number_of_seasons`/`number_of_episodes`/`vote_count` 为 `integer`
    - _需求：1.13、2.4_

  - [x] 1.2 创建 `app/Models/TvShowCreator.php`
    - 定义 `$table = 'tv_show_creators'`，`public $timestamps = false`，`$fillable = []`
    - 定义 `person()` 关联方法（`belongsTo(Person::class)`），`person_id` 可为 NULL
    - _需求：5.3、5.4_

  - [x] 1.3 创建 `app/Models/TvShowImage.php`
    - 定义 `$table = 'tv_show_images'`，`public $timestamps = false`，`$fillable = []`
    - _需求：4.5_

  - [x] 1.4 创建 `app/Models/TvSeason.php`
    - 定义 `$table = 'tv_seasons'`，`public $timestamps = false`，`$fillable = []`
    - `$casts` 声明：`air_date` 为 `date`，`vote_average` 为 `float`，`episode_count`/`season_number` 为 `integer`
    - _需求：6.6、7.4_

  - [x] 1.5 创建 `app/Models/TvSeasonImage.php`
    - 定义 `$table = 'tv_season_images'`，`public $timestamps = false`，`$fillable = []`
    - _需求：8.4_

  - [x] 1.6 创建 `app/Models/TvEpisode.php`
    - 定义 `$table = 'tv_episodes'`，`public $timestamps = false`，`$fillable = []`
    - `$casts` 声明：`air_date` 为 `date`，`vote_average` 为 `float`，`vote_count`/`runtime`/`season_number`/`episode_number` 为 `integer`
    - _需求：9.6、10.4_

  - [x] 1.7 创建 `app/Models/TvEpisodeCredit.php`
    - 定义 `$table = 'tv_episode_credits'`，`public $timestamps = false`，`$fillable = []`
    - `$casts` 声明：`credit_type` 为 `CreditType::class`（复用已有 Enum）
    - 定义 `person()` 关联方法（`belongsTo(Person::class)`），`person_id` 可为 NULL
    - _需求：11.6、11.7_

  - [x] 1.8 创建 `app/Models/TvEpisodeImage.php`
    - 定义 `$table = 'tv_episode_images'`，`public $timestamps = false`，`$fillable = []`
    - _需求：12.4_


- [x] 2. 实现 Repository Interfaces 与 Implementations（TvShow 子模块）
  - [x] 2.1 创建 `app/Repositories/Contracts/TvShowRepositoryInterface.php`
    - 声明 `paginateWithFilters(array $filters): LengthAwarePaginator`
    - 声明 `findById(int $id): ?TvShow`
    - _需求：1.1、2.1_

  - [x] 2.2 创建 `app/Repositories/TvShowRepository.php`
    - 继承 `BaseRepository`，实现 `TvShowRepositoryInterface`
    - `ALLOWED_SORTS = ['popularity', 'first_air_date', 'vote_average', 'vote_count', 'id']`
    - `paginateWithFilters`：支持 `q`（LIKE q% 匹配 name/original_name）、`genre_id`（JOIN tv_show_genres）、`status`（精确匹配）、`first_air_year`（YEAR(first_air_date)）、`in_production`（布尔）、`sort`/`order`（白名单校验，默认 id DESC）
    - `findById`：直接 `find($id)` 返回单条或 null
    - _需求：1.4、1.5、1.6、1.7、1.8、1.9、1.10、2.2、2.3_

  - [x] 2.3 创建 `app/Repositories/Contracts/TvShowGenreRepositoryInterface.php`
    - 声明 `getByTvShowId(int $tvShowId): Collection`
    - _需求：3.1_

  - [x] 2.4 创建 `app/Repositories/TvShowGenreRepository.php`
    - 继承 `BaseRepository`，实现 `TvShowGenreRepositoryInterface`
    - `getByTvShowId`：JOIN `tv_show_genres` 过滤，返回 Genre 集合
    - _需求：3.1、3.7_

  - [x] 2.5 创建 `app/Repositories/Contracts/TvShowKeywordRepositoryInterface.php` 与 `app/Repositories/TvShowKeywordRepository.php`
    - 接口声明 `getByTvShowId(int $tvShowId): Collection`
    - 实现：JOIN `tv_show_keywords` 过滤，返回 Keyword 集合
    - _需求：3.2、3.8_

  - [x] 2.6 创建 `app/Repositories/Contracts/TvShowNetworkRepositoryInterface.php` 与 `app/Repositories/TvShowNetworkRepository.php`
    - 接口声明 `getByTvShowId(int $tvShowId): Collection`
    - 实现：JOIN `tv_show_networks` 过滤，返回 TvNetwork 集合
    - _需求：3.4、3.10_

  - [x] 2.7 创建 `app/Repositories/Contracts/TvShowProductionCompanyRepositoryInterface.php` 与 `app/Repositories/TvShowProductionCompanyRepository.php`
    - 接口声明 `getByTvShowId(int $tvShowId): Collection`
    - 实现：JOIN `tv_show_production_companies` 过滤，返回 ProductionCompany 集合
    - _需求：3.3、3.9_

  - [x] 2.8 创建 `app/Repositories/Contracts/TvShowImageRepositoryInterface.php` 与 `app/Repositories/TvShowImageRepository.php`
    - 接口声明 `paginateByTvShowId(int $tvShowId, array $filters): LengthAwarePaginator`
    - 实现：强制 WHERE tv_show_id，支持 `image_type` 筛选（白名单：poster/backdrop/logo），支持分页
    - _需求：4.1、4.3、4.4_

  - [x] 2.9 创建 `app/Repositories/Contracts/TvShowCreatorRepositoryInterface.php` 与 `app/Repositories/TvShowCreatorRepository.php`
    - 接口声明 `getByTvShowId(int $tvShowId): Collection`
    - 实现：WHERE tv_show_id，`with('person')` 预加载，person_id 可为 NULL
    - _需求：5.1、5.4、5.5_


- [x] 3. 实现 Repository Interfaces 与 Implementations（TvSeason 子模块）
  - [x] 3.1 创建 `app/Repositories/Contracts/TvSeasonRepositoryInterface.php`
    - 声明 `paginateByTvShowId(int $tvShowId, array $filters): LengthAwarePaginator`
    - 声明 `findById(int $id): ?TvSeason`
    - _需求：6.1、7.1_

  - [x] 3.2 创建 `app/Repositories/TvSeasonRepository.php`
    - 继承 `BaseRepository`，实现 `TvSeasonRepositoryInterface`
    - `ALLOWED_SORTS = ['season_number', 'air_date', 'vote_average', 'id']`
    - `paginateByTvShowId`：强制 WHERE tv_show_id（大表约束：100 万+ 条），支持 sort/order 白名单校验，默认 id ASC
    - `findById`：直接 `find($id)` 返回单条或 null
    - _需求：6.2、6.3、6.4、6.5、7.2、7.3_

  - [x] 3.3 创建 `app/Repositories/Contracts/TvSeasonImageRepositoryInterface.php` 与 `app/Repositories/TvSeasonImageRepository.php`
    - 接口声明 `paginateByTvSeasonId(int $tvSeasonId, array $filters): LengthAwarePaginator`
    - 实现：强制 WHERE tv_season_id，支持分页
    - _需求：8.1、8.2、8.3_

- [x] 4. 实现 Repository Interfaces 与 Implementations（TvEpisode 子模块）
  - [x] 4.1 创建 `app/Repositories/Contracts/TvEpisodeRepositoryInterface.php`
    - 声明 `paginateByTvSeasonId(int $tvSeasonId, array $filters): LengthAwarePaginator`
    - 声明 `findById(int $id): ?TvEpisode`
    - _需求：9.1、10.1_

  - [x] 4.2 创建 `app/Repositories/TvEpisodeRepository.php`
    - 继承 `BaseRepository`，实现 `TvEpisodeRepositoryInterface`
    - `ALLOWED_SORTS = ['episode_number', 'air_date', 'vote_average', 'id']`
    - `paginateByTvSeasonId`：强制 WHERE tv_season_id（大表约束：2000 万+ 条），支持 sort/order 白名单校验，默认 id ASC
    - `findById`：直接 `find($id)` 返回单条或 null
    - _需求：9.2、9.3、9.4、9.5、10.2、10.3_

  - [x] 4.3 创建 `app/Repositories/Contracts/TvEpisodeCreditRepositoryInterface.php` 与 `app/Repositories/TvEpisodeCreditRepository.php`
    - 接口声明 `paginateByTvEpisodeId(int $tvEpisodeId, array $filters): LengthAwarePaginator`
    - 实现：强制 WHERE tv_episode_id（大表约束：极大），`with('person')` 预加载，支持 `credit_type`（cast/crew）筛选，支持分页
    - _需求：11.1、11.2、11.3、11.4、11.5_

  - [x] 4.4 创建 `app/Repositories/Contracts/TvEpisodeImageRepositoryInterface.php` 与 `app/Repositories/TvEpisodeImageRepository.php`
    - 接口声明 `paginateByTvEpisodeId(int $tvEpisodeId, array $filters): LengthAwarePaginator`
    - 实现：强制 WHERE tv_episode_id，支持分页
    - _需求：12.1、12.2、12.3_


- [x] 5. 在 AppServiceProvider 注册 Repository 绑定
  - 在 `app/Providers/AppServiceProvider.php` 的 `register()` 方法中新增 12 个绑定
  - TvShow 子模块：`TvShowRepositoryInterface`、`TvShowGenreRepositoryInterface`、`TvShowKeywordRepositoryInterface`、`TvShowNetworkRepositoryInterface`、`TvShowProductionCompanyRepositoryInterface`、`TvShowImageRepositoryInterface`、`TvShowCreatorRepositoryInterface`
  - TvSeason 子模块：`TvSeasonRepositoryInterface`、`TvSeasonImageRepositoryInterface`
  - TvEpisode 子模块：`TvEpisodeRepositoryInterface`、`TvEpisodeCreditRepositoryInterface`、`TvEpisodeImageRepositoryInterface`
  - _需求：13.1（所有接口可正常解析依赖）_

- [x] 6. 实现 Services
  - [x] 6.1 创建 `app/Services/TvShowService.php`
    - 注入 `TvShowRepositoryInterface`
    - `getList(array $filters): LengthAwarePaginator`：直接委托 Repository
    - `findById(int $id): TvShow`：调用 Repository，不存在时 `throw new AppException('电视剧不存在', 404)`
    - _需求：1.1、1.2、1.3、2.1、2.2、2.3_

  - [x] 6.2 创建 `app/Services/TvShowGenreService.php`、`TvShowKeywordService.php`、`TvShowNetworkService.php`、`TvShowProductionCompanyService.php`
    - 各自注入对应 Repository Interface
    - 各自实现 `getList(int $tvShowId): Collection`，直接委托 Repository
    - _需求：3.1、3.2、3.3、3.4_

  - [x] 6.3 创建 `app/Services/TvShowImageService.php`
    - 注入 `TvShowImageRepositoryInterface`
    - `getList(int $tvShowId, array $filters): LengthAwarePaginator`，直接委托 Repository
    - _需求：4.1_

  - [x] 6.4 创建 `app/Services/TvShowCreatorService.php`
    - 注入 `TvShowCreatorRepositoryInterface`
    - `getList(int $tvShowId): Collection`，直接委托 Repository
    - _需求：5.1_

  - [x] 6.5 创建 `app/Services/TvSeasonService.php`
    - 注入 `TvSeasonRepositoryInterface`
    - `getList(int $tvShowId, array $filters): LengthAwarePaginator`
    - `findById(int $id): TvSeason`：不存在时 `throw new AppException('季不存在', 404)`
    - _需求：6.1、7.1、7.2、7.3_

  - [x] 6.6 创建 `app/Services/TvSeasonImageService.php`
    - 注入 `TvSeasonImageRepositoryInterface`
    - `getList(int $tvSeasonId, array $filters): LengthAwarePaginator`
    - _需求：8.1_

  - [x] 6.7 创建 `app/Services/TvEpisodeService.php`
    - 注入 `TvEpisodeRepositoryInterface`
    - `getList(int $tvSeasonId, array $filters): LengthAwarePaginator`
    - `findById(int $id): TvEpisode`：不存在时 `throw new AppException('集不存在', 404)`
    - _需求：9.1、10.1、10.2、10.3_

  - [x] 6.8 创建 `app/Services/TvEpisodeCreditService.php`
    - 注入 `TvEpisodeCreditRepositoryInterface`
    - `getList(int $tvEpisodeId, array $filters): LengthAwarePaginator`
    - _需求：11.1_

  - [x] 6.9 创建 `app/Services/TvEpisodeImageService.php`
    - 注入 `TvEpisodeImageRepositoryInterface`
    - `getList(int $tvEpisodeId, array $filters): LengthAwarePaginator`
    - _需求：12.1_


- [x] 7. 实现 FormRequests
  - [x] 7.1 创建 `app/Http/Requests/ListTvShowRequest.php`
    - 可选参数：`q`（string, max:100）、`genre_id`（integer）、`status`（string）、`first_air_year`（integer, 4位）、`in_production`（boolean, 0/1）
    - `sort`：`Rule::in(['popularity','first_air_date','vote_average','vote_count','id'])`
    - `order`：`Rule::in(['asc','desc'])`
    - `page`：integer, min:1, max:1000；`per_page`：integer, min:1, max:100
    - _需求：1.9、1.10、1.11、1.12_

  - [x] 7.2 创建 `app/Http/Requests/ListTvShowGenreRequest.php`、`ListTvShowKeywordRequest.php`、`ListTvShowNetworkRequest.php`、`ListTvShowProductionCompanyRequest.php`、`ListTvShowCreatorRequest.php`
    - 必填参数：`tv_show_id`（required, integer）
    - _需求：3.5_

  - [x] 7.3 创建 `app/Http/Requests/ListTvShowImageRequest.php`
    - 必填：`tv_show_id`（required, integer）
    - 可选：`image_type`（`Rule::in(['poster','backdrop','logo'])`）、`page`（max:1000）、`per_page`（max:100）
    - _需求：4.2、4.3、4.4_

  - [x] 7.4 创建 `app/Http/Requests/ListTvSeasonRequest.php`
    - 必填：`tv_show_id`（required, integer）
    - 可选：`sort`（`Rule::in(['season_number','air_date','vote_average','id'])`）、`order`、`page`（max:1000）、`per_page`（max:100）
    - _需求：6.2、6.4、6.5_

  - [x] 7.5 创建 `app/Http/Requests/ListTvSeasonImageRequest.php`
    - 必填：`tv_season_id`（required, integer）
    - 可选：`page`（max:1000）、`per_page`（max:100）
    - _需求：8.2、8.3_

  - [x] 7.6 创建 `app/Http/Requests/ListTvEpisodeRequest.php`
    - 必填：`tv_season_id`（required, integer）
    - 可选：`sort`（`Rule::in(['episode_number','air_date','vote_average','id'])`）、`order`、`page`（max:1000）、`per_page`（max:100）
    - _需求：9.2、9.4、9.5_

  - [x] 7.7 创建 `app/Http/Requests/ListTvEpisodeCreditRequest.php`
    - 必填：`tv_episode_id`（required, integer）
    - 可选：`credit_type`（`Rule::in(['cast','crew'])`）、`page`（max:1000）、`per_page`（max:100）
    - _需求：11.2、11.4、11.5_

  - [x] 7.8 创建 `app/Http/Requests/ListTvEpisodeImageRequest.php`
    - 必填：`tv_episode_id`（required, integer）
    - 可选：`page`（max:1000）、`per_page`（max:100）
    - _需求：12.2、12.3_


- [x] 8. 实现 API Resources
  - [x] 8.1 创建 `app/Http/Resources/TvShowListResource.php`
    - 输出字段：`id`、`tmdb_id`、`name`、`original_name`、`original_language`、`status`、`first_air_date`、`number_of_seasons`、`number_of_episodes`、`in_production`、`popularity`、`vote_average`、`vote_count`、`adult`
    - `poster_path`：`ImageHelper::url($this->poster_path, 'w342')`
    - `backdrop_path`：`ImageHelper::url($this->backdrop_path, 'w780')`
    - _需求：1.13、1.14_

  - [x] 8.2 创建 `app/Http/Resources/TvShowResource.php`
    - 输出所有详情字段：`id`、`tmdb_id`、`name`、`original_name`、`original_language`、`overview`、`tagline`、`status`、`type`、`first_air_date`、`last_air_date`、`number_of_seasons`、`number_of_episodes`、`episode_run_time`、`popularity`、`vote_average`、`vote_count`、`adult`、`in_production`、`homepage`、`origin_country_codes`、`spoken_language_codes`、`language_codes`、`production_country_codes`、`last_episode_to_air`、`next_episode_to_air`、`created_at`、`updated_at`
    - `poster_path`：`ImageHelper::url($this->poster_path, 'w500')`
    - `backdrop_path`：`ImageHelper::url($this->backdrop_path, 'original')`
    - _需求：2.4、2.5_

  - [x] 8.3 创建 `app/Http/Resources/TvShowGenreResource.php`
    - 输出：`id`、`tmdb_id`、`name`、`type`
    - _需求：3.7_

  - [x] 8.4 创建 `app/Http/Resources/TvShowKeywordResource.php`
    - 输出：`id`、`tmdb_id`、`name`
    - _需求：3.8_

  - [x] 8.5 创建 `app/Http/Resources/TvShowNetworkResource.php`
    - 输出：`id`、`tmdb_id`、`name`、`origin_country`
    - `logo_path`：`ImageHelper::url($this->logo_path, 'w185')`
    - _需求：3.10_

  - [x] 8.6 创建 `app/Http/Resources/TvShowProductionCompanyResource.php`
    - 输出：`id`、`tmdb_id`、`name`、`origin_country`
    - `logo_path`：`ImageHelper::url($this->logo_path, 'w185')`
    - _需求：3.9_

  - [x] 8.7 创建 `app/Http/Resources/TvShowImageResource.php`
    - 输出：`id`、`tv_show_id`、`image_type`、`width`、`height`、`vote_average`、`vote_count`
    - `file_path`：backdrop 类型用 `w780`，其他用 `w342`（`$this->image_type === 'backdrop' ? 'w780' : 'w342'`）
    - _需求：4.5、4.6_

  - [x] 8.8 创建 `app/Http/Resources/TvShowCreatorResource.php`
    - 输出：`tv_show_id`、`person_tmdb_id`、`person_id`
    - `person` 字段：`person_id` 不为 NULL 且 person 已加载时输出 `id`/`tmdb_id`/`name`/`profile_path`（w185），否则输出 `null`
    - _需求：5.3、5.4、5.5_

  - [x] 8.9 创建 `app/Http/Resources/TvSeasonListResource.php`
    - 输出：`id`、`tv_show_id`、`tmdb_id`、`season_number`、`name`、`air_date`、`episode_count`、`vote_average`
    - `poster_path`：`ImageHelper::url($this->poster_path, 'w342')`
    - _需求：6.6、6.7_

  - [x] 8.10 创建 `app/Http/Resources/TvSeasonResource.php`
    - 输出：`id`、`tv_show_id`、`tmdb_id`、`season_number`、`name`、`overview`、`air_date`、`episode_count`、`vote_average`
    - `poster_path`：`ImageHelper::url($this->poster_path, 'w500')`
    - _需求：7.4、7.5_

  - [x] 8.11 创建 `app/Http/Resources/TvSeasonImageResource.php`
    - 输出：`id`、`tv_season_id`、`image_type`、`width`、`height`、`vote_average`、`vote_count`
    - `file_path`：`ImageHelper::url($this->file_path, 'w342')`
    - _需求：8.4、8.5_

  - [x] 8.12 创建 `app/Http/Resources/TvEpisodeListResource.php`
    - 输出：`id`、`tv_show_id`、`tv_season_id`、`tmdb_id`、`season_number`、`episode_number`、`episode_type`、`name`、`air_date`、`runtime`、`vote_average`、`vote_count`
    - `still_path`：`ImageHelper::url($this->still_path, 'w300')`
    - _需求：9.6、9.7_

  - [x] 8.13 创建 `app/Http/Resources/TvEpisodeResource.php`
    - 输出：`id`、`tv_show_id`、`tv_season_id`、`tmdb_id`、`season_number`、`episode_number`、`episode_type`、`production_code`、`name`、`overview`、`air_date`、`runtime`、`vote_average`、`vote_count`
    - `still_path`：`ImageHelper::url($this->still_path, 'w780')`
    - _需求：10.4、10.5_

  - [x] 8.14 创建 `app/Http/Resources/TvEpisodeCreditResource.php`
    - 输出：`id`、`tv_episode_id`、`person_tmdb_id`、`person_id`、`credit_type`、`character`、`cast_order`、`department_id`、`job_id`
    - `person` 字段：`person_id` 不为 NULL 且 person 已加载时输出 `id`/`tmdb_id`/`name`/`profile_path`（w185），否则输出 `null`
    - _需求：11.6、11.7、11.8_

  - [x] 8.15 创建 `app/Http/Resources/TvEpisodeImageResource.php`
    - 输出：`id`、`tv_episode_id`、`image_type`、`width`、`height`、`vote_average`、`vote_count`
    - `file_path`：`ImageHelper::url($this->file_path, 'w300')`
    - _需求：12.4、12.5_


- [x] 9. 实现 Controllers
  - [x] 9.1 创建 `app/Http/Controllers/Api/TvShowController.php`
    - 继承 `BaseController`，注入 `TvShowService`
    - `index(ListTvShowRequest $request)`：调用 `getList($request->validated())`，返回 `$this->paginate(..., TvShowListResource::class)`
    - `show(int $id)`：调用 `findById($id)`，返回 `$this->success(new TvShowResource(...))`
    - _需求：1.1、1.2、1.3、2.1、2.2、2.3_

  - [x] 9.2 创建 `app/Http/Controllers/Api/TvShowGenreController.php`
    - 注入 `TvShowGenreService`
    - `index(ListTvShowGenreRequest $request)`：返回 `$this->listing(TvShowGenreResource::collection(...))`
    - _需求：3.1、3.6_

  - [x] 9.3 创建 `app/Http/Controllers/Api/TvShowKeywordController.php`
    - 注入 `TvShowKeywordService`
    - `index(ListTvShowKeywordRequest $request)`：返回 `$this->listing(...)`
    - _需求：3.2、3.6_

  - [x] 9.4 创建 `app/Http/Controllers/Api/TvShowNetworkController.php`
    - 注入 `TvShowNetworkService`
    - `index(ListTvShowNetworkRequest $request)`：返回 `$this->listing(...)`
    - _需求：3.4、3.6_

  - [x] 9.5 创建 `app/Http/Controllers/Api/TvShowProductionCompanyController.php`
    - 注入 `TvShowProductionCompanyService`
    - `index(ListTvShowProductionCompanyRequest $request)`：返回 `$this->listing(...)`
    - _需求：3.3、3.6_

  - [x] 9.6 创建 `app/Http/Controllers/Api/TvShowImageController.php`
    - 注入 `TvShowImageService`
    - `index(ListTvShowImageRequest $request)`：返回 `$this->paginate(..., TvShowImageResource::class)`
    - _需求：4.1_

  - [x] 9.7 创建 `app/Http/Controllers/Api/TvShowCreatorController.php`
    - 注入 `TvShowCreatorService`
    - `index(ListTvShowCreatorRequest $request)`：返回 `$this->listing(...)`
    - _需求：5.1_

  - [x] 9.8 创建 `app/Http/Controllers/Api/TvSeasonController.php`
    - 注入 `TvSeasonService`
    - `index(ListTvSeasonRequest $request)`：返回 `$this->paginate(..., TvSeasonListResource::class)`
    - `show(int $id)`：返回 `$this->success(new TvSeasonResource(...))`
    - _需求：6.1、7.1、7.2、7.3_

  - [x] 9.9 创建 `app/Http/Controllers/Api/TvSeasonImageController.php`
    - 注入 `TvSeasonImageService`
    - `index(ListTvSeasonImageRequest $request)`：返回 `$this->paginate(..., TvSeasonImageResource::class)`
    - _需求：8.1_

  - [x] 9.10 创建 `app/Http/Controllers/Api/TvEpisodeController.php`
    - 注入 `TvEpisodeService`
    - `index(ListTvEpisodeRequest $request)`：返回 `$this->paginate(..., TvEpisodeListResource::class)`
    - `show(int $id)`：返回 `$this->success(new TvEpisodeResource(...))`
    - _需求：9.1、10.1、10.2、10.3_

  - [x] 9.11 创建 `app/Http/Controllers/Api/TvEpisodeCreditController.php`
    - 注入 `TvEpisodeCreditService`
    - `index(ListTvEpisodeCreditRequest $request)`：返回 `$this->paginate(..., TvEpisodeCreditResource::class)`
    - _需求：11.1_

  - [x] 9.12 创建 `app/Http/Controllers/Api/TvEpisodeImageController.php`
    - 注入 `TvEpisodeImageService`
    - `index(ListTvEpisodeImageRequest $request)`：返回 `$this->paginate(..., TvEpisodeImageResource::class)`
    - _需求：12.1_

- [x] 10. 注册路由
  - 在 `routes/api.php` 的 `auth:api` middleware 组内追加 15 条路由
  - TvShow 主资源：`GET tv-shows`、`GET tv-shows/{id}`
  - TvShow 子资源（全量）：`GET tv-show-genres`、`GET tv-show-keywords`、`GET tv-show-networks`、`GET tv-show-production-companies`、`GET tv-show-creators`
  - TvShow 子资源（分页）：`GET tv-show-images`
  - TvSeason：`GET tv-seasons`、`GET tv-seasons/{id}`、`GET tv-season-images`
  - TvEpisode：`GET tv-episodes`、`GET tv-episodes/{id}`、`GET tv-episode-credits`、`GET tv-episode-images`
  - _需求：13.1、13.2_

- [x] 11. 检查点 — 确保所有路由可访问
  - 运行 `php artisan route:list --path=tv` 确认 15 条路由已注册
  - 运行 `./vendor/bin/pint` 确保代码格式符合规范
  - 如有问题，请向用户说明。


- [x] 12. 实现 Feature Tests
  - [x] 12.1 创建 `tests/Feature/TvShows/TvShowListTest.php`
    - 使用 mock `TvShowService`，不依赖真实数据库
    - 测试场景：未认证返回 401、正常请求返回 `code:0` 及分页结构、非法 sort 值返回 422、`page > 1000` 返回 422
    - _需求：1.1、1.2、1.3、1.9、1.12、13.2_

  - [x] 12.2 创建 `tests/Feature/TvShows/TvShowDetailTest.php`
    - 使用 mock `TvShowService`
    - 测试场景：存在的 id 返回 `code:0` 及完整字段结构、不存在的 id 返回 `code:404`
    - _需求：2.1、2.2、2.3_

  - [x] 12.3 创建 `tests/Feature/TvShows/TvShowSubResourceTest.php`
    - 使用 mock 各子资源 Service
    - 测试场景：缺少 `tv_show_id` 返回 422（genres/keywords/networks/companies/images/creators 各接口）、正常请求返回 `code:0`
    - 测试 `TvShowCreatorService` 返回含 `person_id = null` 记录时，响应中 `person` 字段为 `null` 且记录不被过滤
    - _需求：3.5、4.2、5.2、5.4_

  - [x] 12.4 创建 `tests/Feature/TvShows/TvSeasonListTest.php`
    - 使用 mock `TvSeasonService`
    - 测试场景：缺少 `tv_show_id` 返回 422、正常请求返回分页结构、非法 sort 返回 422、`page > 1000` 返回 422
    - _需求：6.2、6.4、6.5_

  - [x] 12.5 创建 `tests/Feature/TvShows/TvSeasonDetailTest.php`
    - 使用 mock `TvSeasonService`
    - 测试场景：存在的 id 返回 `code:0`、不存在的 id 返回 `code:404`
    - _需求：7.2、7.3_

  - [x] 12.6 创建 `tests/Feature/TvShows/TvSeasonImageTest.php`
    - 使用 mock `TvSeasonImageService`
    - 测试场景：缺少 `tv_season_id` 返回 422、正常请求返回分页结构
    - _需求：8.2_

  - [x] 12.7 创建 `tests/Feature/TvShows/TvEpisodeListTest.php`
    - 使用 mock `TvEpisodeService`
    - 测试场景：缺少 `tv_season_id` 返回 422、正常请求返回分页结构、非法 sort 返回 422、`page > 1000` 返回 422
    - _需求：9.2、9.4、9.5_

  - [x] 12.8 创建 `tests/Feature/TvShows/TvEpisodeDetailTest.php`
    - 使用 mock `TvEpisodeService`
    - 测试场景：存在的 id 返回 `code:0`、不存在的 id 返回 `code:404`
    - _需求：10.2、10.3_

  - [x] 12.9 创建 `tests/Feature/TvShows/TvEpisodeCreditTest.php`
    - 使用 mock `TvEpisodeCreditService`
    - 测试场景：缺少 `tv_episode_id` 返回 422、非法 `credit_type` 返回 422、正常请求返回分页结构
    - 测试含 `person_id = null` 记录时 `person` 字段为 `null` 且记录不被过滤（null 安全）
    - _需求：11.2、11.4、11.7_

  - [x] 12.10 创建 `tests/Feature/TvShows/TvEpisodeImageTest.php`
    - 使用 mock `TvEpisodeImageService`
    - 测试场景：缺少 `tv_episode_id` 返回 422、正常请求返回分页结构
    - _需求：12.2_

- [x] 13. 最终检查点 — 确保所有测试通过
  - 运行 `php artisan test tests/Feature/TvShows/` 确认全部测试通过
  - 如有失败，请向用户说明。

## 备注

- 标注 `*` 的子任务为可选项，可跳过以加快 MVP 进度
- 大表约束（tv_show_id / tv_season_id / tv_episode_id 必填）通过 FormRequest `required` 规则在请求层拦截，Repository 方法签名提供额外类型保障
- 异步关联 null 安全处理统一在 Resource 层实现，不在 Service/Repository 层过滤
- 所有 Feature Test 使用 mock Service，不依赖真实数据库（遵循 `testing-strategy.md`）
