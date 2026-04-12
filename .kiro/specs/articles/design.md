# 设计文档：专题（Articles）功能模块

## 概述

专题（Article）模块是 Filmly Management Backend 的图文内容管理功能，允许编辑人员创建以 Markdown 为载体的图文专题，在正文中内嵌影视实体卡片，系统自动解析并维护引用索引，支持按实体反向查询关联专题。

本模块是项目中少数几个**可写业务表**之一（`articles` 和 `article_items`），与只读的 TMDB 采集数据表共存于同一数据库。

### 核心功能

- 专题 CRUD（创建、列表、详情、更新、删除）
- Media 占位符解析：`::media{type="movie" id="123"}` → `article_items` 同步
- 详情接口返回 Entities Map（批量预加载，防 N+1）
- 列表按状态筛选、多字段排序
- 按实体反向查询关联专题

---

## 架构

遵循项目标准分层架构：

```
routes/api.php
  └── StoreArticleRequest / UpdateArticleRequest / ListArticleRequest / ListArticleItemRequest
        └── ArticleController / ArticleItemController
              └── ArticleService
                    ├── ArticleRepository（操作 articles 表）
                    └── ArticleItemRepository（操作 article_items 表）
```

### 架构决策

**决策 1：ArticleService 同时注入两个 Repository**

`article_items` 的同步逻辑（先删后插）与 `articles` 的写入操作必须在同一事务中完成，因此 `ArticleService` 同时持有 `ArticleRepository` 和 `ArticleItemRepository`，由 Service 层协调跨表操作。Repository 层各自只操作单一 Model，符合项目规范。

**决策 2：占位符解析在 Service 层完成**

Media 占位符解析是业务逻辑，放在 `ArticleService` 的 `private` 方法中，不下沉到 Repository，也不上浮到 Controller。

**决策 3：Entities Map 构建策略**

详情接口需要返回正文中所有引用实体的数据。为防止 N+1，Service 层先从 `article_items` 按 `entity_type` 分组，再对每种类型发起一次 `whereIn` 批量查询，最终在内存中组装 map。各实体类型的查询通过 Repository 接口分发。

**决策 4：反向查询独立 Controller**

`GET /api/article-items` 是独立的查询接口，遵循项目"子资源用独立路由 + 参数过滤"的约定，由 `ArticleItemController` 处理，不嵌套在 `ArticleController` 下。

---

## 组件与接口

### 路由

```
# 专题 CRUD（auth:api）
GET    /api/articles              # 列表（支持 status 筛选、排序、分页）
POST   /api/articles              # 创建
GET    /api/articles/{id}         # 详情（含 entities map）
PUT    /api/articles/{id}         # 更新
DELETE /api/articles/{id}         # 删除

# 反向查询（auth:api）
GET    /api/article-items         # 按实体查询关联专题（entity_type + entity_id 必填）
```

### Controller

**`ArticleController`**（继承 `BaseController`）

| 方法 | 路由 | 说明 |
|------|------|------|
| `index` | GET /api/articles | 调用 `articleService->getList()`，返回分页列表 |
| `store` | POST /api/articles | 调用 `articleService->create()`，返回创建后的详情 |
| `show` | GET /api/articles/{id} | 调用 `articleService->findById()`，返回详情含 entities |
| `update` | PUT /api/articles/{id} | 调用 `articleService->update()`，返回更新后的详情 |
| `destroy` | DELETE /api/articles/{id} | 调用 `articleService->delete()`，返回 `success` |

```php
public function __construct(private readonly ArticleService $articleService) {}

public function store(StoreArticleRequest $request): JsonResponse
{
    $article = $this->articleService->create($request->validated(), $request->user()->id);
    return $this->success(new ArticleResource($article));
}
```

**`ArticleItemController`**（继承 `BaseController`）

| 方法 | 路由 | 说明 |
|------|------|------|
| `index` | GET /api/article-items | 调用 `articleItemService->getByEntity()`，返回分页列表 |

### Service

**`ArticleService`**

| 方法 | 返回类型 | 说明 |
|------|---------|------|
| `getList(array $filters)` | `LengthAwarePaginator` | 列表查询，支持 status 筛选和排序 |
| `findById(int $id)` | `Article` | 详情查询，含 entities map，不存在时抛 404 |
| `create(array $data, int $userId)` | `Article` | 创建专题，解析占位符，同步 article_items |
| `update(int $id, array $data)` | `Article` | 更新专题，重新解析占位符，全量同步 article_items |
| `delete(int $id)` | `void` | 删除专题及关联 article_items（事务） |
| `private parsePlaceholders(string $content)` | `array` | 解析 content 中的 Media 占位符，返回去重后的 `[(entity_type, entity_id)]` 数组 |
| `private buildEntitiesMap(Article $article)` | `array` | 按 entity_type 分组批量查询，构建 entities map |
| `private syncArticleItems(int $articleId, array $items)` | `void` | 全量同步 article_items（先删后插） |

**`ArticleItemService`**（或直接在 `ArticleItemController` 中注入 `ArticleItemRepository`）

| 方法 | 返回类型 | 说明 |
|------|---------|------|
| `getByEntity(array $filters)` | `LengthAwarePaginator` | 按 entity_type + entity_id 查询关联专题 |

### Repository

**`ArticleRepositoryInterface` / `ArticleRepository`**

| 方法 | 返回类型 | 说明 |
|------|---------|------|
| `paginateWithFilters(array $filters)` | `LengthAwarePaginator` | 支持 status 筛选、sort/order 排序 |
| `findById(int $id)` | `?Article` | 按 id 查询，不预加载 |
| `create(array $data)` | `Article` | 创建记录 |
| `update(Article $article, array $data)` | `Article` | 更新记录 |
| `delete(int $id)` | `void` | 删除记录 |

**`ArticleItemRepositoryInterface` / `ArticleItemRepository`**

| 方法 | 返回类型 | 说明 |
|------|---------|------|
| `paginateByEntity(string $entityType, int $entityId, array $filters)` | `LengthAwarePaginator` | 按实体反向查询，预加载 article |
| `deleteByArticleId(int $articleId)` | `void` | 删除指定专题的所有 article_items |
| `insertBatch(int $articleId, array $items)` | `void` | 批量插入 article_items |

### FormRequest

| 类名 | 用途 |
|------|------|
| `StoreArticleRequest` | 创建专题参数验证 |
| `UpdateArticleRequest` | 更新专题参数验证 |
| `ListArticleRequest` | 列表查询参数验证（status 筛选、排序、分页） |
| `ListArticleItemRequest` | 反向查询参数验证（entity_type、entity_id 必填） |

### Resource

| 类名 | 用途 |
|------|------|
| `ArticleResource` | 详情响应（含 items 分组数组、cover_path） |
| `ArticleListResource` | 列表响应（不含 content 字段） |
| `ArticleItemResource` | 反向查询响应（含关联专题摘要） |

### Enum

| 类名 | 值 | 说明 |
|------|-----|------|
| `ArticleStatus` | `draft` / `published` / `archived` | 专题状态 |
| `ArticleEntityType` | `movie` / `collection` / `tv_show` / `tv_season` / `tv_episode` / `person` / `production_company` / `tv_network` / `genre` / `keyword` | 支持的实体类型 |

---

## 数据模型

### articles 表

| 字段 | 类型 | 约束 | 说明 |
|------|------|------|------|
| `id` | bigint | PK, 自增 | 主键 |
| `title` | varchar(500) | NOT NULL | 专题标题 |
| `slug` | varchar(255) | UNIQUE, nullable | URL 友好标识符，允许多个 null |
| `cover_path` | varchar(500) | nullable | 主图相对路径 |
| `content` | longtext | NOT NULL | Markdown 正文 |
| `status` | enum | NOT NULL, default `draft` | `draft` / `published` / `archived` |
| `sort_order` | int | NOT NULL, default 0 | 排序优先级 |
| `published_at` | timestamp | nullable | 发布时间 |
| `created_by` | bigint | FK → users.id, nullable | 创建者 |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

索引：`status`、`sort_order`

### article_items 表

| 字段 | 类型 | 约束 | 说明 |
|------|------|------|------|
| `article_id` | bigint | FK → articles.id, CASCADE DELETE | 所属专题 |
| `entity_type` | varchar(50) | NOT NULL | 实体类型（ArticleEntityType 枚举值） |
| `entity_id` | bigint | NOT NULL | 实体本地主键 |

唯一约束：`(article_id, entity_type, entity_id)`
索引：`(entity_type, entity_id)`（支持反向查询）

### Article Model

```php
class Article extends Model
{
    protected $fillable = [
        'title', 'slug', 'cover_path', 'content',
        'status', 'sort_order', 'published_at', 'created_by',
    ];

    protected $casts = [
        'status' => ArticleStatus::class,
        'sort_order' => 'integer',
        'published_at' => 'datetime',
        'created_by' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ArticleItem::class);
    }
}
```

### ArticleItem Model

```php
class ArticleItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['article_id', 'entity_type', 'entity_id'];

    protected $casts = [
        'entity_type' => ArticleEntityType::class,
        'entity_id' => 'integer',
        'article_id' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
```

### Items Map 结构

详情接口 `data` 中的 `items` 字段，按实体类型分组，每种类型对应一个数组，没有引用的类型返回空数组：

```json
{
  "items": {
    "movies": [
      { "id": 123, "title": "星际穿越", "poster_path": "/abc.jpg" }
    ],
    "collections": [],
    "tv_shows": [],
    "tv_seasons": [],
    "tv_episodes": [],
    "persons": [
      { "id": 456, "name": "克里斯托弗·诺兰", "profile_path": "/def.jpg" }
    ],
    "production_companies": [],
    "tv_networks": [],
    "genres": [],
    "keywords": []
  }
}
```

图片字段输出相对路径（如 `/abc.jpg`），由 `ImageHelper::url()` 处理，不拼接域名和 size 前缀。前端自行构建 `id → entity` 的 map。

### 各实体类型在 Items Map 中的输出字段

| entity_type | 输出字段 |
|-------------|---------|
| `movie` | `id`, `title`, `poster_path`（w342，相对路径） |
| `collection` | `id`, `name`, `poster_path`（w342，相对路径） |
| `tv_show` | `id`, `name`, `poster_path`（w342，相对路径） |
| `tv_season` | `id`, `name`, `poster_path`（w342，相对路径）, `season_number` |
| `tv_episode` | `id`, `name`, `still_path`（w300，相对路径）, `episode_number` |
| `person` | `id`, `name`, `profile_path`（w185，相对路径） |
| `production_company` | `id`, `name`, `logo_path`（w185，相对路径） |
| `tv_network` | `id`, `name`, `logo_path`（w185，相对路径） |
| `genre` | `id`, `name` |
| `keyword` | `id`, `name` |

---

## 正确性属性

*属性（Property）是在系统所有合法执行中都应成立的特征或行为——本质上是对系统应做什么的形式化陈述。属性是人类可读规范与机器可验证正确性保证之间的桥梁。*

### 属性 1：占位符解析与 article_items 同步一致性

对于任意包含合法 Media 占位符的 `content` 字符串，保存专题后，`article_items` 表中该专题的记录集合应与从 `content` 中解析出的 `(entity_type, entity_id)` 去重集合完全一致。

**验证：需求 3.1、3.2**

### 属性 2：article_items 同步幂等性

对于任意专题，使用相同的 `content` 多次更新（无论更新几次），`article_items` 表中该专题的记录集合应始终保持不变。

**验证：需求 3.2、3.8**

### 属性 3：状态筛选结果一致性

对于任意 `status` 筛选值（`draft` / `published` / `archived`），列表接口返回的所有专题记录的 `status` 字段都应等于筛选值，不存在其他状态的记录混入。

**验证：需求 5.1**

### 属性 4：排序结果正确性

对于任意数量的专题记录，按 `sort_order asc` 排序时，返回列表中相邻两条记录满足 `前一条.sort_order ≤ 后一条.sort_order`。

**验证：需求 5.2**

### 属性 5：反向查询结果一致性

对于任意 `(entity_type, entity_id)` 组合，反向查询接口返回的所有记录都应是引用了该实体的专题，不存在未引用该实体的专题混入。

**验证：需求 6.1**

---

## 错误处理

### 业务异常

| 场景 | 异常 | 响应 |
|------|------|------|
| 专题不存在（GET/PUT/DELETE） | `AppException('专题不存在', 404)` | `{"code": 404, "message": "专题不存在", "data": null}` |
| slug 重复 | `AppException('slug 已被使用', 422)` | `{"code": 422, "message": "slug 已被使用", "data": null}` |
| 未认证 | JWT middleware 处理 | `{"code": 401, "message": "未认证，请先登录", "data": null}` |
| 参数验证失败 | FormRequest 处理 | `{"code": 422, "message": "...", "data": null}` |

### 占位符解析容错

- `type` 不在 `ArticleEntityType` 枚举中：静默忽略，不写入 `article_items`，不抛异常
- `id` 不是合法正整数（非数字、负数、零）：静默忽略
- 格式不完整的占位符（缺少 type 或 id 属性）：静默忽略

### Entities Map 容错

- `entity_id` 在对应实体表中不存在：该键值设为 `null`，不影响其他实体的返回
- 实体表查询失败：不影响专题主体数据的返回，entities map 中对应键值设为 `null`

### 事务保障

删除专题时，`articles` 记录和 `article_items` 记录的删除在同一数据库事务中完成：

```php
DB::transaction(function () use ($id): void {
    $this->articleItemRepository->deleteByArticleId($id);
    $this->articleRepository->delete($id);
});
```

创建/更新专题时，`articles` 写入和 `article_items` 同步同样在事务中完成，保证数据一致性。

---

## 测试策略

本模块属于**可写业务表**，测试使用 `RefreshDatabase` trait + SQLite in-memory，无需 mock Repository。

### Feature Test（主要）

位置：`tests/Feature/Articles/`

**必须覆盖的场景：**

| 测试文件 | 覆盖场景 |
|---------|---------|
| `ArticleListTest.php` | 未认证返回 401；正常分页列表；status 筛选；排序；列表不含 content 字段 |
| `ArticleDetailTest.php` | 未认证返回 401；正常详情含 entities；专题不存在返回 404；cover_url 格式正确 |
| `ArticleStoreTest.php` | 未认证返回 401；正常创建；title 必填验证；slug 格式验证；slug 重复返回 422；status=published 且 slug 为 null 返回 422；占位符解析写入 article_items |
| `ArticleUpdateTest.php` | 未认证返回 401；正常更新；更新后 article_items 全量同步；专题不存在返回 404 |
| `ArticleDeleteTest.php` | 未认证返回 401；正常删除；级联删除 article_items；专题不存在返回 404 |
| `ArticleItemListTest.php` | 未认证返回 401；正常反向查询；entity_type 必填验证；entity_id 必填验证 |

**属性测试（基于 PBT）：**

使用 [Pest](https://pestphp.com/) + [pest-plugin-faker](https://github.com/pestphp/pest-plugin-faker) 或 PHPUnit + 自定义数据生成器实现属性测试，每个属性最少运行 100 次迭代。

```php
// 属性 1：占位符解析与 article_items 同步一致性
// Feature: articles, Property 1: 占位符解析后 article_items 与解析结果完全一致
it('syncs article_items to match parsed placeholders for any content', function () {
    // 生成随机数量的合法占位符，拼接成 content
    // 创建专题，验证 article_items 记录集合与占位符集合一致
})->repeat(100);

// 属性 2：article_items 同步幂等性
// Feature: articles, Property 2: 相同 content 多次更新后 article_items 不变
it('produces identical article_items when updated with same content multiple times', function () {
    // 创建专题，记录 article_items，再次用相同 content 更新，验证 article_items 不变
})->repeat(100);

// 属性 3：状态筛选结果一致性
// Feature: articles, Property 3: 按 status 筛选时所有结果的 status 等于筛选值
it('returns only articles matching the requested status', function () {
    // 创建随机数量的不同状态专题，按 status 筛选，验证结果全部匹配
})->repeat(100);

// 属性 4：排序结果正确性
// Feature: articles, Property 4: 按 sort_order asc 排序时结果单调不减
it('returns articles in non-decreasing sort_order when sorted ascending', function () {
    // 创建随机 sort_order 的多条专题，验证返回顺序单调不减
})->repeat(100);

// 属性 5：反向查询结果一致性
// Feature: articles, Property 5: 反向查询结果中所有记录都引用了指定实体
it('returns only articles that reference the queried entity', function () {
    // 创建引用不同实体的专题，按 entity_type+entity_id 查询，验证结果全部引用了指定实体
})->repeat(100);
```

### Unit Test（按需）

位置：`tests/Unit/Services/`

**`ArticleServiceTest.php`**：测试 `parsePlaceholders` 私有方法的边界情况（通过反射或提取为 public static 方法测试）：
- 空 content 返回空数组
- 非法 type 被忽略
- 非法 id（负数、零、非数字）被忽略
- 重复占位符去重
- 格式不完整的占位符被忽略

### 测试数据策略

- `articles` 和 `article_items` 使用 `RefreshDatabase` + Factory 创建测试数据
- 只读实体（movies、persons 等）在 Entities Map 测试中使用 mock，不依赖真实数据
- 属性测试中的随机数据通过 Faker 生成，确保覆盖边界值
