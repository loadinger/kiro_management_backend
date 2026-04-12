<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleListTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/articles');

        $response->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_returns_paginated_article_list(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        Article::factory()->count(3)->create();

        $response = $this->withToken($token)->getJson('/api/articles');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonStructure([
                'data' => [
                    'list',
                    'pagination' => ['total', 'page', 'per_page', 'last_page'],
                ],
            ]);

        $this->assertCount(3, $response->json('data.list'));
    }

    public function test_filters_by_status(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        Article::factory()->count(2)->create(['status' => 'draft']);
        Article::factory()->count(2)->create(['status' => 'published', 'slug' => null]);

        // Give each published article a unique slug
        Article::where('status', 'published')->get()->each(function (Article $article, int $i) {
            $article->update(['slug' => 'published-slug-' . ($i + 1)]);
        });

        $response = $this->withToken($token)->getJson('/api/articles?status=published');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $list = $response->json('data.list');
        $this->assertCount(2, $list);
        foreach ($list as $item) {
            $this->assertSame('published', $item['status']);
        }
    }

    public function test_sorts_by_sort_order_ascending(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        Article::factory()->create(['sort_order' => 30]);
        Article::factory()->create(['sort_order' => 10]);
        Article::factory()->create(['sort_order' => 20]);

        $response = $this->withToken($token)->getJson('/api/articles?sort=sort_order&order=asc');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $list = $response->json('data.list');
        $sortOrders = array_column($list, 'sort_order');

        for ($i = 1; $i < count($sortOrders); $i++) {
            $this->assertGreaterThanOrEqual(
                $sortOrders[$i - 1],
                $sortOrders[$i],
                'sort_order should be monotonically non-decreasing'
            );
        }
    }

    public function test_list_does_not_contain_content_field(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        Article::factory()->count(2)->create();

        $response = $this->withToken($token)->getJson('/api/articles');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $list = $response->json('data.list');
        foreach ($list as $item) {
            $this->assertArrayNotHasKey('content', $item);
        }
    }
}
