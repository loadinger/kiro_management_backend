<?php

declare(strict_types=1);

namespace Tests\Feature\Refs;

use App\Exceptions\AppException;
use App\Models\ProductionCompany;
use App\Models\User;
use App\Services\ProductionCompanyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class ProductionCompanyTest extends TestCase
{
    use RefreshDatabase;

    private function token(): string
    {
        return auth('api')->login(User::factory()->create());
    }

    // ── index ──────────────────────────────────────────────

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/production-companies')
            ->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_index_returns_paginated_list(): void
    {
        $this->mock(ProductionCompanyService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 0, 20));
        });

        $this->withToken($this->token())->getJson('/api/production-companies')
            ->assertStatus(200)
            ->assertJsonStructure([
                'code', 'message',
                'data' => [
                    'list',
                    'pagination' => ['total', 'page', 'per_page', 'last_page'],
                ],
            ])
            ->assertJson(['code' => 0]);
    }

    public function test_index_invalid_sort_returns_422(): void
    {
        $this->withToken($this->token())->getJson('/api/production-companies?sort=name')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_index_per_page_exceeds_max_returns_422(): void
    {
        $this->withToken($this->token())->getJson('/api/production-companies?per_page=101')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    // ── show ───────────────────────────────────────────────

    public function test_show_returns_company(): void
    {
        $company = (new ProductionCompany())->forceFill(['id' => 1, 'tmdb_id' => 420, 'name' => 'Marvel Studios']);

        $this->mock(ProductionCompanyService::class, function (MockInterface $mock) use ($company) {
            $mock->shouldReceive('findById')
                ->with(1)
                ->once()
                ->andReturn($company);
        });

        $this->withToken($this->token())->getJson('/api/production-companies/1')
            ->assertStatus(200)
            ->assertJsonStructure(['code', 'message', 'data' => ['id', 'tmdb_id', 'name']])
            ->assertJson(['code' => 0]);
    }

    public function test_show_not_found_returns_404(): void
    {
        $this->mock(ProductionCompanyService::class, function (MockInterface $mock) {
            $mock->shouldReceive('findById')
                ->with(999)
                ->once()
                ->andThrow(new AppException('制作公司不存在', 404));
        });

        $this->withToken($this->token())->getJson('/api/production-companies/999')
            ->assertStatus(200)
            ->assertJson(['code' => 404]);
    }

    public function test_show_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/production-companies/1')
            ->assertStatus(200)
            ->assertJson(['code' => 401]);
    }
}
