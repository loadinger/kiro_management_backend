# 需求文档：Media List Snapshots

## 简介

Media List Snapshots 模块为 Filmly Management Backend 提供 TMDB 榜单快照数据的只读 API 接口。

`media_list_snapshots` 表由独立采集项目写入，存储 TMDB 各类榜单（电影趋势、电视剧趋势、正在上映等）的每日快照数据。本模块在此基础上提供 7 类榜单接口，按 `(list_type, snapshot_date, rank)` 索引查询，支持指定日期或默认取最新快照，并通过 `local_id` / `tmdb_id` 关联本地实体数据。

---

## 词汇表

- **MediaListSnapshot**：`media_list_snapshots` 表的 Eloquent 模型，代表某榜单某日期某条目的快照记录
- **ListType**：榜单类型枚举，取值见下方枚举定义
- **SnapshotDate**：快照日期（UTC），格式 `Y-m-d`，由采集项目按天写入
- **LatestSnapshot**：指定 `list_type` 下 `snapshot_date` 最大的那一天的快照数据
- **local_id**：快照记录关联的本地实体主键（`movies.id` / `tv_shows.id` / `persons.id`），由采集项目的 entity refresh 步骤填充，初始为 NULL
- **tmdb_id**：TMDB 原始 ID，`local_id` 为 NULL 时用于降级关联查询
- **MediaListSnapshotService**：负责榜单快照业务逻辑的 Service 类
- **MediaListSnapshotRepository**：负责 `media_list_snapshots` 表查询的 Repository 类
- **MovieSnapshotResource**：电影类榜单条目的 API Resource
- **TvShowSnapshotResource**：电视剧类榜单条目的 API Resource
- **PersonSnapshotResource**：人物类榜单条目的 API Resource

### ListType 枚举值

| 枚举值 | 说明 | 关联实体 |
|--------|------|---------|
| `movie_trending_day` | 电影日趋势榜 | movies |
| `movie_trending_week` | 电影周趋势榜 | movies |
| `movie_now_playing` | 正在热映 | movies |
| `movie_upcoming` | 即将上映 | movies |
| `tv_trending_day` | 电视剧日趋势榜 | tv_shows |
| `tv_trending_week` | 电视剧周趋势榜 | tv_shows |
| `tv_airing_today` | 今日播出 | tv_shows |
| `tv_on_the_air` | 即将播出 | tv_shows |
| `person_trending_day` | 人物日趋势榜 | persons |
| `person_trending_week` | 人物周趋势榜 | persons |

---

## 需求

### 需求 1：电影榜单接口

**用户故事：** 作为管理后台用户，我希望查询各类电影榜单的快照数据，以便了解当前热映、即将上映及趋势电影。

#### 验收标准

1. THE **API** SHALL 提供以下 4 个只读接口：
   - `GET /api/media-lists/movie-now-playing`
   - `GET /api/media-lists/movie-upcoming`
   - `GET /api/media-lists/movie-trending-day`
   - `GET /api/media-lists/movie-trending-week`

2. WHEN 请求不携带 `snapshot_date` 参数时，THE **MediaListSnapshotRepository** SHALL 查询该 `list_type` 下 `snapshot_date` 最大的日期作为目标日期。

3. WHEN 请求携带合法的 `snapshot_date` 参数（格式 `Y-m-d`）时，THE **MediaListSnapshotRepository** SHALL 使用该日期作为目标日期查询快照数据。

4. IF `snapshot_date` 参数格式不符合 `Y-m-d`，THEN THE **API** SHALL 返回 `code: 422` 及中文错误信息。

5. WHEN 查询目标日期的快照数据时，THE **MediaListSnapshotRepository** SHALL 按 `(list_type, snapshot_date, rank)` 索引查询，结果按 `rank` 升序排列。

6. WHEN 快照条目的 `local_id` 不为 NULL 时，THE **MediaListSnapshotService** SHALL 通过 `local_id` 关联 `movies` 表获取电影实体数据。

7. WHEN 快照条目的 `local_id` 为 NULL 时，THE **MediaListSnapshotService** SHALL 通过 `tmdb_id` 查询 `movies` 表获取电影实体数据；IF `tmdb_id` 在 `movies` 表中也不存在，THEN THE **MovieSnapshotResource** SHALL 将电影实体字段输出为 `null`，不报错。

8. THE **MovieSnapshotResource** SHALL 在响应中输出以下字段：
   - 快照字段：`rank`、`popularity`（decimal 字符串）、`snapshot_date`（`Y-m-d` 格式）、`tmdb_id`、`local_id`（可为 null）
   - 电影实体字段（实体不存在时为 null）：`id`、`title`、`original_title`、`release_date`（`Y-m-d` 格式）、`poster_path`、`vote_average`、`status`

9. THE **API** SHALL 对电影榜单接口的响应不做分页，直接返回目标日期的全部条目（通常 ≤ 100 条）。

10. WHEN 目标日期不存在任何快照数据时，THE **API** SHALL 返回 `code: 0`，`data.list` 为空数组，`data.snapshot_date` 为 null。

11. THE **API** SHALL 在响应的 `data` 节点中包含 `snapshot_date` 字段，标明本次返回数据所属的快照日期。

---

### 需求 2：电视剧榜单接口

**用户故事：** 作为管理后台用户，我希望查询各类电视剧榜单的快照数据，以便了解今日播出、即将播出及趋势剧集。

#### 验收标准

1. THE **API** SHALL 提供以下 4 个只读接口：
   - `GET /api/media-lists/tv-airing-today`
   - `GET /api/media-lists/tv-on-the-air`
   - `GET /api/media-lists/tv-trending-day`
   - `GET /api/media-lists/tv-trending-week`

2. WHEN 请求不携带 `snapshot_date` 参数时，THE **MediaListSnapshotRepository** SHALL 查询该 `list_type` 下 `snapshot_date` 最大的日期作为目标日期。

3. WHEN 请求携带合法的 `snapshot_date` 参数（格式 `Y-m-d`）时，THE **MediaListSnapshotRepository** SHALL 使用该日期作为目标日期查询快照数据。

4. IF `snapshot_date` 参数格式不符合 `Y-m-d`，THEN THE **API** SHALL 返回 `code: 422` 及中文错误信息。

5. WHEN 查询目标日期的快照数据时，THE **MediaListSnapshotRepository** SHALL 按 `(list_type, snapshot_date, rank)` 索引查询，结果按 `rank` 升序排列。

6. WHEN 快照条目的 `local_id` 不为 NULL 时，THE **MediaListSnapshotService** SHALL 通过 `local_id` 关联 `tv_shows` 表获取电视剧实体数据。

7. WHEN 快照条目的 `local_id` 为 NULL 时，THE **MediaListSnapshotService** SHALL 通过 `tmdb_id` 查询 `tv_shows` 表获取电视剧实体数据；IF `tmdb_id` 在 `tv_shows` 表中也不存在，THEN THE **TvShowSnapshotResource** SHALL 将电视剧实体字段输出为 `null`，不报错。

8. THE **TvShowSnapshotResource** SHALL 在响应中输出以下字段：
   - 快照字段：`rank`、`popularity`（decimal 字符串）、`snapshot_date`（`Y-m-d` 格式）、`tmdb_id`、`local_id`（可为 null）
   - 电视剧实体字段（实体不存在时为 null）：`id`、`name`、`original_name`、`first_air_date`（`Y-m-d` 格式）、`poster_path`、`vote_average`、`status`

9. THE **API** SHALL 对电视剧榜单接口的响应不做分页，直接返回目标日期的全部条目（通常 ≤ 100 条）。

10. WHEN 目标日期不存在任何快照数据时，THE **API** SHALL 返回 `code: 0`，`data.list` 为空数组，`data.snapshot_date` 为 null。

11. THE **API** SHALL 在响应的 `data` 节点中包含 `snapshot_date` 字段，标明本次返回数据所属的快照日期。

---

### 需求 3：人物榜单接口

**用户故事：** 作为管理后台用户，我希望查询热门人物榜单的快照数据，以便了解当前日/周趋势人物。

#### 验收标准

1. THE **API** SHALL 提供以下 2 个只读接口：
   - `GET /api/media-lists/person-trending-day`
   - `GET /api/media-lists/person-trending-week`

2. WHEN 请求不携带 `snapshot_date` 参数时，THE **MediaListSnapshotRepository** SHALL 查询该 `list_type` 下 `snapshot_date` 最大的日期作为目标日期。

3. WHEN 请求携带合法的 `snapshot_date` 参数（格式 `Y-m-d`）时，THE **MediaListSnapshotRepository** SHALL 使用该日期作为目标日期查询快照数据。

4. IF `snapshot_date` 参数格式不符合 `Y-m-d`，THEN THE **API** SHALL 返回 `code: 422` 及中文错误信息。

5. WHEN 查询目标日期的快照数据时，THE **MediaListSnapshotRepository** SHALL 按 `(list_type, snapshot_date, rank)` 索引查询，结果按 `rank` 升序排列。

6. WHEN 快照条目的 `local_id` 不为 NULL 时，THE **MediaListSnapshotService** SHALL 通过 `local_id` 关联 `persons` 表获取人物实体数据。

7. WHEN 快照条目的 `local_id` 为 NULL 时，THE **MediaListSnapshotService** SHALL 通过 `tmdb_id` 查询 `persons` 表获取人物实体数据；IF `tmdb_id` 在 `persons` 表中也不存在，THEN THE **PersonSnapshotResource** SHALL 将人物实体字段输出为 `null`，不报错。

8. THE **PersonSnapshotResource** SHALL 在响应中输出以下字段：
   - 快照字段：`rank`、`popularity`（decimal 字符串）、`snapshot_date`（`Y-m-d` 格式）、`tmdb_id`、`local_id`（可为 null）
   - 人物实体字段（实体不存在时为 null）：`id`、`name`、`known_for_department`（字符串，如 `"Acting"`）、`profile_path`、`gender`

9. THE **API** SHALL 对人物榜单接口的响应不做分页，直接返回目标日期的全部条目（通常 ≤ 100 条）。

10. WHEN 目标日期不存在任何快照数据时，THE **API** SHALL 返回 `code: 0`，`data.list` 为空数组，`data.snapshot_date` 为 null。

11. THE **API** SHALL 在响应的 `data` 节点中包含 `snapshot_date` 字段，标明本次返回数据所属的快照日期。

---

### 需求 4：认证与安全

**用户故事：** 作为系统管理员，我希望榜单接口受到认证保护，以防止未授权访问。

#### 验收标准

1. THE **API** SHALL 对所有 10 个榜单接口应用 `auth:api` middleware。

2. WHEN 请求未携带有效 JWT Token 时，THE **API** SHALL 返回 `code: 401` 及中文错误信息。

3. THE **API** SHALL 对所有榜单接口仅支持 `GET` 方法，禁止写操作。

---

### 需求 5：响应格式规范

**用户故事：** 作为前端开发者，我希望榜单接口的响应格式统一且可预期，以便前端统一处理。

#### 验收标准

1. THE **API** SHALL 对所有榜单接口统一返回 HTTP 200，业务状态通过信封格式的 `code` 字段区分。

2. THE **API** SHALL 对所有榜单接口的成功响应采用以下结构：
   ```json
   {
     "code": 0,
     "message": "success",
     "data": {
       "list": [...],
       "snapshot_date": "2025-01-15"
     }
   }
   ```

3. THE **MovieSnapshotResource** SHALL 输出 `poster_path` 字段（数据库原始相对路径），WHEN `poster_path` 为 null 时输出 null。

4. THE **TvShowSnapshotResource** SHALL 输出 `poster_path` 字段（数据库原始相对路径），WHEN `poster_path` 为 null 时输出 null。

5. THE **PersonSnapshotResource** SHALL 输出 `profile_path` 字段（数据库原始相对路径），WHEN `profile_path` 为 null 时输出 null。

6. THE **API** SHALL 将 `snapshot_date`、`release_date`、`first_air_date` 等日期字段统一以 `Y-m-d` 格式字符串输出。
