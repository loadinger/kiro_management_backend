# Filmly Management Backend — 开发进度

## 项目状态：准备阶段

---

## Spec 列表

状态说明：`待开始` / `进行中` / `已完成` / `已暂停`

### System
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| S-01 | Auth：login / logout / refresh / me | 待开始 | 骨架已实现，待完善测试 |

### Movies
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| M-01 | Movie List：列表，支持排序、过滤、搜索 | 待开始 | |
| M-02 | Movie Detail：详情 | 待开始 | |
| M-03 | Movie Credits：list | 待开始 | |
| M-04 | Movie Images：list | 待开始 | |
| M-05 | Movie Genres：list | 待开始 | |
| M-06 | Movie Keywords：list | 待开始 | |
| M-07 | Movie Production Companies：list | 待开始 | |
| M-08 | Movie Collections：list | 待开始 | |

### TV Shows
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| T-01 | TV Show List：列表，支持排序、过滤、搜索 | 待开始 | |
| T-02 | TV Show Detail：详情 | 待开始 | |
| T-03 | TV Show Creators：list | 待开始 | |
| T-04 | TV Show Genres：list | 待开始 | |
| T-05 | TV Show Images：list | 待开始 | |
| T-06 | TV Show Keywords：list | 待开始 | |
| T-07 | TV Show Networks：list | 待开始 | |
| T-08 | TV Show Production Companies：list | 待开始 | |

### TV Seasons
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| TS-01 | TV Season List：列表，支持排序、搜索 | 待开始 | 必须带 tv_show_id |
| TS-02 | TV Season Detail：详情 | 待开始 | |
| TS-03 | TV Season Images：list | 待开始 | |

### TV Episodes
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| TE-01 | TV Episode List：列表，支持排序、搜索 | 待开始 | 必须带 tv_season_id |
| TE-02 | TV Episode Detail：详情 | 待开始 | |
| TE-03 | TV Episode Credits：list | 待开始 | |
| TE-04 | TV Episode Images：list | 待开始 | |

### Persons
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| P-01 | Person List：列表，支持排序、过滤、搜索 | 待开始 | 大表，per_page ≤ 50 |
| P-02 | Person Detail：详情 | 待开始 | |

### Media List Snapshots
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| ML-01 | Media List Snapshots：list | 待开始 | 按 list_type + snapshot_date 查询 |

### 基础参考数据
| # | 接口 | 状态 | 备注 |
|---|------|------|------|
| R-01 | Countries：list | 待开始 | 小表，可缓存 |
| R-02 | Departments：list | 待开始 | 小表，可缓存 |
| R-03 | Genres：list | 待开始 | 小表，可缓存 |
| R-04 | Jobs：list | 待开始 | 小表，可缓存 |
| R-05 | Keywords：list | 待开始 | 数十万条，支持搜索 |
| R-06 | Languages：list | 待开始 | 小表，可缓存 |
| R-07 | Production Companies：list | 待开始 | 数十万条，支持搜索 |

---

## 开发日志

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

## 决策记录

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

- [ ] 生产环境部署方案（服务器/容器化）
- [ ] 专题文章模块的具体字段设计
- [ ] Redis 缓存策略（哪些接口需要缓存、TTL 设置）
- [ ] 是否需要操作日志（记录管理员的写操作）
