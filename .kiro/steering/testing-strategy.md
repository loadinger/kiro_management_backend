# 测试策略

## 测试原则

- 测试覆盖业务逻辑，不测试框架本身
- 优先写 Feature Test（HTTP 层集成测试），Unit Test 只用于复杂纯逻辑
- 测试代码与生产代码遵循同等命名和代码规范
- 每个测试方法只验证一个行为

---

## 测试分层

### Feature Test（主要）

位置：`tests/Feature/`

覆盖范围：
- 所有 API 端点的 HTTP 请求/响应
- 认证流程（登录、token 验证、过期）
- 参数验证（必填项、类型、边界值）
- 分页行为
- 权限控制（未登录返回 401）

不覆盖：
- 数据库内部实现细节
- Repository 的具体 SQL

```php
// Feature Test 示例
class MovieListTest extends TestCase
{
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/movies');
        $response->assertStatus(200)
                 ->assertJson(['code' => 401]);
    }

    public function test_returns_paginated_movie_list(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/movies?per_page=10');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'code',
                     'message',
                     'data' => [
                         'items',
                         'meta' => ['total', 'page', 'per_page', 'last_page'],
                     ],
                 ])
                 ->assertJson(['code' => 0]);
    }
}
```

### Unit Test（按需）

位置：`tests/Unit/`

覆盖范围：
- Service 层复杂业务逻辑（需要 mock Repository）
- Helper 类（如 `ImageHelper::url()`）
- 自定义 Enum 的行为

不覆盖：
- Controller（由 Feature Test 覆盖）
- Repository（由 Feature Test 间接覆盖）
- 简单的 getter/setter

```php
// Unit Test 示例（ImageHelper）
class ImageHelperTest extends TestCase
{
    public function test_returns_full_url_with_size(): void
    {
        $url = ImageHelper::url('/abc123.jpg', 'w342');
        $this->assertSame('https://image.tmdb.org/t/p/w342/abc123.jpg', $url);
    }

    public function test_returns_null_when_path_is_null(): void
    {
        $this->assertNull(ImageHelper::url(null, 'w342'));
    }
}
```

---

## 数据库测试策略

**本项目测试不使用真实云端数据库。**

使用 SQLite in-memory 数据库做测试隔离：

```php
// phpunit.xml 已配置
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

**注意：** 核心业务表（movies、tv_shows 等）由采集项目维护，本项目没有这些表的 migration。

测试策略：
- 只读业务的 Feature Test 使用 **mock Service / mock Repository**，不依赖真实数据
- 可写业务（users、专题文章）使用 `RefreshDatabase` trait + SQLite in-memory
- 禁止在测试中连接云端数据库

```php
// 只读接口测试：mock Service
class MovieControllerTest extends TestCase
{
    public function test_index_returns_movie_list(): void
    {
        $this->mock(MovieService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')
                 ->once()
                 ->andReturn(new LengthAwarePaginator([], 0, 20));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/movies')
             ->assertJson(['code' => 0]);
    }
}

// 可写接口测试：RefreshDatabase
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_user(): void { ... }
}
```

---

## 测试文件命名与组织

```
tests/
├── Feature/
│   ├── Auth/
│   │   └── AuthTest.php
│   ├── Movies/
│   │   ├── MovieListTest.php
│   │   └── MovieDetailTest.php
│   └── TvShows/
│       └── TvShowListTest.php
└── Unit/
    ├── Helpers/
    │   └── ImageHelperTest.php
    └── Services/
        └── MovieServiceTest.php
```

命名规范：
- 测试类：`{被测类名}Test`
- 测试方法：`test_{描述行为的英文短语}`，如 `test_returns_404_when_movie_not_found`

---

## 运行测试

```bash
# 运行全部测试
php artisan test

# 运行指定目录
php artisan test --testsuite=Feature

# 运行指定文件
php artisan test tests/Feature/Movies/MovieListTest.php

# 运行指定方法
php artisan test --filter test_returns_paginated_movie_list
```

---

## 测试覆盖要求

| 场景 | 是否必须测试 |
|------|------------|
| 未认证请求返回 401 | 必须 |
| 参数验证失败返回 422 | 必须 |
| 正常请求返回正确结构 | 必须 |
| 资源不存在返回 404 | 必须 |
| 大表深翻页限制 | 必须 |
| 分页 meta 结构正确 | 必须 |
| 图片 URL 格式正确 | 必须（Unit Test） |
| 异步关联 null 安全 | 必须 |
| Service 复杂分支逻辑 | 按需 |
