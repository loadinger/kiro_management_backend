<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ArticleSlugService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;

class GenerateSlugsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:generate-slugs
        {--batch-size=10 : Number of articles per LLM call}
        {--limit= : Maximum number of articles to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate slugs for articles with missing slug via Ollama LLM';

    public function __construct(private readonly ArticleSlugService $articleSlugService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * Validates options, counts pending articles, shows a progress bar,
     * delegates to ArticleSlugService, and prints final stats.
     */
    public function handle(): int
    {
        $batchSize = (int) $this->option('batch-size');
        $limitRaw = $this->option('limit');
        $limit = $limitRaw !== null ? (int) $limitRaw : null;

        if ($batchSize < 1) {
            $this->error('--batch-size must be at least 1.');

            return self::FAILURE;
        }

        if ($limit !== null && $limit < 1) {
            $this->error('--limit must be at least 1.');

            return self::FAILURE;
        }

        try {
            $pendingCount = $this->countPending($limit);

            if ($pendingCount === 0) {
                $this->info('No articles pending slug generation.');

                return self::SUCCESS;
            }

            $bar = $this->output->createProgressBar($pendingCount);
            $bar->start();

            $onProgress = function (int $processed) use ($bar): void {
                $bar->setProgress($processed);
            };

            $stats = $this->articleSlugService->generateSlugs($batchSize, $limit, $onProgress);

            $bar->finish();
            $this->newLine();

            $this->line("Done. Success: {$stats['success']}, Skipped batches: {$stats['skipped_batches']}");
        } catch (ConnectionException $e) {
            $this->error('Failed to connect to Ollama: '.$e->getMessage());

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('An error occurred: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Count articles with slug IS NULL, optionally capped by limit.
     */
    private function countPending(?int $limit): int
    {
        $count = DB::table('articles')
            ->whereNull('slug')
            ->count();

        if ($limit !== null) {
            return min($count, $limit);
        }

        return $count;
    }
}
