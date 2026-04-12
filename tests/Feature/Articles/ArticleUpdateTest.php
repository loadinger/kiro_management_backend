<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\ArticleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->putJson('/api/articles/1', []);

        $response->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_updates_article_successfully(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $article = Article::factory()->create(['title' => 'Original Title']);

        $response = $this->withToken($token)->putJson("/api/articles/{$article->id}", [
            'title' => 'Updated Title',
            'content' => $article->content,
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_article_items_are_fully_synced_on_update(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        // Create article with one placeholder
        $article = Article::factory()->create([
            'content' => '::media{type="movie" id="1"}',
        ]);
        ArticleItem::create([
            'article_id' => $article->id,
            'entity_type' => 'movie',
            'entity_id' => 1,
        ]);

        // Update with a different placeholder
        $response = $this->withToken($token)->putJson("/api/articles/{$article->id}", [
            'content' => '::media{type="person" id="5"}',
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        // Old item should be gone
        $this->assertDatabaseMissing('article_items', [
            'article_id' => $article->id,
            'entity_type' => 'movie',
            'entity_id' => 1,
        ]);

        // New item should exist
        $this->assertDatabaseHas('article_items', [
            'article_id' => $article->id,
            'entity_type' => 'person',
            'entity_id' => 5,
        ]);
    }

    public function test_returns_404_when_article_not_found(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->putJson('/api/articles/99999', [
            'title' => 'Updated Title',
            'content' => 'Some content.',
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 404]);
    }
}
