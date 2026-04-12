<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\ArticleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->postJson('/api/articles', []);

        $response->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_creates_article_successfully(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson('/api/articles', [
            'title' => 'Test Article',
            'content' => 'Some content here.',
            'status' => 'draft',
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('articles', ['title' => 'Test Article']);
    }

    public function test_title_is_required(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson('/api/articles', [
            'content' => 'Some content.',
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_slug_format_validation(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson('/api/articles', [
            'title' => 'Test Article',
            'content' => 'Some content.',
            'slug' => 'Invalid-Slug-With-Uppercase',
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_duplicate_slug_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        Article::factory()->create(['slug' => 'my-unique-slug']);

        $response = $this->withToken($token)->postJson('/api/articles', [
            'title' => 'Another Article',
            'content' => 'Some content.',
            'slug' => 'my-unique-slug',
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_published_without_slug_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson('/api/articles', [
            'title' => 'Published Article',
            'content' => 'Some content.',
            'status' => 'published',
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_placeholders_are_written_to_article_items(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $content = 'Some text ::media{type="movie" id="1"} more text.';

        $response = $this->withToken($token)->postJson('/api/articles', [
            'title' => 'Article With Placeholder',
            'content' => $content,
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $articleId = $response->json('data.id');

        $this->assertDatabaseHas('article_items', [
            'article_id' => $articleId,
            'entity_type' => 'movie',
            'entity_id' => 1,
        ]);
    }
}
