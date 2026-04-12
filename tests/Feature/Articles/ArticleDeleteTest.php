<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\ArticleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->deleteJson('/api/articles/1');

        $response->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_deletes_article_successfully(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $article = Article::factory()->create();

        $response = $this->withToken($token)->deleteJson("/api/articles/{$article->id}");

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    public function test_cascades_delete_to_article_items(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $article = Article::factory()->create([
            'content' => '::media{type="movie" id="10"}',
        ]);
        ArticleItem::create([
            'article_id' => $article->id,
            'entity_type' => 'movie',
            'entity_id' => 10,
        ]);

        $this->withToken($token)->deleteJson("/api/articles/{$article->id}");

        $this->assertDatabaseMissing('article_items', ['article_id' => $article->id]);
    }

    public function test_returns_404_when_article_not_found(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->deleteJson('/api/articles/99999');

        $response->assertStatus(200)
            ->assertJson(['code' => 404]);
    }
}
