# 需求文档：专题（Articles）功能模块

## 简介

专题（Article）是 Filmly Management Backend 的图文内容管理模块。编辑人员可以创建以 Markdown 为载体的图文专题，在正文任意位置内嵌影视实体卡片（电影、人物、剧集等），系统自动解析并维护引用索引，支持按实体反向查询关联专题。

---

## 词汇表

- **Article（专题）**：由标题、主图、Markdown 正文、状态、排序优先级等字段构成的图文内容单元，存储于 `articles` 表。
- **ArticleItem（专题引用项）**：`article_items` 冗余索引表中的一条记录，表示某篇专题引用了某个实体，用于反向查询与引用校验。
- **Media 占位符**：正文中内嵌实体的自定义语法，格式为 `::media{type="<entity_type>" id="<entity_id>"}`，其中 `id` 为本地数据库主键。
- **Entity（实体）**：被专题引用的影视数据对象，支持类型：`movie`、`collection`、`tv_show`、`tv_season`、`tv_episode`、`person`、`production_company`、`tv_network`、`genre`、`keyword`。
- **Entities Map（实体映射）**：详情接口返回的附加数据结构，包含正文中所有被引用实体的完整数据，键为 `"<entity_type>:<entity_id>"`。
- **ArticleService**：专题业务逻辑层，负责 CRUD 操作、占位符解析、article_items 同步。
- **ArticleRepository**：专题数据访问层，封装 `articles` 表的所有查询。
- **ArticleItemRepository**：专题引用项数据访问层，封装 `article_items` 表的所有查询。
- **slug**：专题的 URL 友好唯一标识符，由字母、数字、连字符组成。
- **cover_path**：专题主图的相对路径，通过 `ImageHelper::url()` 输出完整 URL。

---

## 需求列表

### 需求 1：专题基础 CRUD

**用户故事：** 作为管理员，我希望能够创建、查看、更新和删除专题，以便管理影视图文内容。

#### 验收标准

1. WHEN 请求 `POST /api/articles` 且请求体包含合法的 `title`、`slug`、`content` 字段，THE ArticleService SHALL 在 `articles` 表创建一条新记录，并将 `created_by` 设置为当前认证用户的 `id`。
2. WHEN 请求 `GET /api/articles`，THE ArticleService SHALL 返回按 `sort_order` 升序、`created_at` 降序排列的专题分页列表，每页默认 20 条，最大 100 条。
3. WHEN 请求 `GET /api/articles/{id}` 且专题存在，THE ArticleService SHALL 返回该专题的完整字段及 entities map。
4. WHEN 请求 `PUT /api/articles/{id}` 且专题存在，THE ArticleService SHALL 更新该专题的可编辑字段。
5. WHEN 请求 `DELETE /api/articles/{id}` 且专题存在，THE ArticleService SHALL 删除该专题记录及其所有关联的 `article_items` 记录。
6. IF 请求 `GET /api/articles/{id}` 且专题不存在，THEN THE ArticleService SHALL 抛出 404 异常，响应 `{"code": 404, "message": "专题不存在", "data": null}`。
7. IF 创建或更新专题时 `slug` 与已有记录重复，THEN THE ArticleService SHALL 抛出 422 异常，响应 `{"code": 422, "message": "slug 已被使用", "data": null}`。
8. THE Article 模型 SHALL 包含字段：`id`、`title`、`slug`、`cover_path`、`content`、`status`、`sort_order`、`published_at`、`created_by`、`created_at`、`updated_at`。
9. THE Article 模型 SHALL 将 `status` 字段声明为 `ArticleStatus` enum，枚举值为 `draft`、`published`、`archived`。

---

### 需求 2：专题字段验证

**用户故事：** 作为系统，我希望对专题的输入字段进行严格验证，以便保证数据完整性。

#### 验收标准

1. WHEN 创建专题时，THE StoreArticleRequest SHALL 验证 `title` 为必填字符串，最大长度 500 字符。
2. WHEN 创建专题时，THE StoreArticleRequest SHALL 验证 `slug` 为可选字段（nullable），若有值则仅允许小写字母、数字和连字符，最大长度 255 字符。
3. WHEN 创建专题时，THE StoreArticleRequest SHALL 验证 `content` 为必填字符串。
4. WHEN 创建或更新专题时，THE StoreArticleRequest / UpdateArticleRequest SHALL 验证 `cover_path` 为可选字符串，最大长度 500 字符。
5. WHEN 创建或更新专题时，THE StoreArticleRequest / UpdateArticleRequest SHALL 验证 `status` 为可选字段，值必须为 `draft`、`published`、`archived` 之一，默认值为 `draft`。
6. WHEN 创建或更新专题时，THE StoreArticleRequest / UpdateArticleRequest SHALL 验证 `sort_order` 为可选整数，最小值 0，默认值 0。
7. WHEN 创建或更新专题时，THE StoreArticleRequest / UpdateArticleRequest SHALL 验证 `published_at` 为可选日期时间字符串，格式为 ISO 8601。
8. IF 任意必填字段缺失或格式不合法，THEN THE FormRequest SHALL 返回 422 响应，`message` 字段包含具体的中文错误说明。
9. IF 创建或更新专题时 `status` 为 `published` 且 `slug` 为 null，THEN THE StoreArticleRequest / UpdateArticleRequest SHALL 返回 422 响应，`message` 为"发布专题必须填写 slug"。

---

### 需求 3：Media 占位符解析与 article_items 同步

**用户故事：** 作为系统，我希望在保存专题时自动解析正文中的 Media 占位符并维护引用索引，以便支持反向查询和引用校验。

#### 验收标准

1. WHEN 创建或更新专题时，THE ArticleService SHALL 解析 `content` 字段中所有符合 `::media{type="<entity_type>" id="<entity_id>"}` 格式的占位符。
2. WHEN 解析完成后，THE ArticleService SHALL 全量同步 `article_items` 表：先删除该专题的所有旧记录，再批量插入本次解析到的所有 `(article_id, entity_type, entity_id)` 记录，同一专题内相同的 `(entity_type, entity_id)` 组合只保留一条。
3. THE ArticleItem 模型 SHALL 包含字段：`article_id`、`entity_type`、`entity_id`，并在 `(article_id, entity_type, entity_id)` 上建立唯一约束。
4. THE ArticleItem 模型 SHALL 将 `entity_type` 字段声明为 `ArticleEntityType` enum，枚举值为 `movie`、`collection`、`tv_show`、`tv_season`、`tv_episode`、`person`、`production_company`、`tv_network`、`genre`、`keyword`。
5. IF `content` 中某个占位符的 `type` 值不在支持的实体类型列表中，THEN THE ArticleService SHALL 忽略该占位符，不写入 `article_items`，不抛出异常。
6. IF `content` 中某个占位符的 `id` 值不是合法正整数，THEN THE ArticleService SHALL 忽略该占位符，不写入 `article_items`，不抛出异常。
7. WHEN 删除专题时，THE ArticleService SHALL 在同一数据库事务中删除 `articles` 记录及其所有 `article_items` 记录。
8. FOR ALL 合法的 `content` 字符串，解析后再将 `article_items` 重新写入，结果 SHALL 与直接解析原始 `content` 得到的结果一致（幂等性）。

---

### 需求 4：专题详情接口返回 Entities Map

**用户故事：** 作为 API 消费方，我希望获取专题详情时能一并拿到正文中所有引用实体的数据，以便前端无需再发额外请求即可渲染实体卡片。

#### 验收标准

1. WHEN 请求 `GET /api/articles/{id}`，THE ArticleService SHALL 读取该专题的 `article_items`，按 `entity_type` 分组，批量查询各实体表，构建 entities map。
2. THE ArticleResource SHALL 在响应的 `data` 中包含 `items` 字段，其结构为按实体类型分组的对象，每个键对应一个实体数组（无引用时为空数组）：`{ "movies": [...], "tv_shows": [...], ... }`。
3. WHILE 构建 items map 时，THE ArticleService SHALL 对每种 entity_type 只发起一次批量查询，禁止对每个 entity_id 单独查询（防止 N+1）。
4. IF `article_items` 中某个 `entity_id` 在对应实体表中不存在，THEN THE ArticleService SHALL 在对应类型数组中忽略该条记录，不抛出异常，不影响其他实体的返回。
5. THE ArticleResource SHALL 在响应中输出 `cover_url` 字段，值为 `ImageHelper::url($this->cover_path, 'w780')`，`cover_path` 为 null 时输出 null。

---

### 需求 5：专题列表筛选与排序

**用户故事：** 作为管理员，我希望能够按状态筛选专题列表并控制排序，以便快速找到目标内容。

#### 验收标准

1. WHEN 请求 `GET /api/articles?status=published`，THE ArticleRepository SHALL 仅返回 `status` 为 `published` 的专题记录。
2. WHEN 请求 `GET /api/articles?sort=sort_order&order=asc`，THE ArticleRepository SHALL 按 `sort_order` 升序排列结果。
3. THE ListArticleRequest SHALL 支持的排序字段白名单为：`sort_order`、`created_at`、`published_at`，默认排序为 `sort_order` 升序。
4. IF 请求的 `status` 参数值不在 `draft`、`published`、`archived` 之内，THEN THE ListArticleRequest SHALL 返回 422 响应。
5. THE ArticleListResource SHALL 在列表响应中输出字段：`id`、`title`、`slug`、`cover_url`、`status`、`sort_order`、`published_at`、`created_at`、`updated_at`，不包含 `content` 字段。

---

### 需求 6：按实体反向查询关联专题

**用户故事：** 作为管理员，我希望能够查询某个实体（如某部电影）被哪些专题引用，以便了解内容关联关系。

#### 验收标准

1. WHEN 请求 `GET /api/article-items?entity_type=movie&entity_id=123`，THE ArticleItemRepository SHALL 查询 `article_items` 表，返回所有引用了该实体的专题分页列表。
2. THE ListArticleItemRequest SHALL 验证 `entity_type` 为必填字段，值必须在支持的实体类型枚举中。
3. THE ListArticleItemRequest SHALL 验证 `entity_id` 为必填正整数。
4. THE ArticleItemResource SHALL 在响应中包含关联专题的摘要信息：`article_id`、`title`、`slug`、`status`、`cover_url`。
5. WHILE 查询 article_items 时，THE ArticleItemRepository SHALL 通过 `with('article')` 预加载关联专题，避免 N+1 查询。

---

### 需求 7：认证与权限控制

**用户故事：** 作为系统，我希望所有专题接口都需要认证，以便保护内容管理操作的安全性。

#### 验收标准

1. THE ArticleController SHALL 注册在 `auth:api` middleware 组内，所有专题接口均需携带有效 JWT Token。
2. IF 请求专题接口时未携带 Token 或 Token 无效，THEN THE System SHALL 返回 `{"code": 401, "message": "未认证，请先登录", "data": null}`。
3. THE ArticleItemController SHALL 注册在 `auth:api` middleware 组内，反向查询接口同样需要认证。

---

### 需求 8：数据库迁移

**用户故事：** 作为开发者，我希望通过 Laravel Migration 创建专题相关数据表，以便在本项目中管理可写业务表的结构。

#### 验收标准

1. THE Migration SHALL 创建 `articles` 表，包含字段：`id`（bigint PK 自增）、`title`（varchar 500，非空）、`slug`（varchar 255，UNIQUE，可为空（nullable））、`cover_path`（varchar 500，可为空）、`content`（longtext，非空）、`status`（enum: `draft`/`published`/`archived`，默认 `draft`）、`sort_order`（int，默认 0）、`published_at`（timestamp，可为空）、`created_by`（bigint，外键 → `users.id`，可为空）、`created_at`/`updated_at`（timestamp）。
2. THE Migration SHALL 在 `articles` 表的 `status` 字段和 `sort_order` 字段上建立索引。
3. THE Migration SHALL 创建 `article_items` 表，包含字段：`article_id`（bigint，外键 → `articles.id`，级联删除）、`entity_type`（varchar 50，非空）、`entity_id`（bigint，非空）。
4. THE Migration SHALL 在 `article_items` 表的 `(article_id, entity_type, entity_id)` 上建立唯一约束。
5. THE Migration SHALL 在 `article_items` 表的 `(entity_type, entity_id)` 上建立索引，以支持反向查询。
