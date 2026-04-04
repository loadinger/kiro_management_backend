<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TranslationService;
use Illuminate\Console\Command;

class TranslateNamesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translate:names
        {--table=all : Target table: departments|jobs|keywords|languages|all}
        {--batch-size=20 : Number of records per LLM call}
        {--limit= : Maximum number of records to process; omit to process all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translate name fields to Chinese via Ollama LLM';

    private const ALLOWED_TABLES = ['departments', 'jobs', 'keywords', 'languages', 'all'];

    private const ALL_TABLES = ['departments', 'jobs', 'keywords', 'languages'];

    public function __construct(private readonly TranslationService $translationService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * Validates --table option, then dispatches translation for one or all tables,
     * showing a progress bar per table and printing aggregate stats at the end.
     */
    public function handle(): int
    {
        $table = (string) $this->option('table');
        $batchSize = (int) $this->option('batch-size');
        $limitRaw = $this->option('limit');
        $limit = $limitRaw !== null ? (int) $limitRaw : null;

        if (! in_array($table, self::ALLOWED_TABLES, true)) {
            $this->error("Invalid --table value: \"{$table}\". Allowed: departments, jobs, keywords, languages, all.");

            return self::FAILURE;
        }

        $tables = $table === 'all' ? self::ALL_TABLES : [$table];

        $totalSuccess = 0;
        $totalSkipped = 0;

        foreach ($tables as $currentTable) {
            $this->line("Translating <info>{$currentTable}</info>...");

            $bar = null;

            $onProgress = function (int $processed, int $total) use (&$bar): void {
                if ($bar === null) {
                    $bar = $this->output->createProgressBar($total);
                    $bar->start();
                }
                $bar->setProgress($processed);
            };

            $stats = $this->translationService->translateTable(
                $currentTable,
                $batchSize,
                $limit,
                $onProgress,
            );

            if ($bar !== null) {
                $bar->finish();
                $this->newLine();
            }

            $totalSuccess += $stats['success'];
            $totalSkipped += $stats['skipped_batches'];
        }

        $this->line('Translation complete.');
        $this->line("  Success: {$totalSuccess}");
        $this->line("  Skipped batches: {$totalSkipped}");

        return self::SUCCESS;
    }
}
