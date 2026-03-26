# Project Overview

## 项目定位

Filmly Management Backend — 影视数据管理系统后台 API 服务。

纯 RESTful HTTP API，无展示逻辑。数据来源于 TMDB，由独立的数据采集项目写入数据库，本项目只负责数据的读取输出和少量 CRUD 管理功能。

## 技术栈

- PHP 8.3 + Laravel 11
- MySQL 8.0（云端，连接配置见 .env）
- Redis 7.x（缓存）
- tymon/jwt-auth 2.x（JWT 认证）
- 运行环境：macOS 本地 + Nginx + PHP-FPM

## 架构分层

```
Routes → FormRequest (验证) → Controller → Service → Repository → Model
```

- Controller：只负责接收请求、调用 Service、返回响应，不含业务逻辑
- Service：业务逻辑层，可跨 Repository 组合数据
- Repository：数据访问层，封装所有 Eloquent/DB 查询
- Model：Eloquent 模型，定义关联关系和字段映射

## 目录结构约定

```
app/
  Http/
    Controllers/
      Api/          # 所有 API Controller
    Requests/       # FormRequest 验证类
    Resources/      # API Resource 响应序列化
  Services/         # 业务逻辑
  Repositories/     # 数据访问
  Models/           # Eloquent 模型
  Exceptions/       # 自定义异常
routes/
  api.php           # 所有 API 路由
```

## 数据库

数据库结构详见 `.kiro/steering/database-schema.md`。

数据库中的数据绝大多数为只读（来自 TMDB 采集），以下为可写业务：
- 用户管理（users 表，管理员账号）
- 专题文章（待规划）
