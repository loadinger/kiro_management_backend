# Filmly Management Backend — 开发进度

## 项目状态：开发中

---

## Spec 列表

状态说明：`待开始` / `进行中` / `已完成` / `已暂停`

### System
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| S-01 | Auth：login / logout / refresh / me | 已完成 | login / logout / refresh / me 全部实现，10 个测试通过 |
| S-02 | Global Search：`GET /api/search?q=` | 待开始 | 跨表搜索 movies / tv_shows / persons，每表 ≤ 10 条 |
| S-03 | LLM 翻译：`php artisan translate:names` | 已完成 | Artisan Command + TranslationService + LlmTranslationService 全部实现，migration 已添加 name_zh / translated_at 字段，暂无自动化测试 |
| S-04 | Dashboard 数据统计：`GET /api/dashboard/stats` + `GET /api/dashboard/trends` | 已完成 | 各实体总量、近期新增趋势折线图、异步关联完成率、翻译覆盖率、数据新鲜度、每日采集健康度 |

### Movies
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| M-01 | Movie List：列表，支持排序、过滤、搜索 | 已完成 | 支持 q/genre_id/status/release_year/adult 筛选，默认 id 降序，page 最大 1000 |
| M-02 | Movie Detail：详情 | 已完成 | 全字段输出，不存在返回 404 |
| M-03 | Movie Credits：list | 已完成 | 支持 credit_type 筛选，person_id 异步关联 null 安全处理 |
| M-04 | Movie Images：list | 已完成 | 支持 image_type 筛选，backdrop/poster/logo 不同 size |
| M-05 | Movie Genres：list | 已完成 | 不分页，movie_id 必填 |
| M-06 | Movie Keywords：list | 已完成 | 不分页，movie_id 必填 |
| M-07 | Movie Production Companies：list | 已完成 | 不分页，movie_id 必填 |

### Collections
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| C-01 | Collection List：列表，支持搜索 | 已完成 | 支持 q 参数搜索，分页 |
| C-02 | Collection Detail：详情（含关联电影列表） | 已完成 | collection_movies 异步关联，movie_id 可为 null，resolved 字段标识关联状态 |

### TV Shows
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| T-01 | TV Show List：列表，支持排序、过滤、搜索 | 已完成 | |
| T-02 | TV Show Detail：详情 | 已完成 | |
| T-03 | TV Show Creators：list | 已完成 | tv_show_id 必填，person_id 异步关联 null 安全处理 |
| T-04 | TV Show Genres：list | 已完成 | tv_show_id 必填，不分页 |
| T-05 | TV Show Images：list | 已完成 | tv_show_id 必填，支持 image_type 筛选 |
| T-06 | TV Show Keywords：list | 已完成 | tv_show_id 必填，不分页 |
| T-07 | TV Show Networks：list | 已完成 | tv_show_id 必填，不分页 |
| T-08 | TV Show Production Companies：list | 已完成 | tv_show_id 必填，不分页 |

### TV Seasons
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| TS-01 | TV Season List：列表，支持排序、搜索 | 已完成 | tv_show_id 必填 |
| TS-02 | TV Season Detail：详情 | 已完成 | |
| TS-03 | TV Season Images：list | 已完成 | tv_season_id 必填 |

### TV Episodes
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| TE-01 | TV Episode List：列表，支持排序、搜索 | 已完成 | tv_season_id 必填 |
| TE-02 | TV Episode Detail：详情 | 已完成 | |
| TE-03 | TV Episode Credits：list | 已完成 | tv_episode_id 必填，person_id 异步关联 null 安全处理 |
| TE-04 | TV Episode Images：list | 已完成 | tv_episode_id 必填 |

### Persons
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| P-01 | Person List：列表，支持排序、过滤、搜索 | 已完成 | 大表，per_page ≤ 50，支持 gender/adult/known_for_department/q 筛选 |
| P-02 | Person Detail：详情 | 已完成 | 全字段输出，不存在返回 404 |
| P-03 | Person Movies：人物参演电影列表 | 已完成 | person_id 必填，平铺输出 credit + movie 字段 |
| P-04 | Person TV Shows：人物参演电视剧列表 | 已完成 | person_id 必填，以 tv_show 为单位去重，JOIN + DISTINCT |

### Media List Snapshots
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| ML-01 | Movie Now Playing：正在热映 | 已完成 | list_type=movie_now_playing |
| ML-02 | Movie Upcoming：即将上映 | 已完成 | list_type=movie_upcoming |
| ML-03 | Movie Trending：热门电影（日/周） | 已完成 | list_type=movie_trending_day / movie_trending_week |
| ML-04 | TV Airing Today：今日播出 | 已完成 | list_type=tv_airing_today |
| ML-05 | TV On The Air：即将播出 | 已完成 | list_type=tv_on_the_air |
| ML-06 | TV Trending：热门剧集（日/周） | 已完成 | list_type=tv_trending_day / tv_trending_week |
| ML-07 | Person Trending：热门人物（日/周） | 已完成 | list_type=person_trending_day / person_trending_week |

### Articles（专题）
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| A-01 | Article List：列表，支持 status 筛选、排序 | 已完成 | |
| A-02 | Article Detail：详情（含 items 分组数组） | 已完成 | |
| A-03 | Article Store：创建 | 已完成 | |
| A-04 | Article Update：更新 | 已完成 | |
| A-05 | Article Delete：删除 | 已完成 | |
| A-06 | Article Items：按实体反向查询关联专题 | 已完成 | |

### 基础参考数据
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| R-01 | Reference Data：countries / departments / genres / jobs / keywords / languages 列表+搜索 | 已完成 | 同一 Spec 实现 |
| R-02 | Production Companies：列表+搜索+详情 | 已完成 | 同一 Spec 实现 |
| R-03 | TV Networks：列表+搜索+详情 | 已完成 | 同一 Spec 实现 |

> R-01 / R-02 / R-03 合并在同一个 Spec：`.kiro/specs/reference-data/`

---

## 开发日志

### 2026-03-26 — Auth 模块完成

**完成内容：**
- 实现 login / logout / refresh / me 四个接口
- LoginRequest 参数验证（email + password，含中文错误信息）
- UserResource 明确排除 password / remember_token 敏感字段
- refresh 路由不走 auth:api middleware，允许携带过期 token 换新 token
- login 接口加 throttle:10,1 限流防暴力破解
- 10 个 Feature Test 全部通过，覆盖正常流程、错误凭证、缺失 token、token 黑名单验证

**已注册路由：**
- `POST /api/auth/login`（无需认证，限流 10次/分钟/IP）
- `POST /api/auth/refresh`（无需认证）
- `POST /api/auth/logout`（需认证）
- `GET /api/auth/me`（需认证）

---

### 2026-03-28 — reference-data Spec 完成

**完成内容：**
- 实现 8 类只读参考数据 API（countries / departments / genres / jobs / keywords / languages / production_companies / tv_networks）
- 标准分层架构：Model → Repository → Service → FormRequest → Resource → Controller → Route
- 全部路由注册在 `auth:api` middleware 组内，仅 GET 方法
- 富参考数据（production_companies / tv_networks）支持列表+详情，logo_url 通过 ImageHelper 拼接
- 41 个测试全部通过，140 个断言

**已注册路由：**
- `GET /api/countries`
- `GET /api/departments`
- `GET /api/genres`
- `GET /api/jobs`
- `GET /api/keywords`
- `GET /api/languages`
- `GET /api/production-companies`
- `GET /api/production-companies/{id}`
- `GET /api/tv-networks`
- `GET /api/tv-networks/{id}`

---

### 2026-03-26 — 项目初始化

**完成内容：**
- Laravel 11 项目初始化（PHP 8.3 + FPM）
- 安装 tymon/jwt-auth 2.x
- 搭建项目骨架：BaseController、BaseRepository、AppException、全局异常处理
- 配置 JWT guard（auth:api）
- 实现 AuthController（login / logout / refresh / me）
- 配置 CORS、路由结构

**Steering 文档（AI 开发上下文）：**
- `project-overview.md` — 项目定位、技术栈、目录结构
- `database-schema.md` — 完整数据库表结构
- `api-conventions.md` — 响应格式、分页、路由、筛选排序规范
- `coding-standards.md` — 分层规范、依赖注入、异常、日志、注释
- `data-flow.md` — 读写边界、异步关联、图片 URL、大表约束
- `development-workflow.md` — 环境启动、新增模块标准流程
- `testing-strategy.md` — 测试分层、mock 策略、覆盖要求
- `security.md` — 认证、输入安全、CORS、敏感信息保护

---

### 2026-04-04 — LLM 翻译模块完成

**完成内容：**
- `TranslateNamesCommand`：Artisan Command，支持 `--table`、`--batch-size`、`--limit` 参数，带进度条和统计输出
- `TranslationService`：按表分发，cursor-based 分页（`WHERE id > $afterId`）支持断点续传，jobs 表携带 department.name 上下文，空 source 字段自动标记跳过
- `LlmTranslationService`：封装 Ollama `/api/chat` 调用，三级重试（全批 → 5条子批 → 单条），多层 JSON 容错（bracket-depth 扫描 + 格式兼容 `text` 字段回退）
- Migration：为 departments / jobs / keywords / languages 添加 `name_zh`（nullable varchar）和 `translated_at`（nullable timestamp），带 `hasTable()` 守卫兼容测试环境
- `config/services.php` 注册 Ollama 配置（`base_url` / `model` / `timeout`）

**用法：**
```bash
php artisan translate:names --table=keywords --batch-size=20
php artisan translate:names --table=all
php artisan translate:names --table=keywords --limit=100
```

**待补充：**
- Unit Test（TranslationService / LlmTranslationService mock 测试）

---

### 2026-04-04 — dashboard Spec 完成

**完成内容：**
- 实现 Dashboard 数据统计模块，提供两个只读聚合接口
- 标准分层架构：DashboardRepositoryInterface → DashboardRepository → DashboardService → GetTrendsRequest → DashboardController → Route
- Redis 缓存：stats TTL 10 分钟，trends TTL 5 分钟，缓存键含参数签名
- 子项容错：每个统计子项独立 try-catch，失败时返回 null 不影响其他子项
- 29 个测试全部通过，15,517 个断言

**已注册路由：**
- `GET /api/dashboard/stats`
- `GET /api/dashboard/trends?days=30&entities=movies,tv_shows,persons`

---

### 2026-04-10 — TV Shows / TV Seasons / TV Episodes Spec 完成

**完成内容：**
- 实现 TV Show API 模块：列表（支持 q/genre_id/status/adult 筛选，默认 id 降序，page 最大 1000）、详情、及 6 个子资源接口（creators / genres / images / keywords / networks / production-companies）
- 实现 TV Season API 模块：列表（tv_show_id 必填）、详情、图片列表
- 实现 TV Episode API 模块：列表（tv_season_id 必填，大表约束）、详情、演职人员列表、图片列表
- 标准分层架构：Model → Repository → Service → FormRequest → Resource → Controller → Route
- 异步关联 null 安全处理：tv_show_creators / tv_episode_credits 的 person_id 为 null 时不报错
- 全部路由注册在 `auth:api` middleware 组内

**已注册路由：**
- `GET /api/tv-shows`
- `GET /api/tv-shows/{id}`
- `GET /api/tv-show-creators?tv_show_id=`
- `GET /api/tv-show-genres?tv_show_id=`
- `GET /api/tv-show-images?tv_show_id=`
- `GET /api/tv-show-keywords?tv_show_id=`
- `GET /api/tv-show-networks?tv_show_id=`
- `GET /api/tv-show-production-companies?tv_show_id=`
- `GET /api/tv-seasons?tv_show_id=`
- `GET /api/tv-seasons/{id}`
- `GET /api/tv-season-images?tv_season_id=`
- `GET /api/tv-episodes?tv_season_id=`
- `GET /api/tv-episodes/{id}`
- `GET /api/tv-episode-credits?tv_episode_id=`
- `GET /api/tv-episode-images?tv_episode_id=`

---

### 2026-04-10 — movie Spec 完成

**完成内容：**
- 实现 Movie API 模块，提供 7 个只读接口
- 标准分层架构：Movie / MovieCredit / MovieImage / Person Model → Repository → Service → FormRequest → Resource → Controller → Route
- 电影列表支持 `q`（标题前缀搜索）、`genre_id`、`status`、`release_year`、`adult` 筛选，默认 `id` 降序，`page` 最大 1000
- 电影详情不存在时抛出 `AppException` 返回 `code: 404`
- 演职人员列表支持 `credit_type` 筛选，`person_id` 异步关联 null 安全处理（`person` 字段为 null 不报错）
- 图片列表支持 `image_type` 筛选，backdrop 使用 `w780`，poster/logo 使用 `w342`
- 类型/关键词/制作公司列表不分页，`movie_id` 必填
- `CreditType` enum 定义（cast / crew）
- 全部路由注册在 `auth:api` middleware 组内

**已注册路由：**
- `GET /api/movies`
- `GET /api/movies/{id}`
- `GET /api/movie-credits?movie_id=`
- `GET /api/movie-images?movie_id=`
- `GET /api/movie-genres?movie_id=`
- `GET /api/movie-keywords?movie_id=`
- `GET /api/movie-production-companies?movie_id=`

---

### 2026-04-10 — collections Spec 完成

**完成内容：**
- 实现 Collections（合集）API 模块，提供两个只读接口
- 标准分层架构：Collection / CollectionMovie Model → CollectionRepositoryInterface → CollectionRepository → CollectionService → ListCollectionRequest → CollectionListResource / CollectionResource / CollectionMovieResource → CollectionController → Route
- 列表接口支持 `q` 参数模糊搜索（`LIKE %q%`），分页参数 page（最大 1000）/ per_page（最大 100）
- 详情接口预加载 `collection_movies` 关联，`movie_id` 为 null 时 `resolved=false`，不报错
- 全部路由注册在 `auth:api` middleware 组内

**已注册路由：**
- `GET /api/collections`
- `GET /api/collections/{id}`

---

### 2026-04-11 — media-list-snapshots Spec 完成

**完成内容：**
- 实现 Media List Snapshots 模块，提供 10 个只读榜单快照接口
- 标准分层架构：MediaListSnapshot Model → MediaListSnapshotRepositoryInterface → MediaListSnapshotRepository → MediaListSnapshotService → GetMediaListRequest → MovieSnapshotResource / TvShowSnapshotResource / PersonSnapshotResource → MediaListSnapshotController → Route
- `ListType` enum 定义 10 个榜单类型，含 `isMovie()` / `isTvShow()` / `isPerson()` 辅助方法
- Service 层批量关联实体（`local_id` 优先，降级 `tmdb_id`），避免 N+1，实体不存在时字段输出 null 不报错
- 支持 `snapshot_date` 参数（`Y-m-d` 格式），不传时自动取最新快照日期
- 全部路由注册在 `auth:api` middleware 组内，227 个测试全部通过

**已注册路由：**
- `GET /api/media-lists/movie-now-playing`
- `GET /api/media-lists/movie-upcoming`
- `GET /api/media-lists/movie-trending-day`
- `GET /api/media-lists/movie-trending-week`
- `GET /api/media-lists/tv-airing-today`
- `GET /api/media-lists/tv-on-the-air`
- `GET /api/media-lists/tv-trending-day`
- `GET /api/media-lists/tv-trending-week`
- `GET /api/media-lists/person-trending-day`
- `GET /api/media-lists/person-trending-week`

**可选查询参数：** `snapshot_date`（`Y-m-d`，不传取最新）

---

### 2026-04-12 — articles Spec 完成

**完成内容：**
- 实现 Articles（专题）模块，提供 CRUD + 反向查询共 6 个接口
- 标准分层架构：Article / ArticleItem Model → Repository → Service → FormRequest → Resource → Controller → Route
- Media 占位符解析：`::media{type="movie" id="123"}` 格式，支持 type/id 顺序互换，非法 type/id 静默忽略，结果去重
- 保存时在事务中全量同步 `article_items`（先删后插），保证幂等性
- 详情接口返回 `items` 分组数组（`{ movies: [], tv_shows: [], ... }`），按 entity_type 分组批量查询，防 N+1，所有类型始终存在（无引用时为空数组）
- slug 可选（nullable），status=published 时 slug 不能为 null
- 反向查询接口 `GET /api/article-items?entity_type=movie&entity_id=123`，预加载关联专题
- 两张可写业务表 migration：articles + article_items
- 全部路由注册在 `auth:api` middleware 组内

**已注册路由：**
- `GET /api/articles`
- `POST /api/articles`
- `GET /api/articles/{id}`
- `PUT /api/articles/{id}`
- `DELETE /api/articles/{id}`
- `GET /api/article-items?entity_type=&entity_id=`

---

### 2026-04-12 — person Spec 完成

**完成内容：**
- 实现 Person API 模块，提供 4 个只读接口
- 标准分层架构：Person / MovieCredit / TvEpisodeCredit Model → Repository → Service → FormRequest → Resource → Controller → Route
- 人物列表支持 `gender`（0/1/2/3）、`adult`（0/1）、`known_for_department`、`q`（name 前缀搜索）筛选，默认 `id` 降序，`per_page` 最大 50（大表约束），`page` 最大 1000
- 人物详情不存在时抛出 `AppException` 返回 `code: 404`，`birthday`/`deathday` 输出 `Y-m-d` 格式
- 人物参演电影：以 `movie_credits` 为单位，平铺输出 credit 字段（`credit_type`、`character`、`cast_order`、`department_id`、`job_id`）+ movie 字段（`tmdb_id`、`title`、`original_title`、`release_date`、`poster_path`），`with('movie')` 预加载避免 N+1
- 人物参演电视剧：以 `tv_show` 为单位去重，通过 JOIN `tv_episodes` + `tv_episode_credits` + `DISTINCT` 实现，person 不存在时返回 404
- `PersonMovieService` / `PersonTvShowService` 均注入 `PersonRepositoryInterface` 做 person 存在性校验
- 全部路由注册在 `auth:api` middleware 组内，228 个测试全部通过

**已注册路由：**
- `GET /api/persons`
- `GET /api/persons/{id}`
- `GET /api/person-movies?person_id=`
- `GET /api/person-tv-shows?person_id=`

---

### ADR-001 — 技术栈选型（2026-03-26）

**决策：** Laravel 11 + PHP-FPM，不使用 Swoole/Hyperf

**背景：** 项目为管理后台，并发低，主要是 DB 查询 + 序列化输出。

**选项对比：**
- Hyperf + Swoole：性能最高，但开发效率低、运维复杂，优势在此场景边际收益极低
- Laravel + Octane：性能够用，但管理后台不需要
- Laravel + FPM：开发效率最高，调试最简单，完全满足需求

**结论：** Laravel 11 + FPM，性能瓶颈在 MySQL 查询和缓存，不在框架层。

---

### ADR-002 — API 响应格式（2026-03-26）

**决策：** 信封格式，HTTP 状态码统一 200，业务状态用 `code` 字段区分

**背景：** 管理后台前端使用 TanStack Query，两种方案都可行。

**结论：** 选信封格式，符合团队习惯，前端统一拦截 `code` 处理错误。

---

### ADR-003 — 分页策略（2026-03-26）

**决策：** Offset 分页，带 total，大表限制最大 page=1000

**背景：** 管理后台需要显示总数和跳页，cursor 分页体验不符合需求。

**结论：** Offset 分页 + 深翻页限制，实际使用中管理员不会翻到极深页，用搜索/筛选缩小范围。

---

### ADR-004 — 数据库 Migration 策略（2026-03-26）

**决策：** 本项目不维护核心业务表的 migration

**背景：** movies / tv_shows 等表由独立采集项目创建维护，本项目只读。

**结论：** 本项目只维护 users 表和未来新增的纯管理业务表的 migration，禁止对采集项目的表执行 migrate:fresh。

---

### ADR-005 — 子资源路由方式（2026-03-26）

**决策：** 独立路由 + 参数过滤，不使用嵌套路由

**示例：**
```
GET /api/movie-credits?movie_id=123
GET /api/movie-images?movie_id=123
GET /api/tv-seasons?tv_show_id=456
GET /api/tv-episodes?tv_season_id=789
```

**原因：** 更灵活，前端可以在不同上下文复用同一接口，不受父资源路由层级限制。

---

### ADR-006 — 基础参考数据接口（2026-03-26）

**决策：** 所有列表接口统一走分页，包括 countries / languages 等小表

**原因：** 保持接口响应结构一致，前端统一处理逻辑。

---

### ADR-007 — 搜索方案（2026-03-26）

**决策：** 两层搜索策略，不引入独立搜索引擎

**列表页搜索：** 各模块列表接口的 `search` 参数，走 `LIKE keyword%`（前缀匹配，能利用索引），针对主要文本字段（title、name 等）。

**全局搜索：** 独立接口 `GET /api/search?q=keyword`，并发查询 movies / tv_shows / persons 三张主表，每表最多返回 10 条，合并返回，不分页。

```json
{
  "code": 0,
  "data": {
    "movies":   [...],
    "tv_shows": [...],
    "persons":  [...]
  }
}
```

**原因：** 管理后台搜索频率低，用户通常知道目标类型。方案实现简单，性能可控。后续如有复杂搜索需求再引入 MeiliSearch。

---

### ADR-008 — LLM 翻译方案（2026-04-02）

**决策：** 使用本地 Ollama + Qwen 2.5 7B 对参考数据表的英文 name 字段做中文翻译

**背景：** departments / jobs / keywords / languages 的 name 字段均为英文，前端展示需要中文。

**关键决策点：**
- 新增 `name_zh` + `translated_at` 字段，不修改原始 `name`，API 层降级兜底
- 使用 Ollama `format: "json"` 参数强制 JSON 输出，配合多层容错重试（最多 3 次，批量递减）
- 批量翻译用 `id` 字段做映射，不依赖顺序，防止漏翻导致错位
- `jobs` 翻译时携带 `department.name` 作为上下文
- `keywords` 数据量大，支持断点续传（`WHERE name_zh IS NULL`）
- Prompt 明确约束译文长度：keywords/departments/languages ≤ 8 字，jobs ≤ 12 字，并提供正反例

**结论：** Artisan Command 驱动，TranslationService + LlmTranslationService 分层，不影响现有 API 逻辑。

---

## 待决策事项

- [ ] 生产环境部署方案（服务器/容器化）
- [ ] Redis 缓存策略（哪些接口需要缓存、TTL 设置）
- [ ] 是否需要操作日志（记录管理员的写操作）
- [ ] Global Search：`GET /api/search?q=` 待实现
