# Project Overview

## 项目定位

Filmly Management Backend — 影视数据管理系统后台 API 服务。

纯 RESTful HTTP API，无展示逻辑。数据来源于 TMDB，由独立的数据采集项目写入数据库，本项目只负责数据的读取输出和少量 CRUD 管理功能。

## 技术栈

| 组件 | 版本 | 说明 |
|------|------|------|
| PHP | 8.3 | 严格类型模式 |
| Laravel | 11.x | 纯 API 模式，无前端资源 |
| MySQL | 8.0 | 云端，只读为主 |
| Redis | 7.x | 缓存（按需使用） |
| tymon/jwt-auth | 2.x | JWT 认证 |
| Laravel Pint | 内置 | 代码格式化 |

- 开发环境：macOS 本地 + Nginx + PHP-FPM
- 生产环境：TBD

## 架构分层

```
Request
  └── routes/api.php
        └── FormRequest（参数验证）
              └── Controller（接收/响应）
                    └── Service（业务逻辑）
                          └── Repository（数据访问）
                                └── Model（Eloquent）
```

详细职责边界见 `.kiro/steering/coding-standards.md`。

## 目录结构

```
app/
├── Exceptions/          # 自定义异常（继承 AppException）
├── Helpers/             # 工具类（ImageHelper 等）
├── Http/
│   ├── Controllers/Api/ # 所有 API Controller
│   ├── Requests/        # FormRequest 验证类
│   └── Resources/       # API Resource 响应序列化
├── Models/              # Eloquent 模型
├── Repositories/
│   ├── Contracts/       # Repository 接口
│   └── *.php            # Repository 实现
└── Services/            # 业务逻辑层
routes/
└── api.php              # 所有 API 路由
.kiro/steering/          # AI 开发上下文文档
```

## Steering 文档索引

| 文档 | 内容 |
|------|------|
| `project-overview.md` | 本文件，项目全局概览 |
| `database-schema.md` | 完整数据库表结构 |
| `api-conventions.md` | 响应格式、分页、路由、命名规范 |
| `coding-standards.md` | 分层规范、依赖注入、异常、日志、注释 |
| `data-flow.md` | 读写边界、异步关联、图片 URL、大表约束 |
| `development-workflow.md` | 环境启动、新增模块流程、目录速查 |
| `testing-strategy.md` | 测试分层、mock 策略、覆盖要求 |
| `security.md` | 认证、输入安全、CORS、敏感信息保护 |

## 数据库读写边界

- 只读：movies / tv_shows / persons 等所有 TMDB 采集数据表
- 可写：users（管理员账号）、专题文章（待规划）
- 扩展写入：departments / jobs / keywords / languages 的 `name_zh` / `translated_at` 字段（由翻译任务写入，见下方）

详见 `.kiro/steering/data-flow.md`。

---

## LLM 翻译模块

### 背景

数据库中 departments、jobs、keywords、languages 等参考数据表的 `name` 字段均为英文（来自 TMDB），需要翻译为中文供前端展示。

### 技术方案

- LLM 环境：本地部署 Ollama + Qwen 2.5 7B，兼容 OpenAI Chat Completions 格式
- 翻译结果存储：各表新增 `name_zh`（nullable varchar）和 `translated_at`（nullable timestamp）字段
- `translated_at` 为 null 表示未翻译，非 null 为上次翻译时间
- 原始 `name` 字段保持不变，API 输出时 `name_zh` 为 null 时降级返回 `name`

### 涉及表与字段

| 表 | 新增字段 | 备注 |
|----|---------|------|
| `departments` | `name_zh`, `translated_at` | |
| `jobs` | `name_zh`, `translated_at` | 翻译时带 department.name 作为上下文 |
| `keywords` | `name_zh`, `translated_at` | 数据量大，支持断点续传 |
| `languages` | `name_zh`, `translated_at` | |

### 架构分层

```
Artisan Command (translate:names)
  └── TranslationService（按表分发，构建上下文）
        └── LlmTranslationService（封装 Ollama 调用）
              └── Ollama /api/chat（format: "json"）
```

### Prompt 设计原则

**System Prompt 核心约束：**
1. 领域锚定：明确告知模型这是 TMDB 影视行业术语
2. 简洁约束：译文必须是词或短语，不能是句子
   - `keywords` / `departments` / `languages`：≤ 8 个汉字
   - `jobs`：≤ 12 个汉字
3. 正反例示范（对小模型效果显著）：
   - ❌ "based on novel" → "这是一部基于小说改编的作品"
   - ✓ "based on novel" → "小说改编"
4. `jobs` 表额外带部门名上下文，提升职位翻译准确性

**批量翻译结构（用 id 映射，不依赖顺序）：**
```json
// 输入
{"task": "translate_to_chinese", "context": "电影制作职位，所属部门：Camera", "items": [{"id": 1, "text": "Director of Photography"}]}
// 期望输出
[{"id": 1, "translation": "摄影指导"}]
```

### JSON 格式容错策略

小模型输出格式不稳定，采用多层防御：

1. Ollama `format: "json"` 参数强制 JSON 输出
2. 正则从响应中提取 `[...]` 或 `{...}` 块，处理 markdown 代码块等污染
3. 解析失败自动重试，最多 3 次，批量大小递减（20 → 5 → 1）
4. 全部重试失败则跳过该批次，不写入 `translated_at`，下次运行重新处理

### Artisan Command

```bash
php artisan translate:names --table=keywords --batch-size=20
php artisan translate:names --table=all
php artisan translate:names --table=keywords --limit=100  # 小批验证质量
```

- `keywords` 支持断点续传（`WHERE name_zh IS NULL`），其余表数据量小无需
- 显示进度条，输出成功/失败/跳过统计

---

## Dashboard 数据统计模块（已实现）

接口均需 `auth:api` 认证，结果走 Redis 缓存。

| 接口 | 缓存 TTL | 说明 |
|------|---------|------|
| `GET /api/dashboard/stats` | 10 分钟 | 聚合统计，各子项独立查询，单项失败返回 null 不影响其他项 |
| `GET /api/dashboard/trends` | 5 分钟 | 实体每日新增趋势，缓存 key 含 days + entities |

### stats 返回字段

| 字段 | 说明 |
|------|------|
| `entity_counts` | 各主要实体总记录数 |
| `reconcile_rates` | 异步关联表 reconcile 完成率（total / resolved / rate） |
| `translation_coverage` | 各表中文翻译覆盖率（total / translated / rate） |
| `data_freshness` | 各表最后更新时间（last_updated_at / is_stale，超 48 小时标记为 stale） |
| `snapshot_health` | 最近 30 天快照健康状况（checked_days / healthy_days / missing_dates） |

### trends 请求参数

| 参数 | 类型 | 说明 |
|------|------|------|
| `days` | int | 查询天数 |
| `entities` | array | 实体名称列表 |

trends 返回：`dates`（日期序列，升序）+ `series`（各实体每日新增数量，缺失日期填 0）

---

## 已实现模块

### Auth 模块
| 接口 | 说明 |
|------|------|
| `POST /api/auth/login` | 登录，限流 10次/分钟/IP |
| `POST /api/auth/refresh` | 刷新 token（无需认证） |
| `POST /api/auth/logout` | 登出，token 加入黑名单 |
| `GET /api/auth/me` | 当前登录用户信息 |

### 基础参考数据模块
| 接口 | 说明 |
|------|------|
| `GET /api/countries` | 国家列表 |
| `GET /api/departments` | 部门列表 |
| `GET /api/genres` | 类型列表 |
| `GET /api/jobs` | 职位列表 |
| `GET /api/keywords` | 关键词列表 |
| `GET /api/languages` | 语言列表 |
| `GET /api/production-companies` | 制作公司列表 |
| `GET /api/production-companies/{id}` | 制作公司详情 |
| `GET /api/tv-networks` | 电视网络列表 |
| `GET /api/tv-networks/{id}` | 电视网络详情 |

### Dashboard 模块
| 接口 | 说明 |
|------|------|
| `GET /api/dashboard/stats` | 聚合统计（实体总量、关联完成率、翻译覆盖率等） |
| `GET /api/dashboard/trends` | 实体每日新增趋势 |

---

## 语言约定

| 场景 | 语言 |
|------|------|
| Kiro 与用户交流 | 中文 |
| Spec 文档（requirements.md、design.md、tasks.md） | 中文 |
| 代码注释、PHPDoc | 英文 |
| 变量名、类名、方法名等代码标识符 | 英文 |
| 日志输出 | 英文 |
