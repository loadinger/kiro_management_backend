# 实现计划：参考数据 API（reference-data）

## 概述

按标准分层架构（Model → Repository → Service → FormRequest → Resource → Controller → Route）逐步实现 8 类只读参考数据接口，最后统一注册路由并补充测试。

## 任务列表

- [x] 1. 创建 8 个 Eloquent Model
  - 在 `app/Models/` 下创建 `Country`、`Department`、`Genre`、`Job`、`Keyword`、`Language`、`ProductionCompany`、`TvNetwork`
  - 每个 Model 设置正确的 `$table`、`$fillable = []`，`Department` 定义 `hasMany(Job::class)`，`Job` 定义 `belongsTo(Department::class)`
  - _需求：4.1、5.1、6.1、7.1、8.1、9.1、10.1、11.1_

- [x] 2. 创建 Repository 接口与实现（轻量参考数据）
  - [x] 2.1 创建 6 个 Repository 接口
    - 在 `app/Repositories/Contracts/` 下创建 `CountryRepositoryInterface`、`DepartmentRepositoryInterface`、`GenreRepositoryInterface`、`JobRepositoryInterface`、`KeywordRepositoryInterface`、`LanguageRepositoryInterface`
    - 每个接口声明 `paginateWithFilters(array $filters): LengthAwarePaginator`
    - _需求：4.1、5.1、6.1、7.1、8.1、9.1_

  - [x] 2.2 实现 6 个 Repository
    - 在 `app/Repositories/` 下创建对应实现类，继承 `BaseRepository`
    - `CountryRepository`：`q` 前缀匹配 `english_name` OR `native_name`，排序白名单 `id/english_name`
    - `DepartmentRepository`：`q` 前缀匹配 `name`，排序白名单 `id/name`
    - `GenreRepository`：`q` 前缀匹配 `name`，`type` 精确匹配，排序白名单 `id/name`
    - `JobRepository`：`q` 前缀匹配 `name`，`department_id` 精确匹配，排序白名单 `id/name/department_id`
    - `KeywordRepository`：`q` 前缀匹配 `name`，排序白名单 `id/name`
    - `LanguageRepository`：`q` 前缀匹配 `english_name` OR `name`，排序白名单 `id/english_name`
    - _需求：4.2、5.2、6.2、6.4、7.2、7.4、8.2、9.2、13.1_

- [x] 3. 创建 Repository 接口与实现（富参考数据）
  - [x] 3.1 创建 2 个 Repository 接口
    - 在 `app/Repositories/Contracts/` 下创建 `ProductionCompanyRepositoryInterface`、`TvNetworkRepositoryInterface`
    - 声明 `paginateWithFilters(array $filters): LengthAwarePaginator` 和 `findById(int $id): ?Model`
    - _需求：10.1、10.4、11.1、11.4_

  - [x] 3.2 实现 2 个 Repository
    - `ProductionCompanyRepository`：`q` 前缀匹配 `name`，排序白名单 `id/name`，`findById` 返回 `?ProductionCompany`
    - `TvNetworkRepository`：`q` 前缀匹配 `name`，排序白名单 `id/name`，`findById` 返回 `?TvNetwork`
    - _需求：10.2、10.4、11.2、11.4、13.1_

- [x] 4. 在 AppServiceProvider 注册 8 个 Repository 绑定
  - 在 `AppServiceProvider::register()` 中添加全部 8 个 `$this->app->bind(Interface::class, Implementation::class)`
  - _需求：4.1–11.1（架构支撑）_

- [x] 5. 创建 8 个 Service
  - [x] 5.1 创建 6 个轻量参考数据 Service
    - 在 `app/Services/` 下创建 `CountryService`、`DepartmentService`、`GenreService`、`JobService`、`KeywordService`、`LanguageService`
    - 每个 Service 注入对应 Repository 接口，实现 `getList(array $filters): LengthAwarePaginator`
    - _需求：4.1、5.1、6.1、7.1、8.1、9.1_

  - [x] 5.2 创建 2 个富参考数据 Service
    - 创建 `ProductionCompanyService`、`TvNetworkService`
    - 实现 `getList` 和 `findById(int $id): ProductionCompany|TvNetwork`
    - `findById` 返回 null 时抛出 `AppException('制作公司不存在', 404)` / `AppException('电视网络不存在', 404)`
    - _需求：10.4、10.5、11.4、11.5、2.4_

- [x] 6. 创建 FormRequest（轻量参考数据）
  - 在 `app/Http/Requests/` 下创建 `ListCountryRequest`、`ListDepartmentRequest`、`ListGenreRequest`、`ListJobRequest`、`ListKeywordRequest`、`ListLanguageRequest`
  - 每个 FormRequest 声明 `q`（max:100）、`sort`（Rule::in 白名单）、`order`（in:asc,desc）、`page`（integer,min:1,max:1000）、`per_page`（integer,min:1,max:100）
  - `ListGenreRequest` 额外添加 `type`（in:movie,tv）；`ListJobRequest` 额外添加 `department_id`（integer,min:1）
  - 所有 FormRequest 提供中文 `messages()`，`authorize()` 返回 `true`
  - _需求：3.2、3.3、4.3、4.4、4.5、5.3、5.4、6.3、6.5、6.6、7.3、7.5、7.6、8.3、8.4、9.3、9.4、13.1、13.2_

- [x] 7. 创建 FormRequest（富参考数据）
  - 创建 `ListProductionCompanyRequest`、`ListTvNetworkRequest`
  - 字段：`q`（max:100）、`sort`（in:id,name）、`order`（in:asc,desc）、`page`（max:1000）、`per_page`（max:100）
  - _需求：3.2、3.3、10.3、10.8、10.9、11.3、11.8、11.9、13.1、13.2_

- [x] 8. 创建 API Resource（轻量参考数据）
  - 在 `app/Http/Resources/` 下创建 `CountryResource`、`DepartmentResource`、`GenreResource`、`JobResource`、`KeywordResource`、`LanguageResource`
  - 每个 Resource 只输出设计文档中声明的字段，不使用 `toArray()` 全量输出
  - _需求：4.1、5.1、6.1、7.1、8.1、9.1、2.1、2.2_

- [x] 9. 创建 API Resource（富参考数据）
  - 创建 `ProductionCompanyListResource`（列表，`logo_url` 用 `w185`）和 `ProductionCompanyResource`（详情，`logo_url` 用 `w342`）
  - 创建 `TvNetworkListResource`（列表，`logo_url` 用 `w185`）和 `TvNetworkResource`（详情，`logo_url` 用 `w342`）
  - `logo_url` 通过 `ImageHelper::url($this->logo_path, 'w185'/'w342')` 拼接，`logo_path` 为 null 时输出 null
  - _需求：10.1、10.4、10.6、10.7、11.1、11.4、11.6、11.7、14.1、14.2、14.3_

  - [ ]* 9.1 为 ImageHelper 编写单元测试
    - 在 `tests/Unit/Helpers/ImageHelperTest.php` 中编写测试
    - **属性 12：logo_url 拼接往返属性** — `parse_url(ImageHelper::url('/abc.jpg', 'w185'), PHP_URL_PATH) === '/t/p/w185/abc.jpg'`
    - **验证：需求 14.1、14.4**
    - **属性 13：logo_path 为 null 时 logo_url 为 null** — `ImageHelper::url(null, 'w185') === null`
    - **验证：需求 10.7、11.7、14.2**
    - **属性 15：列表尺寸 w185 vs 详情尺寸 w342** — 同一路径列表 URL 含 `w185`，详情 URL 含 `w342`
    - **验证：需求 10.6、11.6、14.3**

- [x] 10. 创建 8 个 Controller
  - 在 `app/Http/Controllers/Api/` 下创建 `CountryController`、`DepartmentController`、`GenreController`、`JobController`、`KeywordController`、`LanguageController`、`ProductionCompanyController`、`TvNetworkController`
  - 轻量参考数据 Controller 只实现 `index` 方法，调用 `$this->paginate()` 返回分页响应
  - `ProductionCompanyController` 和 `TvNetworkController` 额外实现 `show` 方法，调用 `$this->success()` 返回详情
  - 所有 Controller 继承 `BaseController`，构造函数注入对应 Service
  - _需求：4.1、5.1、6.1、7.1、8.1、9.1、10.1、10.4、11.1、11.4、12.1_

- [x] 11. 注册路由
  - 在 `routes/api.php` 的 `auth:api` middleware 组内注册全部 10 条路由（6 个轻量列表 + 2 个富参考数据列表 + 2 个富参考数据详情）
  - 仅注册 GET 方法，不注册 POST/PUT/PATCH/DELETE
  - _需求：1.1、1.2、1.3、12.1、12.2_

- [x] 12. 检查点 — 确保所有代码通过 Pint 格式化
  - 运行 `./vendor/bin/pint` 确保无格式错误，向用户确认是否有疑问。

- [x] 13. 编写 Feature Test（轻量参考数据）
  - [ ]* 13.1 编写 CountryControllerTest
    - 在 `tests/Feature/ReferenceData/CountryControllerTest.php` 中编写测试，mock `CountryService`
    - **属性 1：未认证请求返回 401** — `test_unauthenticated_request_returns_401`，**验证：需求 1.1、1.2**
    - **属性 2：有效 Token 可正常访问** — `test_index_returns_paginated_list_with_correct_structure`，**验证：需求 1.3、2.1、2.2、3.4**
    - **属性 3：列表响应结构完整性** — 验证 `data.items` 和 `data.meta`，**验证：需求 2.1、3.4**
    - **属性 4：per_page 超限返回 422** — `test_per_page_exceeding_100_returns_422`，**验证：需求 3.2**
    - **属性 5：page 超限返回 422** — `test_page_exceeding_1000_returns_422`，**验证：需求 3.3**
    - **属性 7：q 参数超长返回 422** — `test_q_exceeding_100_chars_returns_422`，**验证：需求 4.3**
    - **属性 8：非法 sort 参数返回 422** — `test_invalid_sort_field_returns_422`，**验证：需求 4.4、13.2**

  - [ ]* 13.2 编写 GenreControllerTest
    - 在 `tests/Feature/ReferenceData/GenreControllerTest.php` 中编写测试，mock `GenreService`
    - **属性 9：genres type 筛选精确匹配** — `test_genre_type_filter_returns_matching_records`，**验证：需求 6.2**
    - **属性 10：genres 非法 type 返回 422** — `test_invalid_genre_type_returns_422`，**验证：需求 6.3**
    - 复用属性 1、4、5、7、8 的测试场景，**验证：需求 6.5、6.6**

  - [ ]* 13.3 编写 JobControllerTest
    - 在 `tests/Feature/ReferenceData/JobControllerTest.php` 中编写测试，mock `JobService`
    - **属性 11：jobs department_id 筛选精确匹配** — `test_job_department_id_filter_returns_matching_records`，**验证：需求 7.2**
    - `department_id` 非正整数返回 422，**验证：需求 7.3**
    - 复用属性 1、4、5、7、8 的测试场景，**验证：需求 7.5、7.6**

- [x] 14. 编写 Feature Test（富参考数据）
  - [ ]* 14.1 编写 ProductionCompanyControllerTest
    - 在 `tests/Feature/ReferenceData/ProductionCompanyControllerTest.php` 中编写测试，mock `ProductionCompanyService`
    - **属性 14：详情接口 404 响应** — `test_show_returns_404_when_not_found`，**验证：需求 2.4、10.5**
    - **属性 15：列表 logo_url 含 w185，详情含 w342** — `test_show_returns_full_detail_fields`，**验证：需求 10.4、10.6**
    - **属性 13：logo_path 为 null 时 logo_url 为 null** — `test_logo_url_is_null_when_logo_path_is_null`，**验证：需求 10.7**
    - 复用属性 1、4、5、7、8 的测试场景，**验证：需求 10.3、10.8**

  - [ ]* 14.2 编写 TvNetworkControllerTest
    - 在 `tests/Feature/ReferenceData/TvNetworkControllerTest.php` 中编写测试，mock `TvNetworkService`
    - **属性 14：详情接口 404 响应** — `test_show_returns_404_when_not_found`，**验证：需求 2.4、11.5**
    - **属性 15：列表 logo_url 含 w185，详情含 w342** — `test_show_returns_full_detail_fields`，**验证：需求 11.4、11.6**
    - **属性 13：logo_path 为 null 时 logo_url 为 null** — `test_logo_url_is_null_when_logo_path_is_null`，**验证：需求 11.7**
    - 复用属性 1、4、5、7、8 的测试场景，**验证：需求 11.3、11.8**

- [x] 15. 最终检查点 — 确保所有测试通过
  - 运行 `php artisan test` 确保全部测试通过，向用户确认是否有疑问。

## 备注

- 标有 `*` 的子任务为可选测试任务，可跳过以加快 MVP 进度
- 每个任务引用了具体需求条款，便于追溯
- 检查点确保增量验证，避免积累问题
- Feature Test 统一 mock Service 层，不依赖真实数据库
- Unit Test 覆盖 `ImageHelper` 的正确性属性（属性 12、13、15）
