<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\ArticleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for the Articles module.
 *
 * Each test method loops 100 times over randomly generated inputs to verify
 * that core invariants hold for all valid inputs.
 *
 * Uses RefreshDatabase + SQLite in-memory; no mocks needed.
 */
class ArticlePropertyTest extends TestCase
{
    use RefreshDatabase;

    /** Valid entity types supported by ArticleEntityType enum. */
    private const ENTITY_TYPES = [
        'movie', 'collection', 'tv_show', 'tv_season', 'tv_episode',
        'person', 'production_company', 'tv_network', 'genre', 'keyword',
    ];

    /** Valid article statuses. */
    private const STATUSES = ['draft', 'published', 'archived'];

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a content string containing the given (entity_type, entity_id) pairs
     * as ::media placeholders.
     *
     * @param  array<int, array{entity_type: string, entity_id: int}>  $items
     */
    private function buildContent(array $items): string
    {
        $parts = ['Some article text.'];
        foreach ($items as $item) {
            $parts[] = sprintf('::media{type="%s" id="%d"}', $item['entity_type'], $item['entity_id']);
        }

        return implode(' ', $parts);
    }

    /**
     * Generate a random set of unique (entity_type, entity_id) pairs.
     *
     * @return array<int, array{entity_type: string, entity_id: int}>
     */
    private function randomItems(int $count): array
    {
        $seen = [];
        $items = [];
        $attempts = 0;

        while (count($items) < $count && $attempts < 200) {
            $attempts++;
            $type = self::ENTITY_TYPES[array_rand(self::ENTITY_TYPES)];
            $id = random_int(1, 1000);
            $key = $type . ':' . $id;

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $items[] = ['entity_type' => $type, 'entity_id' => $id];
            }
        }

        return $items;
    }

    /**
     * Create an article via the API and return the response.
     */
    private function createArticle(string $token, array $data): \Illuminate\Testing\TestResponse
    {
        return $this->withToken($token)->postJson('/api/articles', $data);
    }

    // -------------------------------------------------------------------------
    // Property 1: Placeholder sync consistency
    // Validates: Requirements 3.1, 3.2
    // -------------------------------------------------------------------------

    /**
     * Property 1: article_items match parsed placeholders.
     *
     * For any content with valid placeholders, after saving the article,
     * the article_items set must exactly match the parsed placeholder set.
     *
     * Validates: Requirements 3.1, 3.2
     */
    public function test_article_items_match_parsed_placeholders(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        for ($i = 0; $i < 100; $i++) {
            $count = random_int(0, 5);
            $items = $this->randomItems($count);
            $content = $this->buildContent($items);

            $response = $this->createArticle($token, [
                'title' => "Property Test Article $i",
                'content' => $content,
            ]);

            $this->assertSame(0, $response->json('code'),
                "Iteration $i: article creation failed");

            $articleId = $response->json('data.id');

            $dbItems = ArticleItem::where('article_id', $articleId)
                ->get()
                ->map(fn ($item) => $item->entity_type->value . ':' . $item->entity_id)
                ->sort()
                ->values()
                ->toArray();

            $expectedItems = collect($items)
                ->map(fn ($item) => $item['entity_type'] . ':' . $item['entity_id'])
                ->sort()
                ->values()
                ->toArray();

            $this->assertSame($expectedItems, $dbItems,
                "Iteration $i: article_items do not match parsed placeholders (count=$count)");

            // Clean up for next iteration
            Article::find($articleId)?->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Property 2: Sync idempotency
    // Validates: Requirements 3.2, 3.8
    // -------------------------------------------------------------------------

    /**
     * Property 2: article_items are idempotent on same content update.
     *
     * Updating an article with the same content must not change article_items.
     *
     * Validates: Requirements 3.2, 3.8
     */
    public function test_article_items_are_idempotent_on_same_content_update(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        for ($i = 0; $i < 100; $i++) {
            $count = random_int(0, 5);
            $items = $this->randomItems($count);
            $content = $this->buildContent($items);

            // Create article
            $createResponse = $this->createArticle($token, [
                'title' => "Idempotency Test $i",
                'content' => $content,
            ]);

            $this->assertSame(0, $createResponse->json('code'),
                "Iteration $i: article creation failed");

            $articleId = $createResponse->json('data.id');

            // Record initial article_items
            $before = ArticleItem::where('article_id', $articleId)
                ->get()
                ->map(fn ($item) => $item->entity_type->value . ':' . $item->entity_id)
                ->sort()
                ->values()
                ->toArray();

            // Update with same content
            $this->withToken($token)->putJson("/api/articles/{$articleId}", [
                'content' => $content,
            ]);

            // article_items should be unchanged
            $after = ArticleItem::where('article_id', $articleId)
                ->get()
                ->map(fn ($item) => $item->entity_type->value . ':' . $item->entity_id)
                ->sort()
                ->values()
                ->toArray();

            $this->assertSame($before, $after,
                "Iteration $i: article_items changed after same-content update");

            Article::find($articleId)?->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Property 3: Status filter consistency
    // Validates: Requirement 5.1
    // -------------------------------------------------------------------------

    /**
     * Property 3: status filter returns only matching articles.
     *
     * For any status filter value, all returned articles must have that status.
     *
     * Validates: Requirement 5.1
     */
    public function test_status_filter_returns_only_matching_articles(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        for ($i = 0; $i < 100; $i++) {
            // Create a mix of articles with different statuses
            $total = random_int(1, 10);
            $slugCounter = $i * 100;

            for ($j = 0; $j < $total; $j++) {
                $status = self::STATUSES[array_rand(self::STATUSES)];
                $slug = ($status === 'published') ? 'prop3-slug-' . ($slugCounter++) : null;
                Article::factory()->create(['status' => $status, 'slug' => $slug]);
            }

            // Pick a random status to filter by
            $filterStatus = self::STATUSES[array_rand(self::STATUSES)];

            $response = $this->withToken($token)->getJson("/api/articles?status={$filterStatus}&per_page=100");

            $this->assertSame(0, $response->json('code'),
                "Iteration $i: list request failed");

            $list = $response->json('data.list');
            foreach ($list as $item) {
                $this->assertSame($filterStatus, $item['status'],
                    "Iteration $i: found article with status={$item['status']} when filtering by $filterStatus");
            }

            // Clean up
            Article::query()->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Property 4: Sort order correctness
    // Validates: Requirement 5.2
    // -------------------------------------------------------------------------

    /**
     * Property 4: sort_order ascending is monotonically non-decreasing.
     *
     * For any set of articles, sorting by sort_order asc must produce
     * a non-decreasing sequence.
     *
     * Validates: Requirement 5.2
     */
    public function test_sort_order_ascending_is_monotonically_non_decreasing(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        for ($i = 0; $i < 100; $i++) {
            $count = random_int(1, 10);

            for ($j = 0; $j < $count; $j++) {
                Article::factory()->create(['sort_order' => random_int(0, 1000)]);
            }

            $response = $this->withToken($token)->getJson('/api/articles?sort=sort_order&order=asc&per_page=100');

            $this->assertSame(0, $response->json('code'),
                "Iteration $i: list request failed");

            $list = $response->json('data.list');
            $sortOrders = array_column($list, 'sort_order');

            for ($k = 1; $k < count($sortOrders); $k++) {
                $this->assertGreaterThanOrEqual(
                    $sortOrders[$k - 1],
                    $sortOrders[$k],
                    "Iteration $i: sort_order not monotonically non-decreasing at index $k"
                );
            }

            Article::query()->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Property 5: Reverse query consistency
    // Validates: Requirement 6.1
    // -------------------------------------------------------------------------

    /**
     * Property 5: article_items reverse query returns only matching articles.
     *
     * For any (entity_type, entity_id) query, all returned records must
     * reference that entity.
     *
     * Validates: Requirement 6.1
     */
    public function test_article_items_reverse_query_returns_only_matching_articles(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        for ($i = 0; $i < 100; $i++) {
            // Pick a target entity to query
            $targetType = self::ENTITY_TYPES[array_rand(self::ENTITY_TYPES)];
            $targetId = random_int(1, 100);

            // Create some articles that reference the target entity
            $matchCount = random_int(0, 3);
            for ($j = 0; $j < $matchCount; $j++) {
                $article = Article::factory()->create([
                    'content' => sprintf('::media{type="%s" id="%d"}', $targetType, $targetId),
                ]);
                ArticleItem::create([
                    'article_id' => $article->id,
                    'entity_type' => $targetType,
                    'entity_id' => $targetId,
                ]);
            }

            // Create some articles that reference a different entity
            $otherCount = random_int(0, 3);
            for ($j = 0; $j < $otherCount; $j++) {
                // Use a different entity_id to avoid collision
                $otherId = $targetId + 1000 + $j;
                $article = Article::factory()->create([
                    'content' => sprintf('::media{type="%s" id="%d"}', $targetType, $otherId),
                ]);
                ArticleItem::create([
                    'article_id' => $article->id,
                    'entity_type' => $targetType,
                    'entity_id' => $otherId,
                ]);
            }

            $response = $this->withToken($token)->getJson(
                "/api/article-items?entity_type={$targetType}&entity_id={$targetId}&per_page=100"
            );

            $this->assertSame(0, $response->json('code'),
                "Iteration $i: article-items request failed");

            $list = $response->json('data.list');

            // All returned items must reference the target entity
            foreach ($list as $item) {
                $this->assertTrue(
                    ArticleItem::where('article_id', $item['article_id'])
                        ->where('entity_type', $targetType)
                        ->where('entity_id', $targetId)
                        ->exists(),
                    "Iteration $i: returned item (article_id={$item['article_id']}) does not reference target entity $targetType:$targetId"
                );
            }

            // Count must match
            $this->assertCount($matchCount, $list,
                "Iteration $i: expected $matchCount items for entity $targetType:$targetId");

            // Clean up
            ArticleItem::query()->delete();
            Article::query()->delete();
        }
    }
}
