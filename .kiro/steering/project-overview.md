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

详见 `.kiro/steering/data-flow.md`。

## 语言约定

| 场景 | 语言 |
|------|------|
| Kiro 与用户交流 | 中文 |
| Spec 文档（requirements.md、design.md、tasks.md） | 中文 |
| 代码注释、PHPDoc | 英文 |
| 变量名、类名、方法名等代码标识符 | 英文 |
| 日志输出 | 英文 |
