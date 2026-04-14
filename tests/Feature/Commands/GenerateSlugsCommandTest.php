<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Services\ArticleSlugService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class GenerateSlugsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that --batch-size=0 returns FAILURE with an error message.
     *
     * Validates: Requirement 1.4
     */
    public function test_batch_size_zero_returns_failure(): void
    {
        $this->artisan('articles:generate-slugs', ['--batch-size' => '0'])
            ->assertFailed()
            ->expectsOutput('--batch-size must be at least 1.');
    }

    /**
     * Test that --limit=-1 returns FAILURE with an error message.
     *
     * Validates: Requirement 1.5
     */
    public function test_negative_limit_returns_failure(): void
    {
        $this->artisan('articles:generate-slugs', ['--limit' => '-1'])
            ->assertFailed()
            ->expectsOutput('--limit must be at least 1.');
    }

    /**
     * Test that when no articles are pending, the command outputs an info message and returns SUCCESS.
     *
     * Validates: Requirement 2.4
     */
    public function test_no_pending_articles_returns_success_with_message(): void
    {
        $this->artisan('articles:generate-slugs')
            ->assertSuccessful()
            ->expectsOutput('No articles pending slug generation.');
    }

    /**
     * Test that the normal flow outputs correct stats (success / skipped_batches).
     *
     * Validates: Requirements 2.1, 2.2, 2.3
     */
    public function test_normal_flow_outputs_stats(): void
    {
        DB::table('articles')->insert([
            ['title' => 'Article One', 'slug' => null, 'content' => 'Content one.', 'status' => 'draft', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Article Two', 'slug' => null, 'content' => 'Content two.', 'status' => 'draft', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->mock(ArticleSlugService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generateSlugs')
                ->once()
                ->andReturn(['success' => 2, 'skipped_batches' => 1]);
        });

        $this->artisan('articles:generate-slugs')
            ->assertSuccessful()
            ->expectsOutput('Done. Success: 2, Skipped batches: 1');
    }

    /**
     * Test that a ConnectionException causes the command to return FAILURE with an error message.
     *
     * Validates: Requirement 6.1
     */
    public function test_connection_exception_returns_failure(): void
    {
        DB::table('articles')->insert([
            ['title' => 'Article One', 'slug' => null, 'content' => 'Content one.', 'status' => 'draft', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->mock(ArticleSlugService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generateSlugs')
                ->once()
                ->andThrow(new ConnectionException('Connection refused'));
        });

        $this->artisan('articles:generate-slugs')
            ->assertFailed()
            ->expectsOutput('Failed to connect to Ollama: Connection refused');
    }

    /**
     * Test that partial batch failures are reflected correctly in the skipped_batches count.
     *
     * Validates: Requirements 5.3, 6.2
     */
    public function test_partial_batch_failure_shows_correct_skipped_count(): void
    {
        DB::table('articles')->insert([
            ['title' => 'Article One', 'slug' => null, 'content' => 'Content one.', 'status' => 'draft', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Article Two', 'slug' => null, 'content' => 'Content two.', 'status' => 'draft', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Article Three', 'slug' => null, 'content' => 'Content three.', 'status' => 'draft', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->mock(ArticleSlugService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generateSlugs')
                ->once()
                ->andReturn(['success' => 1, 'skipped_batches' => 2]);
        });

        $this->artisan('articles:generate-slugs')
            ->assertSuccessful()
            ->expectsOutput('Done. Success: 1, Skipped batches: 2');
    }
}
