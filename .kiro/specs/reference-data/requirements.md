# 需求文档：参考数据 API（reference-data）

## 简介

本功能为 Filmly 管理后台提供一组只读参考数据的 RESTful API 接口。参考数据由独立的数据采集项目从 TMDB 写入数据库，本项目只负责查询输出。

参考数据分为两类：

- 轻量参考数据：仅提供列表与搜索接口（countries、departments、genres、jobs、keywords、languages）
- 富参考数据：提供列表、搜索与详情接口，含图片 URL 拼接（production_companies、tv_networks）

所有接口均需 JWT 认证，响应遵循项目统一信封格式，列表接口支持分页。

---

## 词汇表

- **ReferenceData_API**：本功能涉及的所有参考数据接口的统称
- **Country**：国家/地区实体，对应 `countries` 表
- **Department**：部门实体，对应 `departments` 表
- **Genre**：影视类型实体，对应 `genres` 表，type 字段区分 `movie` 或 `tv`
- **Job**：职位实体，对应 `jobs` 表，通过 `department_id` 关联 Department
- **Keyword**：关键词实体，对应 `keywords` 表
- **Language**：语言实体，对应 `languages` 表
- **ProductionCompany**：制作公司实体，对应 `production_companies` 表，含 logo 图片
- **TvNetwork**：电视网络实体，对应 `tv_networks` 表，含 logo 图片
- **ImageHelper**：`App\Helpers\ImageHelper`，负责将数据库相对路径拼接为完整图片 URL
- **JWT**：JSON Web Token，项目使用 `tymon/jwt-auth` 实现的认证机制
- **信封格式**：统一响应结构 `{ code, message, data }`
- **分页 meta**：列表响应中的分页信息 `{ total, page, per_page, last_page }`

---

## 需求列表

### 需求 1：认证保护

**用户故事：** 作为系统管理员，我希望所有参考数据接口都需要有效的 JWT Token 才能访问，以确保数据不被未授权访问。

#### 验收标准

1. WHEN 请求参考数据接口时未携带 Authorization 请求头，THE ReferenceData_API SHALL 返回包含 `code: 401` 的响应，`data` 为 null
2. WHEN 请求参考数据接口时携带已过期的 JWT Token，THE ReferenceData_API SHALL 返回包含 `code: 401` 的响应，`data` 为 null
3. WHEN 请求参考数据接口时携带有效的 JWT Token，THE ReferenceData_API SHALL 正常处理请求并返回数据

---

### 需求 2：统一响应格式

**用户故事：** 作为前端开发者，我希望所有参考数据接口的响应格式保持一致，以便统一处理。

#### 验收标准

1. THE ReferenceData_API SHALL 对所有请求统一返回 HTTP 状态码 200，业务状态通过响应体中的 `code` 字段区分
2. WHEN 请求成功时，THE ReferenceData_API SHALL 返回包含 `code: 0`、`message: "success"` 和 `data` 字段的响应体
3. WHEN 参数验证失败时，THE ReferenceData_API SHALL 返回包含 `code: 422` 和具体错误描述的响应体，`data` 为 null
4. WHEN 请求的资源不存在时，THE ReferenceData_API SHALL 返回包含 `code: 404` 的响应体，`data` 为 null

---

### 需求 3：列表分页支持

**用户故事：** 作为前端开发者，我希望所有列表接口支持分页，以便按需加载数据。

#### 验收标准

1. THE ReferenceData_API SHALL 在所有列表接口中支持 `page`（页码，默认 1）和 `per_page`（每页条数，默认 20）查询参数
2. WHEN `per_page` 参数值超过 100 时，THE ReferenceData_API SHALL 返回 `code: 422` 的参数验证错误
3. WHEN `page` 参数值超过 1000 时，THE ReferenceData_API SHALL 返回 `code: 422` 的参数验证错误
4. WHEN 列表请求成功时，THE ReferenceData_API SHALL 在 `data` 中返回 `items` 数组和包含 `total`、`page`、`per_page`、`last_page` 的 `meta` 对象

---

### 需求 4：国家/地区列表与搜索（countries）

**用户故事：** 作为前端开发者，我希望能获取国家/地区列表并按名称搜索，以便在筛选器中使用。

#### 验收标准

1. WHEN 发送 `GET /api/countries` 请求时，THE ReferenceData_API SHALL 返回分页的国家/地区列表，每条记录包含 `id`、`iso_3166_1`、`english_name`、`native_name` 字段
2. WHEN 请求携带 `q` 参数时，THE ReferenceData_API SHALL 对 `english_name` 和 `native_name` 字段执行前缀匹配过滤
3. WHEN `q` 参数长度超过 100 个字符时，THE ReferenceData_API SHALL 返回 `code: 422` 的参数验证错误
4. WHEN 请求携带 `sort` 参数时，THE ReferenceData_API SHALL 仅允许 `id`、`english_name` 作为合法排序字段，其他值返回 `code: 422`
5. WHEN 请求携带 `order` 参数时，THE ReferenceData_API SHALL 仅允许 `asc` 或 `desc` 值，默认为 `asc`

---

### 需求 5：部门列表与搜索（departments）

**用户故事：** 作为前端开发者，我希望能获取部门列表并按名称搜索，以便在职位筛选中使用。

#### 验收标准

1. WHEN 发送 `GET /api/departments` 请求时，THE ReferenceData_API SHALL 返回分页的部门列表，每条记录包含 `id`、`name` 字段
2. WHEN 请求携带 `q` 参数时，THE ReferenceData_API SHALL 对 `name` 字段执行前缀匹配过滤
3. WHEN `q` 参数长度超过 100 个字符时，THE ReferenceData_API SHALL 返回 `code: 422` 的参数验证错误
4. WHEN 请求携带 `sort` 参数时，THE ReferenceData_API SHALL 仅允许 `id`、`name` 作为合法排序字段，其他值返回 `code: 422`
5. WHEN 请求携带 `order` 参数时，THE ReferenceData_API SHALL 仅允许 `asc` 或 `desc` 值，默认为 `asc`

---

### 需求 6：影视类型列表与搜索（genres）

**用户故事：** 作为前端开发者，我希望能获取影视类型列表，并支持按 type 筛选电影或剧集类型，以便在内容筛选器中使用。

#### 验收标准

1. WHEN 发送 `GET /api/genres` 请求时，THE ReferenceData_API SHALL 返回分页的类型列表，每条记录包含 `id`、`tmdb_id`、`name`、`type` 字段
2. WHEN 请求携带 `type` 参数时，THE ReferenceData_API SHALL 仅返回 `type` 字段与参数值匹配的记录
3. WHEN `type` 参数值不是 `movie` 或 `tv` 时，THE ReferenceData_API SHALL 返回 `code: 422` 的参数验证错误
4. WHEN 请求携带 `q` 参数时，THE ReferenceData_API SHALL 对 `name` 字段执行前缀匹配过滤
5. WHEN `q` 参数长度超过 100 个字符时，THE ReferenceData_API SHALL 返回 `code: 422` 的参数验证错误
6. WHEN 请求携带 `sort` 参数时，THE ReferenceData_API SHALL 仅允许 `id`、`name` 作为合法排序字段，其他值返回 `code: 422`
7. WHEN 请求携带 `order` 参数时，THE ReferenceData_API SHALL 仅允许 `asc` 或 `desc` 值，默认为 `asc`

---

### 需求 7：职位列表与搜索（jobs）

**用户故事：** 作为前端开发者，我希望能获取职位列表，并支持按部门筛选，以便在演职人员筛选中使用。

#### 验收标准

1. WHEN 发送 `GET /api/jobs` 请求时，THE ReferenceData_API SHALL 返回分页的职位列表，每条记录包含 `id`、`name`、`department_id` 字段
2. WHEN 请求携带 `department_id` 参数时，THE ReferenceData_API SHALL 仅返回 `department_id` 匹配的职位记录
3. WHEN `department_id` 参数值不是正整数时，THE ReferenceData_API SHALL 返回 `code: 422` 的参数验证错误
4. WHEN 请求携带 `q` 参数时，THE ReferenceData_API SHALL 对 `name` 字段执行前缀匹配过滤
5. WHEN `q` 参数长度超过 100 个字符时，THE ReferenceData_API SHALL 返回 `code: 422` 的参数验证错误
6. WHEN 请求携带 `sort` 参数时，THE ReferenceData_API SHALL 仅允许 `id`、`name`、`department_id` 作为合法排序字段，其他值返回 `code: 422`
7. WHEN 请求携带 `order` 参数时，THE ReferenceData_API SHALL 仅允许 `asc` 或 `desc` 值，默认为 `asc`

---

### 需求 8：关键词列表与搜索（keywords）

**用户故事：** 作为前端开发者，我希望能获取关键词列表并按名称搜索，以便在内容标签筛选中使用。

#### 验收标准

1. WHEN 发送 `GET /api/keywords` 请求时，THE ReferenceData_API SHALL 返回分页的关键词列表，每条记录包含 `id`、`tmdb_id`、`name` 字段
2. WHEN 请求携带 `q` 参数时，THE ReferenceData_API SHALL 对 `name` 字段执行前缀匹配过滤
3. WHEN `q` 参数长度超过 100 个字符时，THE ReferenceData_API SHALL 返回 `code: 422` 的参数验证错误
4. WHEN 请求携带 `sort` 参数时，THE ReferenceData_API SHALL 仅允许 `id`、`name` 作为合法排序字段，其他值返回 `code: 422`
5. WHEN 请求携带 `order` 参数时，THE ReferenceData_API SHALL 仅允许 `asc` 或 `desc` 值，默认为 `asc`

---

### 需求 9：语言列表与搜索（languages）

**用户故事：** 作为前端开发者，我希望能获取语言列表并按名称搜索，以便在语言筛选器中使用。

#### 验收标准

1. WHEN 发送 `GET /api/languages` 请求时，THE ReferenceData_API SHALL 返回分页的语言列表，每条记录包含 `id`、`iso_639_1`、`english_name`、`name` 字段
2. WHEN 请求携带 `q` 参数时，THE ReferenceData_API SHALL 对 `english_name` 和 `name` 字段执行前缀匹配过滤
3. WHEN `q` 参数长度超过 100 个字符时，THE ReferenceData_API SHALL 返回 `code: 422` 的参数验证错误
4. WHEN 请求携带 `sort` 参数时，THE ReferenceData_API SHALL 仅允许 `id`、`english_name` 作为合法排序字段，其他值返回 `code: 422`
5. WHEN 请求携带 `order` 参数时，THE ReferenceData_API SHALL 仅允许 `asc` 或 `desc` 值，默认为 `asc`

---

### 需求 10：制作公司列表、搜索与详情（production_companies）

**用户故事：** 作为前端开发者，我希望能获取制作公司列表、按名称搜索，并查看单个制作公司的详细信息，以便在内容关联展示中使用。

#### 验收标准

1. WHEN 发送 `GET /api/production-companies` 请求时，THE ReferenceData_API SHALL 返回分页的制作公司列表，每条记录包含 `id`、`tmdb_id`、`name`、`origin_country`、`logo_url` 字段
2. WHEN 请求携带 `q` 参数时，THE ReferenceData_API SHALL 对 `name` 字段执行前缀匹配过滤
3. WHEN `q` 参数长度超过 100 个字符时，THE ReferenceData_API SHALL 返回 `code: 422` 的参数验证错误
4. WHEN 发送 `GET /api/production-companies/{id}` 请求时，THE ReferenceData_API SHALL 返回该制作公司的完整信息，包含 `id`、`tmdb_id`、`name`、`description`、`headquarters`、`homepage`、`logo_url`、`origin_country`、`parent_company_tmdb_id` 字段
5. WHEN 请求的 `{id}` 在数据库中不存在时，THE ReferenceData_API SHALL 返回 `code: 404`、`message: "制作公司不存在"` 的响应
6. THE ImageHelper SHALL 将列表中的 `logo_path` 以 `w185` 尺寸拼接为完整 URL，将详情中的 `logo_path` 以 `w342` 尺寸拼接为完整 URL
7. WHEN `logo_path` 字段为 null 时，THE ImageHelper SHALL 将响应中的 `logo_url` 输出为 null 而不是报错
8. WHEN 请求携带 `sort` 参数时，THE ReferenceData_API SHALL 仅允许 `id`、`name` 作为合法排序字段，其他值返回 `code: 422`
9. WHEN 请求携带 `order` 参数时，THE ReferenceData_API SHALL 仅允许 `asc` 或 `desc` 值，默认为 `asc`

---

### 需求 11：电视网络列表、搜索与详情（tv_networks）

**用户故事：** 作为前端开发者，我希望能获取电视网络列表、按名称搜索，并查看单个电视网络的详细信息，以便在剧集关联展示中使用。

#### 验收标准

1. WHEN 发送 `GET /api/tv-networks` 请求时，THE ReferenceData_API SHALL 返回分页的电视网络列表，每条记录包含 `id`、`tmdb_id`、`name`、`origin_country`、`logo_url` 字段
2. WHEN 请求携带 `q` 参数时，THE ReferenceData_API SHALL 对 `name` 字段执行前缀匹配过滤
3. WHEN `q` 参数长度超过 100 个字符时，THE ReferenceData_API SHALL 返回 `code: 422` 的参数验证错误
4. WHEN 发送 `GET /api/tv-networks/{id}` 请求时，THE ReferenceData_API SHALL 返回该电视网络的完整信息，包含 `id`、`tmdb_id`、`name`、`headquarters`、`homepage`、`logo_url`、`origin_country` 字段
5. WHEN 请求的 `{id}` 在数据库中不存在时，THE ReferenceData_API SHALL 返回 `code: 404`、`message: "电视网络不存在"` 的响应
6. THE ImageHelper SHALL 将列表中的 `logo_path` 以 `w185` 尺寸拼接为完整 URL，将详情中的 `logo_path` 以 `w342` 尺寸拼接为完整 URL
7. WHEN `logo_path` 字段为 null 时，THE ImageHelper SHALL 将响应中的 `logo_url` 输出为 null 而不是报错
8. WHEN 请求携带 `sort` 参数时，THE ReferenceData_API SHALL 仅允许 `id`、`name` 作为合法排序字段，其他值返回 `code: 422`
9. WHEN 请求携带 `order` 参数时，THE ReferenceData_API SHALL 仅允许 `asc` 或 `desc` 值，默认为 `asc`

---

### 需求 12：数据只读保护

**用户故事：** 作为系统架构师，我希望参考数据接口严格只读，不允许任何写入操作，以防止破坏采集项目维护的数据。

#### 验收标准

1. THE ReferenceData_API SHALL 仅为参考数据资源注册 `GET` 方法的路由，不注册 `POST`、`PUT`、`PATCH`、`DELETE` 路由
2. WHEN 对参考数据资源路径发送非 `GET` 请求时，THE ReferenceData_API SHALL 返回 HTTP 405 Method Not Allowed

---

### 需求 13：排序字段安全校验

**用户故事：** 作为安全工程师，我希望所有排序参数都经过白名单校验，以防止 SQL 注入攻击。

#### 验收标准

1. THE ReferenceData_API SHALL 在每个列表接口的 FormRequest 中通过 `Rule::in()` 声明允许的排序字段白名单
2. WHEN `sort` 参数值不在对应接口的白名单中时，THE ReferenceData_API SHALL 返回 `code: 422` 的参数验证错误，而不是将该值传入数据库查询

---

### 需求 14：图片 URL 拼接正确性

**用户故事：** 作为前端开发者，我希望富参考数据（制作公司、电视网络）的图片字段直接返回完整可访问的 URL，而不是数据库中的相对路径。

#### 验收标准

1. WHEN `logo_path` 不为 null 时，THE ImageHelper SHALL 将其拼接为格式为 `https://image.tmdb.org/t/p/{size}{path}` 的完整 URL
2. WHEN `logo_path` 为 null 时，THE ImageHelper SHALL 返回 null 而不是抛出异常
3. THE ReferenceData_API SHALL 在列表接口中使用 `w185` 尺寸、在详情接口中使用 `w342` 尺寸拼接 logo URL
4. FOR ALL 非 null 的 `logo_path` 值，THE ImageHelper 对拼接结果解析路径部分 SHALL 与原始 `logo_path` 相等（往返属性）
