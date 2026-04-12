<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\ArticleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleItemListTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/article-items');

        $response->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_returns_article_items_for_entity(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $article = Article::factory()->create([
            'content' => '::media{type="movie" id="1"}',
        ]);
        ArticleItem::create([
            'article_id' => $article->id,
            'entity_type' => 'movie',
            'entity_id' => 1,
        ]);

        $response = $this->withToken($token)->getJson('/api/article-items?entity_type=movie&entity_id=1');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $list = $response->json('data.list');
        $this->assertNotEmpty($list);

        foreach ($list as $item) {
            $this->assertSame($article->id, $item['article_id']);
        }
    }

    public function test_entity_type_is_required(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/article-items?entity_id=1');

        $response->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_entity_id_is_required(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/article-items?entity_type=movie');

        $response->assertStatus(200)
            ->assertJson(['code' => 422]);
    }
}
