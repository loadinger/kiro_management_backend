<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/articles/1');

        $response->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_returns_article_detail_with_entities(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $article = Article::factory()->create();

        $response = $this->withToken($token)->getJson("/api/articles/{$article->id}");

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonStructure([
                'data' => ['id', 'title', 'content', 'status', 'entities'],
            ]);

        // entities should be an object (empty when no items)
        $entities = $response->json('data.entities');
        $this->assertIsArray($entities);
    }

    public function test_returns_404_when_article_not_found(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/articles/99999');

        $response->assertStatus(200)
            ->assertJson(['code' => 404]);
    }

    public function test_cover_url_is_null_when_cover_path_is_null(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $article = Article::factory()->create(['cover_path' => null]);

        $response = $this->withToken($token)->getJson("/api/articles/{$article->id}");

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonPath('data.cover_url', null);
    }
}
