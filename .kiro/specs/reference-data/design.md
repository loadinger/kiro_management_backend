# 技术设计文档：参考数据 API（reference-data）

## 概述

本功能为 Filmly 管理后台提供 8 类只读参考数据的 RESTful API 接口。所有数据由独立采集项目从 TMDB 写入，本项目只负责查询输出。

接口分两类：
- **轻量参考数据**（仅列表+搜索）：countries、departments、genres、jobs、keywords、languages
- **富参考数据**（列表+搜索+详情，含图片）：production_companies、tv_networks

所有接口均需 JWT 认证（`auth:api` middleware），响应遵循统一信封格式 `{ code, message, data }`。

---

## 架构

遵循项目标准分层架构：

```
routes/api.php
  └── auth:api middleware
        └── FormRequest（参数验证 + 白名单排序校验）
              └── Controller（index / show）
                    └── Service（getList / findById）
                          └── Repository（paginateWithFilters / findById）
                                └── Model（只读，无 fillable）
```

### 设计决策

**共享 Repository 基类 vs 独立 Repository**：每个实体独立一套 Repository，不共享泛型基类。原因：各实体的筛选字段、排序白名单、关联预加载各不相同，强行抽象会增加复杂度。

**轻量 vs 富参考数据的 Resource 差异**：富参考数据（production_companies、tv_networks）需要两个 Resource 类（列表用 `XxxListResource`，详情用 `XxxResource`），以便在不同尺寸下拼接 logo URL（列表 `w185`，详情 `w342`）。轻量参考数据只需一个 Resource 类。

**不引入缓存**：需求文档未要求缓存，参考数据变更频率低但本项目不控制写入时机，暂不实现缓存层，避免引入失效策略复杂度。

---

## 组件与接口

### 路由注册（routes/api.php）

所有路由注册在 `auth:api` middleware 组内，仅注册 GET 方法：

```php
Route::middleware('auth:api')->group(function () {
    // 轻量参考数据（仅列表）
    Route::get('countries',    [CountryController::class,    'index']);
    Route::get('departments',  [DepartmentController::class, 'index']);
    Route::get('genres',       [GenreController::class,      'index']);
    Route::get('jobs',         [JobController::class,        'index']);
    Route::get('keywords',     [KeywordController::class,    'index']);
    Route::get('languages',    [LanguageController::class,   'index']);

    // 富参考数据（列表 + 详情）
    Route::get('production-companies',      [ProductionCompanyController::class, 'index']);
    Route::get('production-companies/{id}', [ProductionCompanyController::class, 'show']);
    Route::get('tv-networks',               [TvNetworkController::class,         'index']);
    Route::get('tv-networks/{id}',          [TvNetworkController::class,         'show']);
});
```

### Controllers

所有 Controller 继承 `BaseController`，构造函数注入对应 Service：

| Controller | 方法 | 说明 |
|---|---|---|
| `CountryController` | `index` | 国家列表 |
| `DepartmentController` | `index` | 部门列表 |
| `GenreController` | `index` | 类型列表 |
| `JobController` | `index` | 职位列表 |
| `KeywordController` | `index` | 关键词列表 |
| `LanguageController` | `index` | 语言列表 |
| `ProductionCompanyController` | `index`, `show` | 制作公司列表+详情 |
| `TvNetworkController` | `index`, `show` | 电视网络列表+详情 |

### Services

| Service | 方法 | 返回类型 |
|---|---|---|
| `CountryService` | `getList(array $filters): LengthAwarePaginator` | |
| `DepartmentService` | `getList(array $filters): LengthAwarePaginator` | |
| `GenreService` | `getList(array $filters): LengthAwarePaginator` | |
| `JobService` | `getList(array $filters): LengthAwarePaginator` | |
| `KeywordService` | `getList(array $filters): LengthAwarePaginator` | |
| `LanguageService` | `getList(array $filters): LengthAwarePaginator` | |
| `ProductionCompanyService` | `getList(array $filters): LengthAwarePaginator`<br>`findById(int $id): ProductionCompany` | |
| `TvNetworkService` | `getList(array $filters): LengthAwarePaginator`<br>`findById(int $id): TvNetwork` | |

`findById` 找不到时抛出 `AppException`（code 404）。

### Repositories

| Repository | 接口 | 主要方法 |
|---|---|---|
| `CountryRepository` | `CountryRepositoryInterface` | `paginateWithFilters(array $filters): LengthAwarePaginator` |
| `DepartmentRepository` | `DepartmentRepositoryInterface` | `paginateWithFilters(array $filters): LengthAwarePaginator` |
| `GenreRepository` | `GenreRepositoryInterface` | `paginateWithFilters(array $filters): LengthAwarePaginator` |
| `JobRepository` | `JobRepositoryInterface` | `paginateWithFilters(array $filters): LengthAwarePaginator` |
| `KeywordRepository` | `KeywordRepositoryInterface` | `paginateWithFilters(array $filters): LengthAwarePaginator` |
| `LanguageRepository` | `LanguageRepositoryInterface` | `paginateWithFilters(array $filters): LengthAwarePaginator` |
| `ProductionCompanyRepository` | `ProductionCompanyRepositoryInterface` | `paginateWithFilters(array $filters): LengthAwarePaginator`<br>`findById(int $id): ?ProductionCompany` |
| `TvNetworkRepository` | `TvNetworkRepositoryInterface` | `paginateWithFilters(array $filters): LengthAwarePaginator`<br>`findById(int $id): ?TvNetwork` |

### FormRequests

每个列表接口一个 FormRequest，富参考数据详情接口无需额外 FormRequest（路由参数 `{id}` 直接由 Controller 接收）：

| FormRequest | 验证字段 |
|---|---|
| `ListCountryRequest` | `q`（max:100）、`sort`（in:id,english_name）、`order`（in:asc,desc）、`page`（max:1000）、`per_page`（max:100） |
| `ListDepartmentRequest` | `q`（max:100）、`sort`（in:id,name）、`order`、`page`、`per_page` |
| `ListGenreRequest` | `q`（max:100）、`type`（in:movie,tv）、`sort`（in:id,name）、`order`、`page`、`per_page` |
| `ListJobRequest` | `q`（max:100）、`department_id`（integer,min:1）、`sort`（in:id,name,department_id）、`order`、`page`、`per_page` |
| `ListKeywordRequest` | `q`（max:100）、`sort`（in:id,name）、`order`、`page`、`per_page` |
| `ListLanguageRequest` | `q`（max:100）、`sort`（in:id,english_name）、`order`、`page`、`per_page` |
| `ListProductionCompanyRequest` | `q`（max:100）、`sort`（in:id,name）、`order`、`page`、`per_page` |
| `ListTvNetworkRequest` | `q`（max:100）、`sort`（in:id,name）、`order`、`page`、`per_page` |

所有 FormRequest 的 `order` 默认值为 `asc`，`page` 默认 1，`per_page` 默认 20。

### API Resources

| Resource | 用途 | 输出字段 |
|---|---|---|
| `CountryResource` | 列表 | `id`, `iso_3166_1`, `english_name`, `native_name` |
| `DepartmentResource` | 列表 | `id`, `name` |
| `GenreResource` | 列表 | `id`, `tmdb_id`, `name`, `type` |
| `JobResource` | 列表 | `id`, `name`, `department_id` |
| `KeywordResource` | 列表 | `id`, `tmdb_id`, `name` |
| `LanguageResource` | 列表 | `id`, `iso_639_1`, `english_name`, `name` |
| `ProductionCompanyListResource` | 列表 | `id`, `tmdb_id`, `name`, `origin_country`, `logo_url`（w185） |
| `ProductionCompanyResource` | 详情 | `id`, `tmdb_id`, `name`, `description`, `headquarters`, `homepage`, `logo_url`（w342）, `origin_country`, `parent_company_tmdb_id` |
| `TvNetworkListResource` | 列表 | `id`, `tmdb_id`, `name`, `origin_country`, `logo_url`（w185） |
| `TvNetworkResource` | 详情 | `id`, `tmdb_id`, `name`, `headquarters`, `homepage`, `logo_url`（w342）, `origin_country` |

`logo_url` 通过 `ImageHelper::url($this->logo_path, 'w185')` 或 `ImageHelper::url($this->logo_path, 'w342')` 拼接，`logo_path` 为 null 时输出 null。

---

## 数据模型

### 模型列表

所有模型对应只读表，`$fillable = []`，无 timestamps 写入需求（保留读取）。

**Country**
```php
// app/Models/Country.php
protected $table = 'countries';
protected $fillable = [];
// 无特殊 casts，全字符串字段
```

**Department**
```php
// app/Models/Department.php
protected $table = 'departments';
protected $fillable = [];
// hasMany: jobs
```

**Genre**
```php
// app/Models/Genre.php
protected $table = 'genres';
protected $fillable = [];
// type 字段为 varchar，不使用 enum cast（DB 层已约束）
```

**Job**
```php
// app/Models/Job.php
protected $table = 'jobs';
protected $fillable = [];
// belongsTo: department
```

**Keyword**
```php
// app/Models/Keyword.php
protected $table = 'keywords';
protected $fillable = [];
```

**Language**
```php
// app/Models/Language.php
protected $table = 'languages';
protected $fillable = [];
```

**ProductionCompany**
```php
// app/Models/ProductionCompany.php
protected $table = 'production_companies';
protected $fillable = [];
// parent_company_tmdb_id 为 uint，无本地关联（只存 tmdb_id）
```

**TvNetwork**
```php
// app/Models/TvNetwork.php
protected $table = 'tv_networks';
protected $fillable = [];
```

### 查询模式

**轻量参考数据通用查询模式**（以 Country 为例）：

```php
// Repository 中的 paginateWithFilters 实现模式
$query = Country::query();

if (!empty($filters['q'])) {
    $q = $filters['q'];
    $query->where(function ($sub) use ($q) {
        $sub->where('english_name', 'like', $q . '%')
            ->orWhere('native_name', 'like', $q . '%');
    });
}

$sort  = $filters['sort']  ?? 'id';
$order = $filters['order'] ?? 'asc';
$query->orderBy($sort, $order);

return $query->paginate(
    perPage: $filters['per_page'] ?? 20,
    page:    $filters['page']     ?? 1,
);
```

**搜索字段映射**：

| 实体 | 搜索字段 |
|---|---|
| countries | `english_name` OR `native_name`（前缀匹配） |
| departments | `name`（前缀匹配） |
| genres | `name`（前缀匹配） |
| jobs | `name`（前缀匹配） |
| keywords | `name`（前缀匹配） |
| languages | `english_name` OR `name`（前缀匹配） |
| production_companies | `name`（前缀匹配） |
| tv_networks | `name`（前缀匹配） |

**genres 额外筛选**：`type` 字段精确匹配（`where('type', $filters['type'])`）。

**jobs 额外筛选**：`department_id` 字段精确匹配（`where('department_id', $filters['department_id'])`）。

**富参考数据详情查询**：

```php
// ProductionCompanyRepository::findById
public function findById(int $id): ?ProductionCompany
{
    return ProductionCompany::find($id);
}
```

Service 层判断返回值为 null 时抛出 `AppException('制作公司不存在', 404)`。

### AppServiceProvider 绑定

```php
// 在 AppServiceProvider::register() 中注册所有 Repository 绑定
$this->app->bind(CountryRepositoryInterface::class,           CountryRepository::class);
$this->app->bind(DepartmentRepositoryInterface::class,        DepartmentRepository::class);
$this->app->bind(GenreRepositoryInterface::class,             GenreRepository::class);
$this->app->bind(JobRepositoryInterface::class,               JobRepository::class);
$this->app->bind(KeywordRepositoryInterface::class,           KeywordRepository::class);
$this->app->bind(LanguageRepositoryInterface::class,          LanguageRepository::class);
$this->app->bind(ProductionCompanyRepositoryInterface::class, ProductionCompanyRepository::class);
$this->app->bind(TvNetworkRepositoryInterface::class,         TvNetworkRepository::class);
```

---

## 正确性属性

*属性（Property）是在系统所有合法执行中都应成立的特征或行为——本质上是对系统应做什么的形式化陈述。属性是人类可读规范与机器可验证正确性保证之间的桥梁。*

### 属性 1：未认证请求返回 401

对于任意参考数据接口路径，在不携带 Authorization 请求头的情况下发送 GET 请求，响应体中的 `code` 字段应为 401，`data` 应为 null。

**验证需求：1.1、1.2**

### 属性 2：有效 Token 可正常访问

对于任意参考数据接口路径，携带有效 JWT Token 发送 GET 请求，响应体中的 `code` 字段应为 0。

**验证需求：1.3**

### 属性 3：列表响应结构完整性

对于任意列表接口，携带有效 Token 发送合法请求，响应体应包含 `code: 0`、`message: "success"`，且 `data` 中包含 `items` 数组和含 `total`、`page`、`per_page`、`last_page` 的 `meta` 对象。

**验证需求：2.1、2.2、3.4**

### 属性 4：per_page 超限返回 422

对于任意列表接口，当 `per_page` 参数值大于 100 时，响应体中的 `code` 应为 422。

**验证需求：3.2**

### 属性 5：page 超限返回 422

对于任意列表接口，当 `page` 参数值大于 1000 时，响应体中的 `code` 应为 422。

**验证需求：3.3**

### 属性 6：q 参数前缀过滤有效性

对于任意列表接口，当携带 `q` 参数时，返回的 `items` 中每条记录的搜索字段值应以 `q` 的值为前缀（大小写不敏感）。

**验证需求：4.2、5.2、6.4、7.4、8.2、9.2、10.2、11.2**

### 属性 7：q 参数超长返回 422

对于任意列表接口，当 `q` 参数长度超过 100 个字符时，响应体中的 `code` 应为 422。

**验证需求：4.3、5.3、6.5、7.5、8.3、9.3、10.3、11.3**

### 属性 8：非法 sort 参数返回 422

对于任意列表接口，当 `sort` 参数值不在该接口的白名单中时，响应体中的 `code` 应为 422，而不是将该值传入数据库查询。

**验证需求：4.4、5.4、6.6、7.6、8.4、9.4、10.8、11.8、13.1、13.2**

### 属性 9：genres type 筛选精确匹配

对于 genres 列表接口，当携带合法 `type` 参数（`movie` 或 `tv`）时，返回的 `items` 中每条记录的 `type` 字段值应与参数值完全相同。

**验证需求：6.2**

### 属性 10：genres 非法 type 返回 422

对于 genres 列表接口，当 `type` 参数值不是 `movie` 或 `tv` 时，响应体中的 `code` 应为 422。

**验证需求：6.3**

### 属性 11：jobs department_id 筛选精确匹配

对于 jobs 列表接口，当携带合法 `department_id` 参数时，返回的 `items` 中每条记录的 `department_id` 字段值应与参数值相等。

**验证需求：7.2**

### 属性 12：logo_url 拼接往返属性

对于任意非 null 的 `logo_path` 值，`ImageHelper::url($path, $size)` 返回的 URL 解析其路径部分后应与原始 `logo_path` 相等（即 `parse_url(url, PHP_URL_PATH) === "/t/p/{size}{path}"`）。

**验证需求：14.1、14.4**

### 属性 13：logo_path 为 null 时 logo_url 为 null

对于任意 `logo_path` 为 null 的 production_company 或 tv_network 记录，列表和详情响应中的 `logo_url` 字段应为 null 而不是抛出异常。

**验证需求：10.7、11.7、14.2**

### 属性 14：详情接口 404 响应

对于 production_companies 和 tv_networks 的详情接口，当 `{id}` 在数据库中不存在时，响应体中的 `code` 应为 404，`data` 应为 null。

**验证需求：2.4、10.5、11.5**

### 属性 15：列表接口尺寸与详情接口尺寸不同

对于同一条 production_company 或 tv_network 记录（`logo_path` 非 null），列表接口返回的 `logo_url` 应包含 `w185`，详情接口返回的 `logo_url` 应包含 `w342`。

**验证需求：10.6、11.6、14.3**

---

## 错误处理

### 认证错误（401）

由 `auth:api` middleware 统一处理，全局异常处理器将 JWT 异常转换为信封格式：

```json
{ "code": 401, "message": "未认证，请先登录", "data": null }
```

### 参数验证错误（422）

由 FormRequest 的 `failedValidation` 触发，全局异常处理器捕获 `ValidationException` 并转换：

```json
{ "code": 422, "message": "参数错误：sort 必须是 id, name 之一", "data": null }
```

### 资源不存在（404）

Service 层调用 `findById` 返回 null 时，抛出 `AppException`：

```php
throw new AppException('制作公司不存在', 404);
throw new AppException('电视网络不存在', 404);
```

全局处理器捕获后返回：

```json
{ "code": 404, "message": "制作公司不存在", "data": null }
```

### 非 GET 请求（405）

Laravel 路由层自动处理，未注册的方法返回 HTTP 405 Method Not Allowed（需求 12.2 要求的是 HTTP 层行为，由框架保证）。

---

## 测试策略

### Feature Test（主要）

位置：`tests/Feature/ReferenceData/`

每个资源一个测试文件，覆盖以下场景：

| 场景 | 测试方法命名示例 |
|---|---|
| 未认证返回 401 | `test_unauthenticated_request_returns_401` |
| 正常列表请求返回正确结构 | `test_index_returns_paginated_list_with_correct_structure` |
| q 参数过滤生效 | `test_search_filters_by_prefix` |
| q 超长返回 422 | `test_q_exceeding_100_chars_returns_422` |
| 非法 sort 返回 422 | `test_invalid_sort_field_returns_422` |
| per_page 超限返回 422 | `test_per_page_exceeding_100_returns_422` |
| page 超限返回 422 | `test_page_exceeding_1000_returns_422` |
| 详情接口返回正确字段（富参考数据） | `test_show_returns_full_detail_fields` |
| 详情接口 404 | `test_show_returns_404_when_not_found` |
| genres type 筛选 | `test_genre_type_filter_returns_matching_records` |
| genres 非法 type 返回 422 | `test_invalid_genre_type_returns_422` |
| jobs department_id 筛选 | `test_job_department_id_filter_returns_matching_records` |

所有只读接口的 Feature Test 使用 **mock Service**，不依赖真实数据库：

```php
// Feature Test 示例（CountryControllerTest）
$this->mock(CountryService::class, function (MockInterface $mock) {
    $mock->shouldReceive('getList')
         ->once()
         ->andReturn(new LengthAwarePaginator([], 0, 20));
});
```

### Unit Test

位置：`tests/Unit/Helpers/ImageHelperTest.php`

覆盖 `ImageHelper::url()` 的正确性属性：

```php
// Feature: reference-data, Property 12: logo_url round-trip
public function test_url_returns_correct_full_url(): void
{
    $url = ImageHelper::url('/abc123.jpg', 'w185');
    $this->assertSame('https://image.tmdb.org/t/p/w185/abc123.jpg', $url);
    // Round-trip: parse_url path should equal /t/p/w185 + original path
    $this->assertSame('/t/p/w185/abc123.jpg', parse_url($url, PHP_URL_PATH));
}

// Feature: reference-data, Property 13: null logo_path returns null
public function test_url_returns_null_when_path_is_null(): void
{
    $this->assertNull(ImageHelper::url(null, 'w185'));
}

// Feature: reference-data, Property 15: list size vs detail size
public function test_list_size_w185_vs_detail_size_w342(): void
{
    $listUrl   = ImageHelper::url('/abc123.jpg', 'w185');
    $detailUrl = ImageHelper::url('/abc123.jpg', 'w342');
    $this->assertStringContainsString('w185', $listUrl);
    $this->assertStringContainsString('w342', $detailUrl);
}
```

### 属性测试配置

- 使用 PHPUnit 的 `@dataProvider` 提供多组输入覆盖边界值（最小值、最大值、边界值）
- 每个属性测试至少覆盖 3 组不同输入
- 测试注释格式：`// Feature: reference-data, Property {N}: {property_text}`
