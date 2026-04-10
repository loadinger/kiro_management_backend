# 设计文档：Movie 模块 API

## 概述

本模块在现有 Filmly Management Backend 架构基础上，新增电影（Movie）相关的只读 API 接口。遵循项目既有的分层架构（Controller → Service → Repository → Model），所有数据来自 `movies` 表及其关联表，只读，不做任何写入操作。

模块包含 7 类接口：
- `GET /api/movies` — 电影列表（分页、筛选、排序）
- `GET /api/movies/{id}` — 电影详情
- `GET /api/movie-credits?movie_id={id}` — 演职人员列表（分页）
- `GET /api/movie-images?movie_id={id}` — 图片列表（分页）
- `GET /api/movie-genres?movie_id={id}` — 类型列表（不分页）
- `GET /api/movie-keywords?movie_id={id}` — 关键词列表（不分页）
- `GET /api/movie-production-companies?movie_id={id}` — 制作公司列表（不分页）

---

## 架构

遵循项目标准分层，调用链如下：

```
routes/api.php
  └── FormRequest（参数验证）
        └── Controller（接收请求 → 调用 Service → 返回响应）
              └── Service（业务逻辑，抛出 AppException）
                    └── Repository（封装 Eloquent 查询）
                          └── Model（Eloquent，只读）
```

**关键约束：**
- `movies` 表数据量 100 万+，`page` 最大 1000，防止深翻页慢查询
- 所有接口加 `auth:api` middleware
- 图片路径在 Resource 层通过 `ImageHelper::url()` 拼接完整 URL
- `movie_credits.person_id` 存在异步关联（可为 NULL），Resource 层做 null 安全处理

---

## 组件与接口

### 新增文件清单

```
app/
├── Models/
│   ├── Movie.php
│   ├── MovieCredit.php
│   ├── MovieImage.php
│   └── Person.php                          # movie_credits 关联人物，只需基础字段
├── Repositories/
│   ├── Contracts/
│   │   ├── MovieRepositoryInterface.php
│   │   ├── MovieCreditRepositoryInterface.php
│   │   ├── MovieImageRepositoryInterface.php
│   │   ├── MovieGenreRepositoryInterface.php
│   │   ├── MovieKeywordRepositoryInterface.php
│   │   └── MovieProductionCompanyRepositoryInterface.php
│   ├── MovieRepository.php
│   ├── MovieCreditRepository.php
│   ├── MovieImageRepository.php
│   ├── MovieGenreRepository.php
│   ├── MovieKeywordRepository.php
│   └── MovieProductionCompanyRepository.php
├── Services/
│   ├── MovieService.php
│   ├── MovieCreditService.php
│   ├── MovieImageService.php
│   ├── MovieGenreService.php
│   ├── MovieKeywordService.php
│   └── MovieProductionCompanyService.php
├── Http/
│   ├── Requests/
│   │   ├── ListMovieRequest.php
│   │   ├── ListMovieCreditRequest.php
│   │   ├── ListMovieImageRequest.php
│   │   ├── ListMovieGenreRequest.php
│   │   ├── ListMovieKeywordRequest.php
│   │   └── ListMovieProductionCompanyRequest.php
│   ├── Resources/
│   │   ├── MovieListResource.php           # 列表专用（字段较少）
│   │   ├── MovieResource.php               # 详情（全字段）
│   │   ├── MovieCreditResource.php
│   │   ├── MovieImageResource.php
│   │   ├── MovieGenreResource.php
│   │   ├── MovieKeywordResource.php
│   │   └── MovieProductionCompanyResource.php
│   └── Controllers/Api/
│       ├── MovieController.php
│       ├── MovieCreditController.php
│       ├── MovieImageController.php
│       ├── MovieGenreController.php
│       ├── MovieKeywordController.php
│       └── MovieProductionCompanyController.php
```

### 接口签名

**MovieRepositoryInterface**
```php
public function paginateWithFilters(array $filters): LengthAwarePaginator;
public function findById(int $id): ?Movie;
```

**MovieCreditRepositoryInterface**
```php
// movie_id 为必填，防止无条件全表扫描
public function paginateByMovieId(int $movieId, array $filters): LengthAwarePaginator;
```

**MovieImageRepositoryInterface**
```php
public function paginateByMovieId(int $movieId, array $filters): LengthAwarePaginator;
```

**MovieGenreRepositoryInterface**
```php
public function getByMovieId(int $movieId): Collection;
```

**MovieKeywordRepositoryInterface**
```php
public function getByMovieId(int $movieId): Collection;
```

**MovieProductionCompanyRepositoryInterface**
```php
public function getByMovieId(int $movieId): Collection;
```

---

## 数据模型

### Movie 模型

```php
// app/Models/Movie.php
protected $table = 'movies';
protected $fillable = [];
public $timestamps = true;

protected $casts = [
    'release_date'             => 'date',
    'adult'                    => 'boolean',
    'video'                    => 'boolean',
    'spoken_language_codes'    => 'array',
    'production_country_codes' => 'array',
    'popularity'               => 'float',
    'vote_average'             => 'float',
    'budget'                   => 'integer',
    'revenue'                  => 'integer',
    'runtime'                  => 'integer',
    'vote_count'               => 'integer',
];
```

### MovieCredit 模型

```php
// app/Models/MovieCredit.php
protected $table = 'movie_credits';
protected $fillable = [];
public $timestamps = true;

protected $casts = [
    'credit_type' => CreditType::class,   // enum: cast | crew
    'cast_order'  => 'integer',
];

// 关联（person_id 可为 NULL，异步关联）
public function person(): BelongsTo  // → Person
```

### MovieImage 模型

```php
// app/Models/MovieImage.php
protected $table = 'movie_images';
protected $fillable = [];
public $timestamps = false;

protected $casts = [
    'width'        => 'integer',
    'height'       => 'integer',
    'vote_average' => 'float',
    'vote_count'   => 'integer',
];
```

### Person 模型（仅用于 credit 关联输出）

```php
// app/Models/Person.php
protected $table = 'persons';
protected $fillable = [];
public $timestamps = true;
// 只需 id, tmdb_id, name, profile_path 字段用于 credit 关联输出
```

### CreditType Enum

```php
// app/Enums/CreditType.php
enum CreditType: string
{
    case Cast = 'cast';
    case Crew = 'crew';
}
```

---

## 各接口设计细节

### 需求 1：电影列表 `GET /api/movies`

**ListMovieRequest 验证规则：**

| 参数 | 类型 | 规则 |
|------|------|------|
| `q` | string | nullable, max:100 |
| `genre_id` | integer | nullable, min:1 |
| `status` | string | nullable, max:50 |
| `release_year` | integer | nullable, digits:4, min:1888, max:2100 |
| `adult` | integer | nullable, in:0,1 |
| `sort` | string | nullable, in:popularity,release_date,vote_average,vote_count,id |
| `order` | string | nullable, in:asc,desc |
| `page` | integer | nullable, min:1, max:1000 |
| `per_page` | integer | nullable, min:1, max:100 |

**MovieRepository::paginateWithFilters 查询逻辑：**
- `q`：`WHERE (title LIKE 'q%' OR original_title LIKE 'q%')`（前缀匹配）
- `genre_id`：`JOIN movie_genres ON movie_genres.movie_id = movies.id WHERE movie_genres.genre_id = ?`
- `status`：`WHERE status = ?`
- `release_year`：`WHERE YEAR(release_date) = ?`
- `adult`：`WHERE adult = ?`
- `sort` + `order`：白名单字段排序，默认 `id DESC`

**MovieListResource 输出字段：**
`id`, `tmdb_id`, `title`, `original_title`, `original_language`, `status`, `release_date`, `runtime`, `popularity`, `vote_average`, `vote_count`, `adult`, `poster_path`（w342）, `backdrop_path`（w780）

### 需求 2：电影详情 `GET /api/movies/{id}`

- Service 调用 `findById`，不存在时抛出 `AppException('电影不存在', 404)`
- 无需预加载关联（关联数据由独立子资源接口提供）

**MovieResource 输出字段：**
`id`, `tmdb_id`, `imdb_id`, `title`, `original_title`, `original_language`, `overview`, `tagline`, `status`, `release_date`, `runtime`, `budget`, `revenue`, `popularity`, `vote_average`, `vote_count`, `adult`, `video`, `poster_path`（w500）, `backdrop_path`（original）, `homepage`, `spoken_language_codes`, `production_country_codes`, `created_at`, `updated_at`

### 需求 3：演职人员列表 `GET /api/movie-credits?movie_id={id}`

**ListMovieCreditRequest 验证规则：**

| 参数 | 类型 | 规则 |
|------|------|------|
| `movie_id` | integer | required, min:1 |
| `credit_type` | string | nullable, in:cast,crew |
| `page` | integer | nullable, min:1, max:1000 |
| `per_page` | integer | nullable, min:1, max:100 |

**Repository 查询：**
- `WHERE movie_id = ?`（必填，防止全表扫描）
- 可选 `WHERE credit_type = ?`
- 预加载 `person`（`with('person')`），person_id 为 NULL 时 person 关联为 null

**MovieCreditResource 输出字段：**
`id`, `movie_id`, `person_tmdb_id`, `person_id`, `credit_type`, `character`, `cast_order`, `department_id`, `job_id`, `person`（当 person_id 非 NULL 时：`id`, `tmdb_id`, `name`, `profile_path`（w185））

**null 安全处理：**
```php
'person' => $this->person_id
    ? [
        'id'           => $this->person->id,
        'tmdb_id'      => $this->person->tmdb_id,
        'name'         => $this->person->name,
        'profile_path' => ImageHelper::url($this->person->profile_path, 'w185'),
    ]
    : null,
```

### 需求 4：图片列表 `GET /api/movie-images?movie_id={id}`

**ListMovieImageRequest 验证规则：**

| 参数 | 类型 | 规则 |
|------|------|------|
| `movie_id` | integer | required, min:1 |
| `image_type` | string | nullable, in:poster,backdrop,logo |
| `page` | integer | nullable, min:1, max:1000 |
| `per_page` | integer | nullable, min:1, max:100 |

**MovieImageResource 输出字段：**
`id`, `movie_id`, `image_type`, `file_path`（poster/logo → w342，backdrop → w780）, `width`, `height`, `vote_average`, `vote_count`

**图片 size 逻辑：**
```php
'file_path' => ImageHelper::url(
    $this->file_path,
    $this->image_type === 'backdrop' ? 'w780' : 'w342'
),
```

### 需求 5：类型列表 `GET /api/movie-genres?movie_id={id}`

- 不分页，返回全量列表（`data` 直接为数组）
- Controller 调用 `BaseController::listing()`

**ListMovieGenreRequest：** `movie_id` required, integer, min:1

**MovieGenreResource 输出字段：** `id`, `tmdb_id`, `name`, `type`

**Repository 查询：**
```sql
SELECT genres.* FROM genres
JOIN movie_genres ON movie_genres.genre_id = genres.id
WHERE movie_genres.movie_id = ?
```

### 需求 6：关键词列表 `GET /api/movie-keywords?movie_id={id}`

- 不分页，返回全量列表

**ListMovieKeywordRequest：** `movie_id` required, integer, min:1

**MovieKeywordResource 输出字段：** `id`, `tmdb_id`, `name`

**Repository 查询：**
```sql
SELECT keywords.* FROM keywords
JOIN movie_keywords ON movie_keywords.keyword_id = keywords.id
WHERE movie_keywords.movie_id = ?
```

### 需求 7：制作公司列表 `GET /api/movie-production-companies?movie_id={id}`

- 不分页，返回全量列表

**ListMovieProductionCompanyRequest：** `movie_id` required, integer, min:1

**MovieProductionCompanyResource 输出字段：** `id`, `tmdb_id`, `name`, `origin_country`, `logo_path`（w185）

**Repository 查询：**
```sql
SELECT production_companies.* FROM production_companies
JOIN movie_production_companies ON movie_production_companies.production_company_id = production_companies.id
WHERE movie_production_companies.movie_id = ?
```

---

## 路由注册

在 `routes/api.php` 的 `auth:api` middleware 组内新增：

```php
// 电影主资源
Route::get('movies', [MovieController::class, 'index']);
Route::get('movies/{id}', [MovieController::class, 'show']);

// 电影子资源（独立路由 + 参数过滤）
Route::get('movie-credits', [MovieCreditController::class, 'index']);
Route::get('movie-images', [MovieImageController::class, 'index']);
Route::get('movie-genres', [MovieGenreController::class, 'index']);
Route::get('movie-keywords', [MovieKeywordController::class, 'index']);
Route::get('movie-production-companies', [MovieProductionCompanyController::class, 'index']);
```

---

## AppServiceProvider 绑定

```php
$this->app->bind(MovieRepositoryInterface::class, MovieRepository::class);
$this->app->bind(MovieCreditRepositoryInterface::class, MovieCreditRepository::class);
$this->app->bind(MovieImageRepositoryInterface::class, MovieImageRepository::class);
$this->app->bind(MovieGenreRepositoryInterface::class, MovieGenreRepository::class);
$this->app->bind(MovieKeywordRepositoryInterface::class, MovieKeywordRepository::class);
$this->app->bind(MovieProductionCompanyRepositoryInterface::class, MovieProductionCompanyRepository::class);
```

---

## 错误处理

| 场景 | 处理方式 |
|------|---------|
| 未携带 JWT Token | `auth:api` middleware 拦截，全局异常处理返回 `code: 401` |
| 参数验证失败 | FormRequest 自动返回 `code: 422` |
| `page` 超过 1000 | FormRequest 验证规则 `max:1000`，返回 `code: 422` |
| 电影 ID 不存在 | Service 抛出 `AppException('电影不存在', 404)`，全局处理返回 `code: 404` |
| `movie_id` 缺失（子资源） | FormRequest `required` 规则，返回 `code: 422` |
| `person_id` 为 NULL | Resource 层 null 安全处理，`person` 字段输出 `null`，不报错 |
| 未捕获异常 | 全局异常处理返回 `code: 500` |

---

## 测试策略

本模块为只读 API，测试使用 **mock Service** 策略，不依赖真实数据库（核心业务表无 migration）。

### Feature Test（主要）

位置：`tests/Feature/Movies/`

文件组织：
```
tests/Feature/Movies/
├── MovieListTest.php
├── MovieDetailTest.php
├── MovieCreditListTest.php
├── MovieImageListTest.php
├── MovieGenreListTest.php
├── MovieKeywordListTest.php
└── MovieProductionCompanyListTest.php
```

每个测试文件覆盖：
- 未认证请求返回 `code: 401`（必须）
- 参数验证失败返回 `code: 422`（必须，含大表深翻页限制）
- 正常请求返回正确响应结构（必须）
- 资源不存在返回 `code: 404`（详情接口，必须）
- `movie_id` 缺失返回 `code: 422`（子资源接口，必须）

测试模式（mock Service）：
```php
class MovieListTest extends TestCase
{
    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/movies')
             ->assertStatus(200)
             ->assertJson(['code' => 401]);
    }

    public function test_returns_paginated_movie_list(): void
    {
        $this->mock(MovieService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')
                 ->once()
                 ->andReturn(new LengthAwarePaginator([], 0, 20));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/movies')
             ->assertStatus(200)
             ->assertJsonStructure([
                 'code', 'message',
                 'data' => [
                     'list',
                     'pagination' => ['total', 'page', 'per_page', 'last_page'],
                 ],
             ])
             ->assertJson(['code' => 0]);
    }

    public function test_page_over_1000_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/movies?page=1001')
             ->assertJson(['code' => 422]);
    }
}
```

### Unit Test（按需）

位置：`tests/Unit/`

覆盖场景：
- `ImageHelper::url()` 的图片 URL 拼接逻辑（已有或新增）
- `CreditType` enum 的值正确性

**不需要**为 Repository 写单独 Unit Test（由 Feature Test 间接覆盖）。

### 测试覆盖要求

| 场景 | 测试类型 | 是否必须 |
|------|---------|---------|
| 未认证返回 401 | Feature | 必须 |
| 参数验证失败返回 422 | Feature | 必须 |
| 正常请求返回正确结构 | Feature | 必须 |
| 电影不存在返回 404 | Feature | 必须 |
| page > 1000 返回 422 | Feature | 必须 |
| movie_id 缺失返回 422 | Feature | 必须 |
| person_id 为 null 时 person 字段为 null | Feature | 必须 |
| 图片 URL 格式正确 | Unit | 必须 |
