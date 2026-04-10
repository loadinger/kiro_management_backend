# Filmly 数据库结构文档

> 基于 migrations/ 目录自动整理，最后更新：2026-03-26

---

## 实体依赖层级

```
第 0 层（无依赖，基础参考数据）
  genres / countries / languages / departments / jobs
  persons / keywords / tv_networks / production_companies

第 1 层（依赖第 0 层）
  movies      → genres, keywords, production_companies, persons
  tv_shows    → genres, keywords, production_companies, tv_networks, persons

第 2 层（依赖第 1 层）
  collections → movies
  tv_seasons  → tv_shows

第 3 层（依赖第 2 层）
  tv_episodes → tv_seasons
```

---

## 第 0 层：基础参考数据

### genres（类型）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | 自增主键 |
| tmdb_id | uint UNIQUE | TMDB ID |
| name | varchar(255) | 类型名称 |
| type | varchar(10) | `movie` 或 `tv`，默认 `movie` |
| created_at / updated_at | timestamp | |

### countries（国家/地区）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| iso_3166_1 | varchar(10) UNIQUE | ISO 3166-1 国家代码 |
| english_name | varchar(255) | 英文名 |
| native_name | varchar(255) | 本地名 |
| created_at / updated_at | timestamp | |

### languages（语言）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| iso_639_1 | varchar(10) UNIQUE | ISO 639-1 语言代码 |
| english_name | varchar(255) | 英文名 |
| name | varchar(255) | 本地名 |
| created_at / updated_at | timestamp | |

### departments（部门）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| name | varchar(255) UNIQUE | 部门名称 |
| created_at / updated_at | timestamp | |

### jobs（职位）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| name | varchar(255) | 职位名称 |
| department_id | bigint | → departments.id |
| created_at / updated_at | timestamp | |

唯一约束：`(name, department_id)`

### persons（人物）预计 500 万+ 条
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| tmdb_id | uint UNIQUE | TMDB ID |
| name | varchar(255) | 姓名 |
| gender | tinyint | 0=未知, 1=女, 2=男, 3=非二元 |
| adult | boolean | |
| biography | text | 可为空 |
| birthday | date | 可为空 |
| deathday | date | 可为空 |
| place_of_birth | varchar(255) | 可为空 |
| profile_path | varchar(255) INDEX | 可为空 |
| popularity | double INDEX | |
| known_for_department_id | uint INDEX | → departments.id，可为空 |
| homepage | varchar(500) | 可为空 |
| imdb_id | varchar(20) | 可为空 |
| also_known_as | json | 别名列表，可为空 |
| created_at / updated_at | timestamp | |

### keywords（关键词）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| tmdb_id | uint UNIQUE | |
| name | varchar(255) | |
| created_at / updated_at | timestamp | |

### tv_networks（电视网络）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| tmdb_id | uint UNIQUE | |
| name | varchar(255) | |
| headquarters | varchar(255) | 可为空 |
| homepage | varchar(500) | 可为空 |
| logo_path | varchar(255) INDEX | 可为空 |
| origin_country | varchar(10) | 可为空 |
| created_at / updated_at | timestamp | |

### production_companies（制作公司）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| tmdb_id | uint UNIQUE | |
| name | varchar(255) | |
| description | text | 可为空 |
| headquarters | varchar(255) | 可为空 |
| homepage | varchar(500) | 可为空 |
| logo_path | varchar(255) INDEX | 可为空 |
| origin_country | varchar(10) | 可为空 |
| parent_company_tmdb_id | uint INDEX | 母公司 TMDB ID，可为空 |
| created_at / updated_at | timestamp | |

---

## 第 1 层：核心内容实体

### movies（电影）预计 100 万+ 条
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| tmdb_id | uint UNIQUE | |
| imdb_id | varchar(20) | 可为空 |
| title | varchar(500) | 中文标题 |
| original_title | varchar(500) | |
| original_language | varchar(10) | |
| overview | text | 可为空 |
| tagline | varchar(500) | 可为空 |
| status | varchar(50) | Released / Post Production 等 |
| release_date | date INDEX | 可为空 |
| runtime | int | 分钟，可为空 |
| budget | bigint | 可为空 |
| revenue | bigint | 可为空 |
| popularity | float INDEX | |
| vote_average | float | |
| vote_count | int | |
| adult | boolean | |
| video | boolean | |
| poster_path | varchar(255) INDEX | 可为空 |
| backdrop_path | varchar(255) INDEX | 可为空 |
| homepage | varchar(500) | 可为空 |
| spoken_language_codes | json | 可为空 |
| production_country_codes | json | 可为空 |
| created_at / updated_at | timestamp | |

### tv_shows（电视剧）预计 20 万+ 条
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| tmdb_id | uint UNIQUE | |
| name | varchar(500) | 中文名称 |
| original_name | varchar(500) | |
| original_language | varchar(10) | |
| overview | text | 可为空 |
| tagline | varchar(500) | 可为空 |
| status | varchar(50) | Returning Series / Ended 等 |
| type | varchar(50) | Scripted / Reality 等，可为空 |
| first_air_date | date INDEX | 可为空 |
| last_air_date | date | 可为空 |
| number_of_seasons | int | |
| number_of_episodes | int | |
| episode_run_time | json | 可为空 |
| popularity | float INDEX | |
| vote_average | float | |
| vote_count | int | |
| adult | boolean | |
| in_production | boolean | |
| poster_path | varchar(255) INDEX | 可为空 |
| backdrop_path | varchar(255) INDEX | 可为空 |
| homepage | varchar(500) | 可为空 |
| origin_country_codes | json | 可为空 |
| spoken_language_codes | json | 可为空 |
| language_codes | json | 可为空 |
| production_country_codes | json | 可为空 |
| last_episode_to_air | json | 快照，可为空 |
| next_episode_to_air | json | 快照，可为空 |
| created_at / updated_at | timestamp | |

---

## 第 1 层：关系表（多对多）

### movie_genres / movie_keywords / movie_production_companies
各含唯一约束，关联字段名：
- `movie_genres`：`genre_id`
- `movie_keywords`：`keyword_id`
- `movie_production_companies`：`company_id`（注意：不是 `production_company_id`）

### movie_credits（演职人员，异步关联）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| movie_id | bigint | → movies.id |
| person_tmdb_id | uint | 同步时填充 |
| person_id | bigint NULL | reconcile 后填充 |
| credit_type | enum | `cast` 或 `crew` |
| character | varchar(500) | 可为空 |
| cast_order | int | 可为空 |
| department_id | bigint | 可为空 |
| job_id | bigint | 可为空 |
| created_at / updated_at | timestamp | |

唯一约束：`(movie_id, person_tmdb_id, credit_type, job_id)`

### tv_show_genres / tv_show_keywords / tv_show_networks / tv_show_production_companies
各含对应的 `(tv_show_id, {entity}_id)` 唯一约束。

### tv_show_creators（异步关联）
| 字段 | 类型 | 说明 |
|------|------|------|
| tv_show_id | bigint | |
| person_tmdb_id | uint | |
| person_id | bigint NULL | reconcile 后填充 |

---

## 第 1 层：图片表

### movie_images
`(movie_id, file_path)` 唯一，image_type: `poster` / `backdrop` / `logo`

### tv_show_images
`(tv_show_id, file_path)` 唯一，image_type: `poster` / `backdrop` / `logo`

---

## 第 2 层：合集与电视剧季

### collections
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| tmdb_id | uint UNIQUE | |
| name | varchar(255) | |
| overview | longtext | 可为空 |
| poster_path | varchar(255) INDEX | 可为空 |
| backdrop_path | varchar(255) INDEX | 可为空 |

### collection_movies（异步关联）
`(collection_id, movie_tmdb_id)` 唯一，`movie_id` 初始 NULL。

### tv_seasons（电视剧季）预计 100 万+ 条
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| tv_show_id | bigint INDEX | |
| tmdb_id | uint UNIQUE | |
| season_number | int | |
| name | varchar(500) | 可为空 |
| overview | text | 可为空 |
| poster_path | varchar(255) INDEX | 可为空 |
| air_date | date | 可为空 |
| episode_count | int | 可为空 |
| vote_average | float | 可为空 |

唯一约束：`(tv_show_id, season_number)`

### tv_season_images
`(tv_season_id, file_path)` 唯一，image_type: `poster`

---

## 第 3 层：电视剧集

### tv_episodes（电视剧集）预计 2000 万+ 条
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| tv_show_id | bigint INDEX | |
| tv_season_id | bigint INDEX | |
| tmdb_id | uint UNIQUE | |
| season_number | int | |
| episode_number | int | |
| episode_type | varchar(50) | 可为空 |
| production_code | varchar(50) | 可为空 |
| name | varchar(500) | 可为空 |
| overview | text | 可为空 |
| air_date | date | 可为空 |
| runtime | int | 可为空 |
| still_path | varchar(255) INDEX | 可为空 |
| vote_average | float | 可为空 |
| vote_count | int | 可为空 |

唯一约束：`(tv_season_id, episode_number)`

### tv_episode_credits（异步关联）
唯一约束：`(tv_episode_id, person_tmdb_id, credit_type, job_id)`

### tv_episode_images
`(tv_episode_id, file_path)` 唯一，image_type: `still`

---

## 媒体列表快照

### media_list_snapshots
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| list_type | varchar(30) | 见下表 |
| tmdb_id | uint | |
| local_id | bigint NULL | entity refresh 后填充 |
| rank | smallint | 1-based |
| popularity | decimal(10,3) | |
| snapshot_date | date | UTC |

唯一约束：`(list_type, snapshot_date, tmdb_id)`
索引：`(list_type, snapshot_date, rank)`

list_type 枚举值：`movie_trending_day` / `movie_trending_week` / `movie_now_playing` / `movie_upcoming` / `tv_trending_day` / `tv_trending_week` / `tv_airing_today` / `tv_on_the_air` / `person_trending_day` / `person_trending_week`

---

## 异步关联说明

以下关系表采用异步关联模式，同步时只写 `{entity}_tmdb_id`，`person_id` / `movie_id` 初始为 NULL，由 `reconcile` 步骤批量补填：

- `movie_credits`（person_id）
- `tv_show_creators`（person_id）
- `tv_episode_credits`（person_id）
- `collection_movies`（movie_id）

**API 层处理原则：** `person_id` 为 NULL 时，通过 `person_tmdb_id` 关联查询，或在响应中标记为待关联状态，不报错。
