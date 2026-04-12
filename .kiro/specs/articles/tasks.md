# 实现任务：专题（Articles）功能模块

## 任务列表

### 1. 数据库迁移

- [x] 1.1 创建 `articles` 表 migration，包含所有字段、索引（status、sort_order）
- [x] 1.2 创建 `article_items` 表 migration，包含唯一约束 `(article_id, entity_type, entity_id)` 和反向查询索引 `(entity_type, entity_id)`

### 2. Enum 定义

- [x] 2.1 创建 `app/Enums/ArticleStatus.php`，枚举值：`draft`、`published`、`archived`
- [x] 2.2 创建 `app/Enums/ArticleEntityType.php`，枚举值：`movie`、`collection`、`tv_show`、`tv_season`、`tv_episode`、`person`、`production_company`、`tv_network`、`genre`、`keyword`

### 3. Model

- [x] 3.1 创建 `app/Models/Article.php`，定义 `$fillable`、`$casts`（status → ArticleStatus、published_at → datetime）、关联关系（`creator`、`items`）
- [x] 3.2 创建 `app/Models/ArticleItem.php`，定义 `$fillable`、`$casts`（entity_type → ArticleEntityType）、`$timestamps = false`、关联关系（`article`）

### 4. Repository

- [x] 4.1 创建 `app/Repositories/Contracts/ArticleRepositoryInterface.php`，声明方法：`paginateWithFilters`、`findById`、`create`、`update`、`delete`
- [x] 4.2 创建 `app/Repositories/ArticleRepository.php`，实现接口，`paginateWithFilters` 支持 status 筛选和 sort/order 排序
- [x] 4.3 创建 `app/Repositories/Contracts/ArticleItemRepositoryInterface.php`，声明方法：`paginateByEntity`、`deleteByArticleId`、`insertBatch`
- [x] 4.4 创建 `app/Repositories/ArticleItemRepository.php`，实现接口，`paginateByEntity` 通过 `with('article')` 预加载
- [x] 4.5 在 `AppServiceProvider::register()` 中注册两个 Repository 接口绑定

### 5. Service

- [x] 5.1 创建 `app/Services/ArticleService.php`，注入 `ArticleRepositoryInterface` 和 `ArticleItemRepositoryInterface`
- [x] 5.2 实现 `getList(array $filters): LengthAwarePaginator`
- [x] 5.3 实现 `findById(int $id): Article`，不存在时抛出 `AppException('专题不存在', 404)`
- [x] 5.4 实现 `create(array $data, int $userId): Article`，在事务中写入 article + 同步 article_items
- [x] 5.5 实现 `update(int $id, array $data): Article`，在事务中更新 article + 全量同步 article_items
- [x] 5.6 实现 `delete(int $id): void`，在事务中删除 article_items + article
- [x] 5.7 实现 `private parsePlaceholders(string $content): array`，解析 `::media{type="..." id="..."}` 格式，忽略非法 type/id，结果去重
- [x] 5.8 实现 `private buildEntitiesMap(Article $article): array`，按 entity_type 分组批量查询，构建 items map，不存在的实体设为 null
- [x] 5.9 实现 `private syncArticleItems(int $articleId, array $items): void`，先删后批量插入
- [x] 5.10 创建 `app/Services/ArticleItemService.php`，注入 `ArticleItemRepositoryInterface`，实现 `getByEntity(array $filters): LengthAwarePaginator`

### 6. FormRequest

- [x] 6.1 创建 `app/Http/Requests/StoreArticleRequest.php`，验证 title（必填，max:500）、slug（nullable，格式校验）、content（必填）、cover_path（nullable，max:500）、status（nullable，in enum）、sort_order（nullable，integer，min:0）、published_at（nullable，date）；status=published 且 slug 为 null 时返回 422
- [x] 6.2 创建 `app/Http/Requests/UpdateArticleRequest.php`，与 StoreArticleRequest 规则相同，所有字段改为 sometimes
- [x] 6.3 创建 `app/Http/Requests/ListArticleRequest.php`，验证 status（nullable，in enum）、sort（nullable，in 白名单：sort_order/created_at/published_at）、order（nullable，in:asc,desc）、page、per_page
- [x] 6.4 创建 `app/Http/Requests/ListArticleItemRequest.php`，验证 entity_type（必填，in ArticleEntityType）、entity_id（必填，integer，min:1）、page、per_page

### 7. API Resource

- [x] 7.1 创建 `app/Http/Resources/ArticleResource.php`，输出所有字段 + `cover_url`（ImageHelper::url，w780）+ `entities`（buildEntitiesMap 结果）
- [x] 7.2 创建 `app/Http/Resources/ArticleListResource.php`，输出字段：id、title、slug、cover_url、status、sort_order、published_at、created_at、updated_at，不含 content
- [x] 7.3 创建 `app/Http/Resources/ArticleItemResource.php`，输出：article_id + 关联专题摘要（title、slug、status、cover_url）

### 8. Controller

- [x] 8.1 创建 `app/Http/Controllers/Api/ArticleController.php`，注入 `ArticleService`，实现 `index`、`store`、`show`、`update`、`destroy`
- [x] 8.2 创建 `app/Http/Controllers/Api/ArticleItemController.php`，注入 `ArticleItemService`，实现 `index`

### 9. 路由注册

- [x] 9.1 在 `routes/api.php` 的 `auth:api` middleware 组内注册：
  - `GET /api/articles`
  - `POST /api/articles`
  - `GET /api/articles/{id}`
  - `PUT /api/articles/{id}`
  - `DELETE /api/articles/{id}`
  - `GET /api/article-items`

### 10. 测试

- [x] 10.1 创建 `tests/Feature/Articles/ArticleListTest.php`：未认证 401、分页列表、status 筛选、排序、列表不含 content
- [x] 10.2 创建 `tests/Feature/Articles/ArticleDetailTest.php`：未认证 401、正常详情含 entities、不存在返回 404、cover_url 格式
- [x] 10.3 创建 `tests/Feature/Articles/ArticleStoreTest.php`：未认证 401、正常创建、title 必填、slug 格式、slug 重复 422、published+无 slug 返回 422、占位符写入 article_items
- [x] 10.4 创建 `tests/Feature/Articles/ArticleUpdateTest.php`：未认证 401、正常更新、article_items 全量同步、不存在返回 404
- [x] 10.5 创建 `tests/Feature/Articles/ArticleDeleteTest.php`：未认证 401、正常删除、级联删除 article_items、不存在返回 404
- [x] 10.6 创建 `tests/Feature/Articles/ArticleItemListTest.php`：未认证 401、正常反向查询、entity_type 必填、entity_id 必填
- [x] 10.7 创建 `tests/Unit/Services/ArticleServiceTest.php`：parsePlaceholders 边界情况（空 content、非法 type、非法 id、去重、格式不完整）
- [x] 10.8 创建属性测试（PBT）：属性 1（占位符同步一致性）、属性 2（同步幂等性）、属性 3（状态筛选一致性）、属性 4（排序正确性）、属性 5（反向查询一致性），每个属性 ≥ 100 次迭代
