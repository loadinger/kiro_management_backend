# 数据流与业务规则

## 数据来源与读写边界

本项目数据库由两个系统共享：

- **数据采集项目**（独立）：负责从 TMDB 拉取数据写入数据库，本项目不干预
- **本项目**（管理后台 API）：绝大多数表只读，少量业务表可写

### 只读表（禁止 INSERT / UPDATE / DELETE）

以下表的数据完全由采集项目维护，本项目 API 只做查询输出：

```
genres / countries / languages / departments / jobs
persons / keywords / tv_networks / production_companies
movies / movie_genres / movie_keywords / movie_production_companies / movie_credits / movie_images
tv_shows / tv_show_genres / tv_show_keywords / tv_show_networks / tv_show_production_companies / tv_show_creators / tv_show_images
collections / collection_movies
tv_seasons / tv_season_images
tv_episodes / tv_episode_credits / tv_episode_images
media_list_snapshots / downloaded_images
```

### 可写表（本项目负责 CRUD）

```
users               # 管理员账号管理
（专题文章表，待规划）
```

---

## 异步关联字段处理规则

以下关系表存在异步关联字段，同步时 `person_id` / `movie_id` 初始为 NULL，由采集项目的 reconcile 步骤批量补填：

| 表 | NULL 字段 | 非 NULL 字段 |
|----|-----------|-------------|
| `movie_credits` | `person_id` | `person_tmdb_id` |
| `tv_show_creators` | `person_id` | `person_tmdb_id` |
| `tv_episode_credits` | `person_id` | `person_tmdb_id` |
| `collection_movies` | `movie_id` | `movie_tmdb_id` |

**API 层处理原则：**

- `person_id` 为 NULL 时，不报错，不过滤掉该条记录
- Resource 层输出时，`person_id` 为 NULL 的记录，`person` 关联对象输出为 `null`
- 禁止在 API 层触发 reconcile 操作（写入 `person_id`）
- 如需通过 person 查询关联内容，优先用 `person_tmdb_id` 做关联

```php
// Resource 中的 null 安全处理示例
'person' => $this->whenLoaded('person', fn() => $this->person
    ? new PersonResource($this->person)
    : ['tmdb_id' => $this->person_tmdb_id, 'resolved' => false]
),
```

---

## 图片 URL 拼接规则

数据库存储 TMDB 相对路径（如 `/abc123.jpg`），API 输出时统一由 Resource 层拼接完整 URL。

**基础 URL：** `https://image.tmdb.org/t/p/{size}{path}`

### 各实体图片字段与推荐 size

| 实体 | 字段 | 列表推荐 size | 详情推荐 size |
|------|------|-------------|-------------|
| movies | `poster_path` | `w342` | `w500` |
| movies | `backdrop_path` | `w780` | `original` |
| tv_shows | `poster_path` | `w342` | `w500` |
| tv_shows | `backdrop_path` | `w780` | `original` |
| tv_seasons | `poster_path` | `w342` | `w500` |
| tv_episodes | `still_path` | `w300` | `w780` |
| persons | `profile_path` | `w185` | `w342` |
| production_companies | `logo_path` | `w185` | `w342` |
| tv_networks | `logo_path` | `w185` | `w342` |
| collections | `poster_path` | `w342` | `w500` |
| collections | `backdrop_path` | `w780` | `original` |

**实现方式：** 统一通过 `App\Helpers\ImageHelper::url(?string $path, string $size): ?string` 处理，路径为 null 时返回 null。

```php
// ImageHelper 使用示例
ImageHelper::url($this->poster_path, 'w342')
// 输出：https://image.tmdb.org/t/p/w342/abc123.jpg
// path 为 null 时输出：null
```

---

## TMDB ID 与本地 ID 的关系

- 每个实体都有 `tmdb_id`（TMDB 原始 ID）和 `id`（本地自增主键）
- API 对外统一使用本地 `id` 作为资源标识符（路由参数 `{id}`）
- `tmdb_id` 作为查询参数支持（如 `?tmdb_id=12345`），但路由主键用本地 `id`
- 前端不应依赖 `tmdb_id` 做路由跳转，统一用本地 `id`

---

## 大表查询约束

以下表数据量极大，查询时必须遵守约束：

| 表 | 预计数据量 | 约束 |
|----|-----------|------|
| `persons` | 500 万+ | 必须有筛选条件或限制 per_page ≤ 50，禁止全表扫描 |
| `movies` | 100 万+ | page 最大 1000，必须走索引字段筛选 |
| `tv_seasons` | 100 万+ | 必须带 `tv_show_id` 条件 |
| `tv_episodes` | 2000 万+ | 必须带 `tv_season_id` 或 `tv_show_id` 条件，禁止无条件分页 |
| `tv_episode_credits` | 极大 | 必须带 `tv_episode_id` 条件 |

**Repository 层必须在方法签名或注释中标注大表约束，防止 AI 生成无条件查询。**

---

## 媒体列表快照数据流

`media_list_snapshots` 表存储 TMDB 各类榜单的每日快照，由采集项目写入。

- `local_id` 字段初始为 NULL，entity refresh 后填充为本地实体 id
- API 查询榜单时，优先用 `local_id` 关联实体；`local_id` 为 NULL 时，用 `tmdb_id` 查询
- 榜单接口按 `(list_type, snapshot_date, rank)` 索引查询，不做全表扫描

支持的 `list_type`：
`movie_trending_day` / `movie_trending_week` / `movie_now_playing` / `movie_upcoming` /
`tv_trending_day` / `tv_trending_week` / `tv_airing_today` / `tv_on_the_air` /
`person_trending_day` / `person_trending_week`
