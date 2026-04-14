<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ArticleSlugService;
use App\Services\LlmTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for ArticleSlugService.
 *
 * Covers Properties 1–5 from the article-slug-generation spec design document.
 * Uses SQLite in-memory via RefreshDatabase for DB-dependent tests.
 * LlmTranslationService is mocked via Mockery.
 */
class ArticleSlugServiceTest extends TestCase
{
    use RefreshDatabase;

    private ArticleSlugService $service;

    /** @var LlmTranslationService&MockInterface */
    private LlmTranslationService $llmMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->llmMock = Mockery::mock(LlmTranslationService::class);
        $this->service = new ArticleSlugService($this->llmMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Insert a minimal article row and return its id.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function insertArticle(array $overrides = []): int
    {
        $defaults = [
            'title' => 'Test Article',
            'slug' => null,
            'content' => 'content',
            'status' => 'draft',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('articles')->insert(array_merge($defaults, $overrides));

        return (int) DB::getPdo()->lastInsertId();
    }

    /**
     * Access a private method via Reflection.
     */
    private function getPrivateMethod(string $name): \ReflectionMethod
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    // =========================================================================
    // Property 1: slug format invariant
    // Validates: Requirements 4.2, 4.3
    // =========================================================================

    #[DataProvider('formatSlugInputProvider')]
    public function test_format_slug_output_satisfies_slug_constraints(string $input): void
    {
        $result = $this->service->formatSlug($input);

        // Only [a-z0-9-] characters
        $this->assertMatchesRegularExpression('/^[a-z0-9-]*$/', $result, "Output '{$result}' contains invalid characters for input '{$input}'");

        if ($result !== '') {
            // Does not start or end with -
            $this->assertStringStartsNotWith('-', $result, "Output '{$result}' starts with hyphen");
            $this->assertStringEndsNotWith('-', $result, "Output '{$result}' ends with hyphen");
        }

        // Does not contain --
        $this->assertStringNotContainsString('--', $result, "Output '{$result}' contains consecutive hyphens");

        // Length ≤ 120
        $this->assertLessThanOrEqual(120, strlen($result), "Output '{$result}' exceeds 120 characters");
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function formatSlugInputProvider(): array
    {
        return [
            'chinese characters' => ['复仇者联盟'],
            'spaces' => ['hello world'],
            'underscores' => ['hello_world'],
            'uppercase letters' => ['Hello World'],
            'mixed content' => ['复仇者 Avengers 2024!'],
            'empty string' => [''],
            'very long string 200+ chars' => [str_repeat('avengers-alliance-', 12)],
            'only special chars' => ['!@#$%^&*()'],
            'consecutive hyphens' => ['hello---world'],
            'leading and trailing hyphens' => ['---hello---'],
            'mixed case with numbers' => ['The Dark Knight 2008'],
            'underscores and spaces mixed' => ['hello_world foo bar'],
            'all non-ascii' => ['中文日本語한국어'],
            'hyphen between words' => ['spider-man'],
            'multiple consecutive spaces' => ['hello   world'],
            'tab character' => ["hello\tworld"],
            'newline character' => ["hello\nworld"],
            'numbers only' => ['12345'],
            'punctuation heavy' => ['Hello, World! How are you?'],
            'exactly 120 ascii chars' => [str_repeat('a', 120)],
            'exactly 121 ascii chars' => [str_repeat('a', 121)],
            'trailing hyphen after truncation' => [str_repeat('a', 119).'-extra'],
        ];
    }

    public function test_format_slug_converts_uppercase_to_lowercase(): void
    {
        $result = $this->service->formatSlug('HELLO WORLD');

        $this->assertSame('hello-world', $result);
    }

    public function test_format_slug_replaces_spaces_with_hyphens(): void
    {
        $result = $this->service->formatSlug('hello world');

        $this->assertSame('hello-world', $result);
    }

    public function test_format_slug_replaces_underscores_with_hyphens(): void
    {
        $result = $this->service->formatSlug('hello_world');

        $this->assertSame('hello-world', $result);
    }

    public function test_format_slug_removes_chinese_characters(): void
    {
        $result = $this->service->formatSlug('复仇者联盟');

        $this->assertSame('', $result);
    }

    public function test_format_slug_collapses_consecutive_hyphens(): void
    {
        $result = $this->service->formatSlug('hello---world');

        $this->assertSame('hello-world', $result);
    }

    public function test_format_slug_trims_leading_and_trailing_hyphens(): void
    {
        $result = $this->service->formatSlug('---hello---');

        $this->assertSame('hello', $result);
    }

    public function test_format_slug_truncates_to_120_characters(): void
    {
        $input = str_repeat('a', 200);
        $result = $this->service->formatSlug($input);

        $this->assertSame(120, strlen($result));
    }

    public function test_format_slug_truncates_and_removes_trailing_hyphen(): void
    {
        // 119 'a' chars + '-' + 'extra' — truncation at 120 lands on the hyphen
        $input = str_repeat('a', 119).'-extra';
        $result = $this->service->formatSlug($input);

        $this->assertLessThanOrEqual(120, strlen($result));
        $this->assertStringEndsNotWith('-', $result);
    }

    public function test_format_slug_returns_empty_string_for_all_non_ascii(): void
    {
        $result = $this->service->formatSlug('中文日本語한국어');

        $this->assertSame('', $result);
    }

    // =========================================================================
    // Property 2: empty title filtering
    // Validates: Requirement 6.3
    // =========================================================================

    public function test_empty_title_articles_not_sent_to_llm(): void
    {
        // Insert articles: one with empty title, one with a real title
        $this->insertArticle(['title' => '']);
        $validId = $this->insertArticle(['title' => 'Valid Title']);

        // The LLM should only be called with the non-empty title item
        $capturedItems = null;
        $this->llmMock
            ->shouldReceive('translateBatch')
            ->once()
            ->withArgs(function (array $items) use (&$capturedItems): bool {
                $capturedItems = $items;

                return true;
            })
            ->andReturn([['id' => $validId, 'translation' => 'valid-title']]);

        $this->service->generateSlugs();

        // Verify the captured items contain no empty-title entries
        $this->assertNotNull($capturedItems);
        $this->assertCount(1, $capturedItems);
        $this->assertSame('Valid Title', $capturedItems[0]['text']);
    }

    // =========================================================================
    // Property 3: unique slug resolution
    // Validates: Requirement 5.2
    // =========================================================================

    public function test_resolve_unique_slug_returns_base_slug_when_not_taken(): void
    {
        $id = $this->insertArticle(['title' => 'Test', 'slug' => null]);

        $method = $this->getPrivateMethod('resolveUniqueSlug');
        $result = $method->invoke($this->service, 'my-slug', $id);

        $this->assertSame('my-slug', $result);
    }

    public function test_resolve_unique_slug_appends_suffix_when_base_is_taken(): void
    {
        // Another article already has the base slug
        $this->insertArticle(['title' => 'Other', 'slug' => 'my-slug']);
        $id = $this->insertArticle(['title' => 'Test', 'slug' => null]);

        $method = $this->getPrivateMethod('resolveUniqueSlug');
        $result = $method->invoke($this->service, 'my-slug', $id);

        $this->assertSame('my-slug-2', $result);
    }

    public function test_resolve_unique_slug_finds_minimum_available_suffix(): void
    {
        // Occupy base slug and -2
        $this->insertArticle(['title' => 'A', 'slug' => 'my-slug']);
        $this->insertArticle(['title' => 'B', 'slug' => 'my-slug-2']);
        $id = $this->insertArticle(['title' => 'Test', 'slug' => null]);

        $method = $this->getPrivateMethod('resolveUniqueSlug');
        $result = $method->invoke($this->service, 'my-slug', $id);

        $this->assertSame('my-slug-3', $result);
    }

    public function test_resolve_unique_slug_returns_null_when_all_suffixes_taken(): void
    {
        // Occupy base slug and all suffixes -2 through -99
        $this->insertArticle(['title' => 'Base', 'slug' => 'my-slug']);
        for ($i = 2; $i <= 99; $i++) {
            $this->insertArticle(['title' => "Suffix {$i}", 'slug' => "my-slug-{$i}"]);
        }

        $id = $this->insertArticle(['title' => 'Test', 'slug' => null]);

        $method = $this->getPrivateMethod('resolveUniqueSlug');
        $result = $method->invoke($this->service, 'my-slug', $id);

        $this->assertNull($result);
    }

    // =========================================================================
    // Property 4: write correctness
    // Validates: Requirements 5.4, 5.5
    // =========================================================================

    public function test_write_batch_only_updates_slug_field(): void
    {
        $id = $this->insertArticle([
            'title' => 'Original Title',
            'status' => 'published',
            'sort_order' => 5,
            'content' => 'original content',
        ]);

        // Capture the row before write
        $before = DB::table('articles')->where('id', $id)->first();

        $method = $this->getPrivateMethod('writeBatch');
        $method->invoke($this->service, [$id => 'new-slug']);

        $after = DB::table('articles')->where('id', $id)->first();

        // Slug was updated
        $this->assertSame('new-slug', $after->slug);

        // All other fields remain unchanged
        $this->assertSame($before->title, $after->title);
        $this->assertSame($before->status, $after->status);
        $this->assertSame($before->sort_order, $after->sort_order);
        $this->assertSame($before->content, $after->content);
    }

    // =========================================================================
    // Property 5: limit constraint
    // Validates: Requirement 3.3
    // =========================================================================

    public function test_generate_slugs_respects_limit(): void
    {
        $limit = 3;

        // Insert 5 articles without slugs
        for ($i = 1; $i <= 5; $i++) {
            $this->insertArticle(['title' => "Article {$i}"]);
        }

        // LLM returns a unique slug for each item
        $words = ['alpha', 'bravo', 'charlie', 'delta', 'echo'];
        $this->llmMock
            ->shouldReceive('translateBatch')
            ->andReturnUsing(function (array $items) use (&$words): array {
                $results = [];
                foreach ($items as $item) {
                    $results[] = [
                        'id' => $item['id'],
                        'translation' => array_shift($words) ?? 'fallback',
                    ];
                }

                return $results;
            });

        $stats = $this->service->generateSlugs(batchSize: 10, limit: $limit);

        // Total processed must not exceed limit
        $this->assertLessThanOrEqual($limit, $stats['success']);

        // Verify at most $limit articles received a slug
        $sluggedCount = DB::table('articles')->whereNotNull('slug')->count();
        $this->assertLessThanOrEqual($limit, $sluggedCount);
    }

    // =========================================================================
    // Additional behaviour tests
    // =========================================================================

    public function test_skipped_batches_incremented_when_llm_returns_empty(): void
    {
        $this->insertArticle(['title' => 'Some Article']);

        $this->llmMock
            ->shouldReceive('translateBatch')
            ->once()
            ->andReturn([]);

        $stats = $this->service->generateSlugs();

        $this->assertSame(1, $stats['skipped_batches']);
        $this->assertSame(0, $stats['success']);
    }

    public function test_empty_formatted_slug_is_skipped(): void
    {
        $id = $this->insertArticle(['title' => '纯中文标题']);

        // LLM returns a translation that formats to empty string (all Chinese)
        $this->llmMock
            ->shouldReceive('translateBatch')
            ->once()
            ->andReturn([['id' => $id, 'translation' => '纯中文']]);

        $stats = $this->service->generateSlugs();

        $this->assertSame(0, $stats['success']);

        // The article slug should remain null
        $slug = DB::table('articles')->where('id', $id)->value('slug');
        $this->assertNull($slug);
    }
}
