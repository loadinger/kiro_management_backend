# 实现计划：Media List Snapshots

## 概述

按照项目标准分层架构（Route → FormRequest → Controller → Service → Repository → Model）实现 10 个榜单快照只读接口。Service 层负责聚合快照数据与实体数据，支持 `local_id` / `tmdb_id` 降级关联。

## 任务

- [x] 1. 创建 ListType 枚举与 MediaListSnapshot 模型
  - 在 `app/Enums/ListType.php` 创建 `ListType` 枚举，定义 10 个枚举值及 `isMovie()`、`isTvShow()`、`isPerson()` 辅助方法
  - 在 `app/Models/MediaListSnapshot.php` 创建模型，设置 `$timestamps = false`、`$fillable = []`，声明 `$casts`（`list_type` 转 `ListType`、`snapshot_date` 转 `date`、`local_id`/`tmdb_id`/`rank` 转 `integer`、`popularity` 转 `decimal:3`）
  - _需求：1.5、2.5、3.5_

- [x] 2. 创建 Repository 接口与实现
  - [x] 2.1 创建 `app/Repositories/Contracts/MediaListSnapshotRepositoryInterface.php`，声明 `findByListType(ListType $listType, ?string $snapshotDate): Collection` 和 `findLatestDate(ListType $listType): ?string` 方法签名
  - [x] 2.2 创建 `app/Repositories/MediaListSnapshotRepository.php`，继承 `BaseRepository`，实现 `findLatestDate`（查询指定 `list_type` 下最大 `snapshot_date`）和 `findByListType`（`snapshotDate` 为 null 时先调 `findLatestDate`，再按 `(list_type, snapshot_date, rank)` 索引查询，结果按 `rank` 升序）
  - [x] 2.3 在 `AppServiceProvider::register()` 中注册 `MediaListSnapshotRepositoryInterface` → `MediaListSnapshotRepository` 绑定
  - _需求：1.2、1.3、1.5、2.2、2.3、2.5、3.2、3.3、3.5_

- [x] 3. 创建 FormRequest 与 API Resources
  - [x] 3.1 创建 `app/Http/Requests/GetMediaListRequest.php`，定义 `snapshot_date` 参数规则 `nullable|date_format:Y-m-d`，`messages()` 返回中文错误信息
  - [x] 3.2 创建 `app/Http/Resources/MovieSnapshotResource.php`，输出快照字段（`rank`、`popularity`、`snapshot_date`（`Y-m-d`）、`tmdb_id`、`local_id`）和电影实体字段（`id`、`title`、`original_title`、`release_date`（`Y-m-d`）、`poster_url`（`ImageHelper::url($path, 'w342')`）、`vote_average`、`status`），实体不存在时字段输出 null
  - [x] 3.3 创建 `app/Http/Resources/TvShowSnapshotResource.php`，结构同上，实体字段改为 `id`、`name`、`original_name`、`first_air_date`（`Y-m-d`）、`poster_url`（`w342`）、`vote_average`、`status`
  - [x] 3.4 创建 `app/Http/Resources/PersonSnapshotResource.php`，结构同上，实体字段改为 `id`、`name`、`known_for_department`（字符串）、`profile_url`（`ImageHelper::url($path, 'w185')`）、`gender`
  - _需求：1.4、1.8、2.4、2.8、3.4、3.8、5.3、5.4、5.5、5.6_

- [x] 4. 创建 MediaListSnapshotService
  - [x] 4.1 创建 `app/Services/MediaListSnapshotService.php`，构造函数注入 `MediaListSnapshotRepositoryInterface`、`MovieRepositoryInterface`、`TvShowRepositoryInterface`、`PersonRepositoryInterface`
  - [x] 4.2 实现 `getMovieList(ListType $listType, ?string $snapshotDate): array` 方法：调用 Repository 获取快照集合，调用私有方法 `resolveMovieEntities` 批量关联电影实体，返回 `['list' => [...], 'snapshot_date' => ?string]`
  - [x] 4.3 实现私有方法 `resolveMovieEntities(Collection $snapshots): array`：分离 `local_id` 非 NULL 的快照批量 `findByIds`，分离 `local_id` 为 NULL 的快照批量 `findByTmdbIds`，合并结果按快照顺序排列，找不到的填 null
  - [x] 4.4 实现 `getTvShowList` 和 `getPersonList` 方法，逻辑与 `getMovieList` 相同，分别调用对应的 `resolveTvShowEntities` 和 `resolvePersonEntities` 私有方法
  - _需求：1.6、1.7、1.9、1.10、1.11、2.6、2.7、2.9、2.10、2.11、3.6、3.7、3.9、3.10、3.11_

- [ ]* 4.5 为 Service 实体关联降级解析逻辑编写单元测试
  - 位置：`tests/Unit/Services/MediaListSnapshotServiceTest.php`
  - 覆盖三种情况：`local_id` 非 NULL 时关联到 `local_id` 对应实体；`local_id` 为 NULL 且 `tmdb_id` 存在时关联到 `tmdb_id` 对应实体；`local_id` 为 NULL 且 `tmdb_id` 不存在时实体字段全部为 null 且不抛异常
  - 使用 mock Repository，不依赖真实数据库
  - _需求：1.6、1.7、2.6、2.7、3.6、3.7_

- [x] 5. 创建 Controller 并注册路由
  - [x] 5.1 创建 `app/Http/Controllers/Api/MediaListSnapshotController.php`，继承 `BaseController`，注入 `MediaListSnapshotService`，实现 10 个方法（`movieNowPlaying`、`movieUpcoming`、`movieTrendingDay`、`movieTrendingWeek`、`tvAiringToday`、`tvOnTheAir`、`tvTrendingDay`、`tvTrendingWeek`、`personTrendingDay`、`personTrendingWeek`），每个方法接收 `GetMediaListRequest`，调用对应 Service 方法，返回 `$this->success($result)`
  - [x] 5.2 在 `routes/api.php` 的 `auth:api` middleware 组内添加 `Route::prefix('media-lists')` 路由组，注册全部 10 条 GET 路由
  - _需求：1.1、2.1、3.1、4.1、4.2、4.3、5.1、5.2_

- [x] 6. 检查点 — 确保所有测试通过
  - 确保所有测试通过，如有疑问请向用户确认。

- [x] 7. 编写 Feature Test
  - [ ]* 7.1 创建 `tests/Feature/MediaListSnapshots/MediaListSnapshotTest.php`，mock `MediaListSnapshotService`，覆盖以下场景：
    - `test_unauthenticated_request_returns_401`（对全部 10 个接口验证）
    - `test_returns_movie_list_with_correct_structure`（验证 `code:0`、`data.list`、`data.snapshot_date` 及条目字段）
    - `test_returns_tv_show_list_with_correct_structure`
    - `test_returns_person_list_with_correct_structure`
    - `test_invalid_snapshot_date_returns_422`
    - `test_empty_result_returns_null_snapshot_date`
    - `test_entity_fields_are_null_when_entity_not_found`
    - `test_poster_url_is_null_when_poster_path_is_null`
  - _需求：1.1–1.11、2.1–2.11、3.1–3.11、4.1–4.3、5.1–5.6_

- [ ]* 7.2 编写属性测试：响应结构完整性（属性 1）
  - 生成随机数量（1–100）的快照条目（mock Service 返回），验证响应包含 `code:0`、`data.list`（数组）、`data.snapshot_date`，以及 `data.list` 每个条目包含所有规定快照字段
  - 运行 ≥ 100 次迭代
  - **属性 1：响应结构完整性**
  - **验证：需求 1.8、1.11、2.8、2.11、3.8、3.11、5.1、5.2**

- [ ]* 7.3 编写属性测试：非法日期参数拒绝（属性 5）
  - 生成各种非 `Y-m-d` 格式字符串（随机字符串、斜杠分隔、无分隔符、空字符串等），验证全部返回 `code:422`
  - 运行 ≥ 100 次迭代
  - **属性 5：非法日期参数拒绝**
  - **验证：需求 1.4、2.4、3.4**

- [ ]* 7.4 编写属性测试：图片 URL 转换（属性 3）
  - 生成随机路径字符串（包括 null、各种路径格式），验证 `ImageHelper::url($path, 'w342')` 输出符合规则（非 null 时为完整 URL，null 时输出 null）
  - 运行 ≥ 100 次迭代
  - **属性 3：图片 URL 转换**
  - **验证：需求 5.3、5.4、5.5**

- [ ]* 7.5 编写属性测试：日期字段格式化（属性 4）
  - 生成随机日期（Carbon 对象、date 字符串），验证 Resource 输出的日期字段符合 `Y-m-d` 格式正则 `/^\d{4}-\d{2}-\d{2}$/`
  - 运行 ≥ 100 次迭代
  - **属性 4：日期字段格式化**
  - **验证：需求 5.6**

- [x] 8. 最终检查点 — 确保所有测试通过
  - 确保所有测试通过，运行 `./vendor/bin/pint` 格式化代码，如有疑问请向用户确认。

## 备注

- 标有 `*` 的子任务为可选项，可跳过以加快 MVP 交付
- 每个任务引用了具体需求条款以保证可追溯性
- 属性测试使用 PHPUnit 配合手动参数生成（循环随机值），每个属性 ≥ 100 次迭代
- Service 层实体关联解析采用批量查询策略，避免 N+1 问题
