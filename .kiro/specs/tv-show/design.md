# TV Show 模块设计文档

## 概述

本模块为 Filmly Management Backend 实现完整的电视剧（TV Show）只读 API，覆盖电视剧主资源、季、集及其所有关联子资源。

TV Show 模块与已实现的 Movie 模块在架构上高度对称，遵循相同的分层架构（Route → FormRequest → Controller → Service → Repository → Model），但数据结构更复杂：

- **三级层次结构**：`tv_shows` → `tv_seasons` → `tv_episodes`
- **演职人员挂载在集级别**：`tv_episode_credits`（非 show 级别）
- **特有关联**：`tv_show_creators`（异步关联）、`tv_show_networks`
- **大表约束**：`tv_seasons`（100 万+）必须带 `tv_show_id`，`tv_episodes`（2000 万+）必须带 `tv_season_id`，`tv_episode_credits`（极大）必须带 `tv_episode_id`
- **特有字段**：`name`/`original_name`（非 title）、`first_air_date`、`in_production`、`episode_run_time`（json）、`last_episode_to_air`（json）、`next_episode_to_air`（json）

所有接口均为只读（GET），需要 `auth:api` 认证，遵循项目统一的信封响应格式。

## 架构

TV Show 模块严格遵循项目分层架构，与 Movie 模块对称：

```
Request
  └── routes/api.php（auth:api middleware）
        └── FormRequest（参数验证，必填项/白名单/范围）
              └── Controller（接收请求，调用 Service，返回响应）
                    └── Service（业务逻辑，抛出 AppException）
                          └── Repository（数据访问，封装 Eloquent 查询）
                                └── Model（Eloquent，定义关联关系）
```

### 模块划分

TV Show 模块按资源层级划分为三个子模块，每个子模块独立实现完整的分层：

```
TvShow 子模块     → tv_shows 主表 + 所有 show 级别子资源
TvSeason 子模块   → tv_seasons 表 + 季图片
TvEpisode 子模块  → tv_episodes 表 + 集演职人员 + 集图片
```

### 数据流图

```
GET /api/tv-shows
  → ListTvShowRequest（验证 q/genre_id/status/first_air_year/in_production/adult/sort/order/page/per_page）
  → TvShowController::index()
  → TvShowService::getList(filters)
  → TvShowRepository::paginateWithFilters(filters)
  → TvShow Model（tv_shows 表）
  → TvShowListResource（输出 w342/w780 图片）

GET /api/tv-shows/{id}
  → TvShowController::show(id)
  → TvShowService::findById(id)  [不存在时抛出 AppException 404]
  → TvShowRepository::findById(id)
  → TvShow Model
  → TvShowResource（输出 w500/original 图片）

GET /api/tv-show-genres?tv_show_id=
  → ListTvShowGenreRequest（tv_show_id 必填）
  → TvShowGenreController::index()
  → TvShowGenreService::getList(tvShowId)
  → TvShowGenreRepository::getByTvShowId(tvShowId)
  → Genre Model（JOIN tv_show_genres）
  → TvShowGenreResource

GET /api/tv-show-creators?tv_show_id=
  → ListTvShowCreatorRequest（tv_show_id 必填）
  → TvShowCreatorController::index()
  → TvShowCreatorService::getList(tvShowId)
  → TvShowCreatorRepository::getByTvShowId(tvShowId)  [with('person')]
  → TvShowCreator Model（异步关联，person_id 可 NULL）
  → TvShowCreatorResource（person 为 null 时安全输出）

GET /api/tv-seasons?tv_show_id=
  → ListTvSeasonRequest（tv_show_id 必填，大表约束）
  → TvSeasonController::index()
  → TvSeasonService::getList(tvShowId, filters)
  → TvSeasonRepository::paginateByTvShowId(tvShowId, filters)  [强制 WHERE tv_show_id]
  → TvSeason Model
  → TvSeasonListResource（w342 图片）

GET /api/tv-episodes?tv_season_id=
  → ListTvEpisodeRequest（tv_season_id 必填，大表约束）
  → TvEpisodeController::index()
  → TvEpisodeService::getList(tvSeasonId, filters)
  → TvEpisodeRepository::paginateByTvSeasonId(tvSeasonId, filters)  [强制 WHERE tv_season_id]
  → TvEpisode Model
  → TvEpisodeListResource（w300 图片）

GET /api/tv-episode-credits?tv_episode_id=
  → ListTvEpisodeCreditRequest（tv_episode_id 必填，大表约束）
  → TvEpisodeCreditController::index()
  → TvEpisodeCreditService::getList(tvEpisodeId, filters)
  → TvEpisodeCreditRepository::paginateByTvEpisodeId(tvEpisodeId, filters)  [强制 WHERE tv_episode_id, with('person')]
  → TvEpisodeCredit Model（异步关联，person_id 可 NULL）
  → TvEpisodeCreditResource（person null 安全）
```

## 组件与接口

### 文件清单

#### Models（6 个）

| 文件 | 说明 |
|------|------|
| `app/Models/TvShow.php` | 电视剧主模型，`$casts` 含 json/boolean/date 字段 |
| `app/Models/TvShowCreator.php` | 创作者模型，`person_id` 可 NULL |
| `app/Models/TvShowImage.php` | 电视剧图片模型 |
| `app/Models/TvSeason.php` | 季模型 |
| `app/Models/TvSeasonImage.php` | 季图片模型 |
| `app/Models/TvEpisode.php` | 集模型 |
| `app/Models/TvEpisodeCredit.php` | 集演职人员模型，`person_id` 可 NULL |
| `app/Models/TvEpisodeImage.php` | 集图片模型 |

#### Repository Interfaces（10 个）

| 文件 | 关键方法 |
|------|---------|
| `app/Repositories/Contracts/TvShowRepositoryInterface.php` | `paginateWithFilters(array $filters)`, `findById(int $id)` |
| `app/Repositories/Contracts/TvShowGenreRepositoryInterface.php` | `getByTvShowId(int $tvShowId)` |
| `app/Repositories/Contracts/TvShowKeywordRepositoryInterface.php` | `getByTvShowId(int $tvShowId)` |
| `app/Repositories/Contracts/TvShowNetworkRepositoryInterface.php` | `getByTvShowId(int $tvShowId)` |
| `app/Repositories/Contracts/TvShowProductionCompanyRepositoryInterface.php` | `getByTvShowId(int $tvShowId)` |
| `app/Repositories/Contracts/TvShowImageRepositoryInterface.php` | `paginateByTvShowId(int $tvShowId, array $filters)` |
| `app/Repositories/Contracts/TvShowCreatorRepositoryInterface.php` | `getByTvShowId(int $tvShowId)` |
| `app/Repositories/Contracts/TvSeasonRepositoryInterface.php` | `paginateByTvShowId(int $tvShowId, array $filters)`, `findById(int $id)` |
| `app/Repositories/Contracts/TvSeasonImageRepositoryInterface.php` | `paginateByTvSeasonId(int $tvSeasonId, array $filters)` |
| `app/Repositories/Contracts/TvEpisodeRepositoryInterface.php` | `paginateByTvSeasonId(int $tvSeasonId, array $filters)`, `findById(int $id)` |
| `app/Repositories/Contracts/TvEpisodeCreditRepositoryInterface.php` | `paginateByTvEpisodeId(int $tvEpisodeId, array $filters)` |
| `app/Repositories/Contracts/TvEpisodeImageRepositoryInterface.php` | `paginateByTvEpisodeId(int $tvEpisodeId, array $filters)` |

#### Repository Implementations（10 个）

| 文件 | 继承 | 说明 |
|------|------|------|
| `app/Repositories/TvShowRepository.php` | `BaseRepository` | 带筛选分页，排序白名单 |
| `app/Repositories/TvShowGenreRepository.php` | `BaseRepository` | JOIN tv_show_genres |
| `app/Repositories/TvShowKeywordRepository.php` | `BaseRepository` | JOIN tv_show_keywords |
| `app/Repositories/TvShowNetworkRepository.php` | `BaseRepository` | JOIN tv_show_networks |
| `app/Repositories/TvShowProductionCompanyRepository.php` | `BaseRepository` | JOIN tv_show_production_companies |
| `app/Repositories/TvShowImageRepository.php` | `BaseRepository` | 可按 image_type 筛选 |
| `app/Repositories/TvShowCreatorRepository.php` | `BaseRepository` | with('person')，person_id 可 NULL |
| `app/Repositories/TvSeasonRepository.php` | `BaseRepository` | 强制 WHERE tv_show_id（大表约束） |
| `app/Repositories/TvSeasonImageRepository.php` | `BaseRepository` | 强制 WHERE tv_season_id |
| `app/Repositories/TvEpisodeRepository.php` | `BaseRepository` | 强制 WHERE tv_season_id（大表约束） |
| `app/Repositories/TvEpisodeCreditRepository.php` | `BaseRepository` | 强制 WHERE tv_episode_id（大表约束），with('person') |
| `app/Repositories/TvEpisodeImageRepository.php` | `BaseRepository` | 强制 WHERE tv_episode_id |

#### Services（10 个）

| 文件 | 关键方法 |
|------|---------|
| `app/Services/TvShowService.php` | `getList(array $filters): LengthAwarePaginator`, `findById(int $id): TvShow` |
| `app/Services/TvShowGenreService.php` | `getList(int $tvShowId): Collection` |
| `app/Services/TvShowKeywordService.php` | `getList(int $tvShowId): Collection` |
| `app/Services/TvShowNetworkService.php` | `getList(int $tvShowId): Collection` |
| `app/Services/TvShowProductionCompanyService.php` | `getList(int $tvShowId): Collection` |
| `app/Services/TvShowImageService.php` | `getList(int $tvShowId, array $filters): LengthAwarePaginator` |
| `app/Services/TvShowCreatorService.php` | `getList(int $tvShowId): Collection` |
| `app/Services/TvSeasonService.php` | `getList(int $tvShowId, array $filters): LengthAwarePaginator`, `findById(int $id): TvSeason` |
| `app/Services/TvSeasonImageService.php` | `getList(int $tvSeasonId, array $filters): LengthAwarePaginator` |
| `app/Services/TvEpisodeService.php` | `getList(int $tvSeasonId, array $filters): LengthAwarePaginator`, `findById(int $id): TvEpisode` |
| `app/Services/TvEpisodeCreditService.php` | `getList(int $tvEpisodeId, array $filters): LengthAwarePaginator` |
| `app/Services/TvEpisodeImageService.php` | `getList(int $tvEpisodeId, array $filters): LengthAwarePaginator` |

#### FormRequests（12 个）

| 文件 | 必填参数 | 可选参数 |
|------|---------|---------|
| `app/Http/Requests/ListTvShowRequest.php` | — | q, genre_id, status, first_air_year, in_production, adult, sort, order, page, per_page |
| `app/Http/Requests/ListTvShowGenreRequest.php` | tv_show_id | — |
| `app/Http/Requests/ListTvShowKeywordRequest.php` | tv_show_id | — |
| `app/Http/Requests/ListTvShowNetworkRequest.php` | tv_show_id | — |
| `app/Http/Requests/ListTvShowProductionCompanyRequest.php` | tv_show_id | — |
| `app/Http/Requests/ListTvShowImageRequest.php` | tv_show_id | image_type, page, per_page |
| `app/Http/Requests/ListTvShowCreatorRequest.php` | tv_show_id | — |
| `app/Http/Requests/ListTvSeasonRequest.php` | tv_show_id | sort, order, page, per_page |
| `app/Http/Requests/ListTvSeasonImageRequest.php` | tv_season_id | page, per_page |
| `app/Http/Requests/ListTvEpisodeRequest.php` | tv_season_id | sort, order, page, per_page |
| `app/Http/Requests/ListTvEpisodeCreditRequest.php` | tv_episode_id | credit_type, page, per_page |
| `app/Http/Requests/ListTvEpisodeImageRequest.php` | tv_episode_id | page, per_page |

#### API Resources（12 个）

| 文件 | 对应接口 | 图片尺寸 |
|------|---------|---------|
| `app/Http/Resources/TvShowListResource.php` | 列表 | poster: w342, backdrop: w780 |
| `app/Http/Resources/TvShowResource.php` | 详情 | poster: w500, backdrop: original |
| `app/Http/Resources/TvShowGenreResource.php` | 类型子资源 | — |
| `app/Http/Resources/TvShowKeywordResource.php` | 关键词子资源 | — |
| `app/Http/Resources/TvShowNetworkResource.php` | 电视网络子资源 | logo: w185 |
| `app/Http/Resources/TvShowProductionCompanyResource.php` | 制作公司子资源 | logo: w185 |
| `app/Http/Resources/TvShowImageResource.php` | 图片子资源 | backdrop: w780, 其他: w342 |
| `app/Http/Resources/TvShowCreatorResource.php` | 创作者子资源 | person.profile: w185 |
| `app/Http/Resources/TvSeasonListResource.php` | 季列表 | poster: w342 |
| `app/Http/Resources/TvSeasonResource.php` | 季详情 | poster: w500 |
| `app/Http/Resources/TvSeasonImageResource.php` | 季图片 | file_path: w342 |
| `app/Http/Resources/TvEpisodeListResource.php` | 集列表 | still: w300 |
| `app/Http/Resources/TvEpisodeResource.php` | 集详情 | still: w780 |
| `app/Http/Resources/TvEpisodeCreditResource.php` | 集演职人员 | person.profile: w185 |
| `app/Http/Resources/TvEpisodeImageResource.php` | 集图片 | file_path: w300 |

#### Controllers（10 个）

| 文件 | 方法 | 返回类型 |
|------|------|---------|
| `app/Http/Controllers/Api/TvShowController.php` | `index(ListTvShowRequest)`, `show(int $id)` | paginate / success |
| `app/Http/Controllers/Api/TvShowGenreController.php` | `index(ListTvShowGenreRequest)` | listing |
| `app/Http/Controllers/Api/TvShowKeywordController.php` | `index(ListTvShowKeywordRequest)` | listing |
| `app/Http/Controllers/Api/TvShowNetworkController.php` | `index(ListTvShowNetworkRequest)` | listing |
| `app/Http/Controllers/Api/TvShowProductionCompanyController.php` | `index(ListTvShowProductionCompanyRequest)` | listing |
| `app/Http/Controllers/Api/TvShowImageController.php` | `index(ListTvShowImageRequest)` | paginate |
| `app/Http/Controllers/Api/TvShowCreatorController.php` | `index(ListTvShowCreatorRequest)` | listing |
| `app/Http/Controllers/Api/TvSeasonController.php` | `index(ListTvSeasonRequest)`, `show(int $id)` | paginate / success |
| `app/Http/Controllers/Api/TvSeasonImageController.php` | `index(ListTvSeasonImageRequest)` | paginate |
| `app/Http/Controllers/Api/TvEpisodeController.php` | `index(ListTvEpisodeRequest)`, `show(int $id)` | paginate / success |
| `app/Http/Controllers/Api/TvEpisodeCreditController.php` | `index(ListTvEpisodeCreditRequest)` | paginate |
| `app/Http/Controllers/Api/TvEpisodeImageController.php` | `index(ListTvEpisodeImageRequest)` | paginate |

### 关键类方法签名

#### TvShowRepository

```php
class TvShowRepository extends BaseRepository implements TvShowRepositoryInterface
{
    private const ALLOWED_SORTS = ['popularity', 'first_air_date', 'vote_average', 'vote_count', 'id'];

    /**
     * Paginate tv shows with optional filters.
     * q: prefix match on name and original_name (LIKE q%).
     * genre_id: JOIN tv_show_genres to filter by genre.
     * status: exact match on status field.
     * first_air_year: YEAR(first_air_date) = ?.
     * in_production: exact match on in_production boolean field.
     * sort/order: whitelist-validated, default id DESC.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator;

    /**
     * Find a tv show by its local id. Returns null when not found.
     */
    public function findById(int $id): ?TvShow;
}
```

#### TvSeasonRepository

```php
class TvSeasonRepository extends BaseRepository implements TvSeasonRepositoryInterface
{
    private const ALLOWED_SORTS = ['season_number', 'air_date', 'vote_average', 'id'];

    /**
     * Paginate seasons for a given tv show.
     * Large table constraint (1M+ rows): tv_show_id is REQUIRED to prevent full-table scans.
     * sort/order: whitelist-validated, default id ASC.
     */
    public function paginateByTvShowId(int $tvShowId, array $filters): LengthAwarePaginator;

    /**
     * Find a season by its local id. Returns null when not found.
     */
    public function findById(int $id): ?TvSeason;
}
```

#### TvEpisodeRepository

```php
class TvEpisodeRepository extends BaseRepository implements TvEpisodeRepositoryInterface
{
    private const ALLOWED_SORTS = ['episode_number', 'air_date', 'vote_average', 'id'];

    /**
     * Paginate episodes for a given season.
     * Large table constraint (20M+ rows): tv_season_id is REQUIRED to prevent full-table scans.
     * sort/order: whitelist-validated, default id ASC.
     */
    public function paginateByTvSeasonId(int $tvSeasonId, array $filters): LengthAwarePaginator;

    /**
     * Find an episode by its local id. Returns null when not found.
     */
    public function findById(int $id): ?TvEpisode;
}
```

#### TvEpisodeCreditRepository

```php
class TvEpisodeCreditRepository extends BaseRepository implements TvEpisodeCreditRepositoryInterface
{
    /**
     * Paginate credits for a given episode, eagerly loading the person relation.
     * Large table constraint (extremely large): tv_episode_id is REQUIRED.
     * person_id may be NULL due to async reconciliation — person will be null in that case.
     * Supported filters: credit_type (cast|crew), page, per_page.
     */
    public function paginateByTvEpisodeId(int $tvEpisodeId, array $filters): LengthAwarePaginator;
}
```

#### TvShowCreatorRepository

```php
class TvShowCreatorRepository extends BaseRepository implements TvShowCreatorRepositoryInterface
{
    /**
     * Get all creators for a given tv show, eagerly loading the person relation.
     * person_id may be NULL due to async reconciliation — person will be null in that case.
     */
    public function getByTvShowId(int $tvShowId): Collection;
}
```

## 数据模型

### TvShow Model

```php
class TvShow extends Model
{
    protected $table = 'tv_shows';
    protected $fillable = [];
    protected $casts = [
        'first_air_date'       => 'date',
        'last_air_date'        => 'date',
        'adult'                => 'boolean',
        'in_production'        => 'boolean',
        'episode_run_time'     => 'array',   // json
        'last_episode_to_air'  => 'array',   // json 快照
        'next_episode_to_air'  => 'array',   // json 快照
        'origin_country_codes' => 'array',
        'spoken_language_codes'=> 'array',
        'language_codes'       => 'array',
        'production_country_codes' => 'array',
        'popularity'           => 'float',
        'vote_average'         => 'float',
        'number_of_seasons'    => 'integer',
        'number_of_episodes'   => 'integer',
        'vote_count'           => 'integer',
    ];
}
```

### TvShowCreator Model

```php
class TvShowCreator extends Model
{
    protected $table = 'tv_show_creators';
    public $timestamps = false;
    protected $fillable = [];

    // person_id 可为 NULL（异步关联）
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
```

### TvSeason Model

```php
class TvSeason extends Model
{
    protected $table = 'tv_seasons';
    public $timestamps = false;
    protected $casts = [
        'air_date'     => 'date',
        'vote_average' => 'float',
        'episode_count'=> 'integer',
        'season_number'=> 'integer',
    ];
}
```

### TvEpisode Model

```php
class TvEpisode extends Model
{
    protected $table = 'tv_episodes';
    public $timestamps = false;
    protected $casts = [
        'air_date'       => 'date',
        'vote_average'   => 'float',
        'vote_count'     => 'integer',
        'runtime'        => 'integer',
        'season_number'  => 'integer',
        'episode_number' => 'integer',
    ];
}
```

### TvEpisodeCredit Model

```php
class TvEpisodeCredit extends Model
{
    protected $table = 'tv_episode_credits';
    public $timestamps = false;
    protected $casts = [
        'credit_type' => CreditType::class,  // 复用已有 Enum
    ];

    // person_id 可为 NULL（异步关联）
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
```

### 数据库关系图

```
tv_shows (20万+)
  ├── tv_show_genres        (JOIN genres)
  ├── tv_show_keywords      (JOIN keywords)
  ├── tv_show_networks      (JOIN tv_networks)
  ├── tv_show_production_companies (JOIN production_companies)
  ├── tv_show_images
  ├── tv_show_creators      [person_id 可 NULL]
  └── tv_seasons (100万+)   [必须带 tv_show_id]
        ├── tv_season_images
        └── tv_episodes (2000万+)  [必须带 tv_season_id]
              ├── tv_episode_credits (极大) [必须带 tv_episode_id, person_id 可 NULL]
              └── tv_episode_images
```

### 图片尺寸规则汇总

| 实体 | 字段 | 列表尺寸 | 详情尺寸 |
|------|------|---------|---------|
| TvShow | poster_path | w342 | w500 |
| TvShow | backdrop_path | w780 | original |
| TvShowImage | file_path | backdrop→w780, 其他→w342 | — |
| TvSeason | poster_path | w342 | w500 |
| TvSeasonImage | file_path | w342 | — |
| TvEpisode | still_path | w300 | w780 |
| TvEpisodeImage | file_path | w300 | — |
| Person | profile_path | w185 | — |
| TvNetwork | logo_path | w185 | — |
| ProductionCompany | logo_path | w185 | — |

## 正确性属性

*属性（Property）是在系统所有有效执行中都应成立的特征或行为——本质上是对系统应该做什么的形式化陈述。属性是人类可读规范与机器可验证正确性保证之间的桥梁。*

### 属性 1：列表过滤规则

*对于任意* 筛选参数组合（q、genre_id、status、first_air_year、in_production），返回的所有电视剧记录都必须满足所有已指定的筛选条件。

**验证需求：1.4、1.5、1.6、1.7、1.8**

### 属性 2：列表响应字段完整性

*对于任意* 电视剧列表响应中的每一条记录，都必须包含 id、tmdb_id、name、original_name、original_language、status、first_air_date、number_of_seasons、number_of_episodes、in_production、popularity、vote_average、vote_count、adult、poster_path、backdrop_path 字段。

**验证需求：1.13**

### 属性 3：详情响应字段完整性

*对于任意* 存在的电视剧，其详情响应必须包含 id、tmdb_id、name、original_name、original_language、overview、tagline、status、type、first_air_date、last_air_date、number_of_seasons、number_of_episodes、episode_run_time、popularity、vote_average、vote_count、adult、in_production、poster_path、backdrop_path、homepage、origin_country_codes、spoken_language_codes、language_codes、production_country_codes、last_episode_to_air、next_episode_to_air、created_at、updated_at 字段。

**验证需求：2.4**

### 属性 4：图片 URL 尺寸规则

*对于任意* 包含图片路径的响应记录，图片 URL 必须使用正确的尺寸前缀：TvShow 列表 poster 使用 w342、backdrop 使用 w780；TvShow 详情 poster 使用 w500、backdrop 使用 original；TvShowImage 中 backdrop 类型使用 w780、其他类型使用 w342；TvSeason 列表 poster 使用 w342、详情使用 w500；TvEpisode 列表 still 使用 w300、详情使用 w780；Person profile 使用 w185；logo 使用 w185。

**验证需求：1.14、2.5、3.9、3.10、4.6、5.5、6.7、7.5、8.5、9.7、10.5、11.8、12.5**

### 属性 5：异步关联 null 安全

*对于任意* `person_id` 为 NULL 的创作者（TvShowCreator）或演职人员（TvEpisodeCredit）记录，响应中 `person` 字段必须输出为 `null`，且该记录不被过滤掉，不触发错误。

**验证需求：5.4、11.7**

## 错误处理

### 业务异常

所有业务异常继承 `AppException`，由全局异常处理器统一转换为信封格式响应：

| 场景 | 异常 | HTTP code | 业务 code |
|------|------|-----------|---------|
| 电视剧不存在 | `AppException('电视剧不存在', 404)` | 200 | 404 |
| 季不存在 | `AppException('季不存在', 404)` | 200 | 404 |
| 集不存在 | `AppException('集不存在', 404)` | 200 | 404 |
| 参数验证失败 | FormRequest 自动处理 | 200 | 422 |
| 未认证 | JWT middleware 处理 | 200 | 401 |

### 大表约束违反

大表约束（tv_show_id / tv_season_id / tv_episode_id 必填）通过 FormRequest 的 `required` 规则在请求层拦截，不会到达 Repository 层。Repository 方法签名中的 `int $tvShowId` 等参数类型声明提供额外保障。

### 异步关联 null 安全

`TvShowCreator` 和 `TvEpisodeCredit` 的 `person_id` 可为 NULL。Resource 层处理：

```php
// TvShowCreatorResource / TvEpisodeCreditResource 中的 null 安全处理
'person' => $this->person_id !== null && $this->person !== null
    ? [
        'id'           => $this->person->id,
        'tmdb_id'      => $this->person->tmdb_id,
        'name'         => $this->person->name,
        'profile_path' => ImageHelper::url($this->person->profile_path, 'w185'),
    ]
    : null,
```

## 测试策略

### Feature Test（主要）

位置：`tests/Feature/TvShows/`

测试文件组织：

```
tests/Feature/TvShows/
├── TvShowListTest.php          # 列表接口：认证、筛选、分页、字段结构
├── TvShowDetailTest.php        # 详情接口：存在/不存在、字段结构
├── TvShowSubResourceTest.php   # genres/keywords/networks/companies/images/creators
├── TvSeasonListTest.php        # 季列表：tv_show_id 必填、大表约束、分页
├── TvSeasonDetailTest.php      # 季详情：存在/不存在
├── TvSeasonImageTest.php       # 季图片：tv_season_id 必填
├── TvEpisodeListTest.php       # 集列表：tv_season_id 必填、大表约束
├── TvEpisodeDetailTest.php     # 集详情：存在/不存在
├── TvEpisodeCreditTest.php     # 集演职人员：tv_episode_id 必填、null 安全
└── TvEpisodeImageTest.php      # 集图片：tv_episode_id 必填
```

所有只读接口测试使用 mock Service，不依赖真实数据库：

```php
// 示例：TvShowListTest
class TvShowListTest extends TestCase
{
    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/tv-shows')
             ->assertJson(['code' => 401]);
    }

    public function test_returns_paginated_tv_show_list(): void
    {
        $this->mock(TvShowService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')
                 ->once()
                 ->andReturn(new LengthAwarePaginator([], 0, 20));
        });

        $token = auth('api')->login(User::factory()->create());
        $this->withToken($token)->getJson('/api/tv-shows')
             ->assertJson(['code' => 0]);
    }

    public function test_invalid_sort_returns_422(): void { ... }
    public function test_page_over_1000_returns_422(): void { ... }
    public function test_missing_tv_show_id_returns_422(): void { ... }  // 子资源接口
}
```

### 必须覆盖的测试场景

| 场景 | 测试类型 |
|------|---------|
| 未认证请求返回 401 | Feature Test |
| 参数验证失败返回 422（必填项缺失） | Feature Test |
| 非法 sort 值返回 422 | Feature Test |
| page > 1000 返回 422 | Feature Test |
| 正常请求返回正确结构（code: 0 + pagination） | Feature Test |
| 资源不存在返回 404 | Feature Test |
| 异步关联 person_id 为 NULL 时 person 字段为 null | Feature Test |
| 图片 URL 格式正确（含正确尺寸） | Unit Test（ImageHelper） |

### 属性测试

本模块的核心业务逻辑（过滤、排序、字段输出）适合属性测试，使用 [pest-plugin-faker](https://github.com/pestphp/pest-plugin-faker) 或 [QuickCheck for PHP](https://github.com/steos/php-quickcheck) 生成随机输入：

- **属性 1（过滤规则）**：生成随机 tv_show 数据集和随机筛选参数，验证所有返回结果满足筛选条件
- **属性 4（图片 URL 尺寸）**：生成随机图片路径，验证 Resource 输出的 URL 包含正确尺寸前缀
- **属性 5（null 安全）**：生成 person_id 为 NULL 和非 NULL 的混合数据，验证 person 字段正确处理

每个属性测试最少运行 100 次迭代。标注格式：

```php
// Feature: tv-show, Property 1: 列表过滤规则
// Feature: tv-show, Property 4: 图片 URL 尺寸规则
// Feature: tv-show, Property 5: 异步关联 null 安全
```

## AppServiceProvider 绑定列表

在 `app/Providers/AppServiceProvider.php` 的 `register()` 方法中新增以下绑定（共 12 个）：

```php
// TV Show 主资源
$this->app->bind(TvShowRepositoryInterface::class, TvShowRepository::class);
$this->app->bind(TvShowGenreRepositoryInterface::class, TvShowGenreRepository::class);
$this->app->bind(TvShowKeywordRepositoryInterface::class, TvShowKeywordRepository::class);
$this->app->bind(TvShowNetworkRepositoryInterface::class, TvShowNetworkRepository::class);
$this->app->bind(TvShowProductionCompanyRepositoryInterface::class, TvShowProductionCompanyRepository::class);
$this->app->bind(TvShowImageRepositoryInterface::class, TvShowImageRepository::class);
$this->app->bind(TvShowCreatorRepositoryInterface::class, TvShowCreatorRepository::class);

// TV Season
$this->app->bind(TvSeasonRepositoryInterface::class, TvSeasonRepository::class);
$this->app->bind(TvSeasonImageRepositoryInterface::class, TvSeasonImageRepository::class);

// TV Episode
$this->app->bind(TvEpisodeRepositoryInterface::class, TvEpisodeRepository::class);
$this->app->bind(TvEpisodeCreditRepositoryInterface::class, TvEpisodeCreditRepository::class);
$this->app->bind(TvEpisodeImageRepositoryInterface::class, TvEpisodeImageRepository::class);
```

## 路由注册

在 `routes/api.php` 的 `auth:api` middleware 组内追加以下路由（共 15 条）：

```php
// TV Show 主资源
Route::get('tv-shows', [TvShowController::class, 'index']);
Route::get('tv-shows/{id}', [TvShowController::class, 'show']);

// TV Show 子资源（全量，不分页）
Route::get('tv-show-genres', [TvShowGenreController::class, 'index']);
Route::get('tv-show-keywords', [TvShowKeywordController::class, 'index']);
Route::get('tv-show-networks', [TvShowNetworkController::class, 'index']);
Route::get('tv-show-production-companies', [TvShowProductionCompanyController::class, 'index']);
Route::get('tv-show-creators', [TvShowCreatorController::class, 'index']);

// TV Show 子资源（分页）
Route::get('tv-show-images', [TvShowImageController::class, 'index']);

// TV Season
Route::get('tv-seasons', [TvSeasonController::class, 'index']);
Route::get('tv-seasons/{id}', [TvSeasonController::class, 'show']);
Route::get('tv-season-images', [TvSeasonImageController::class, 'index']);

// TV Episode
Route::get('tv-episodes', [TvEpisodeController::class, 'index']);
Route::get('tv-episodes/{id}', [TvEpisodeController::class, 'show']);
Route::get('tv-episode-credits', [TvEpisodeCreditController::class, 'index']);
Route::get('tv-episode-images', [TvEpisodeImageController::class, 'index']);
```

所有路由均在 `auth:api` middleware 组内，无需额外声明认证。
