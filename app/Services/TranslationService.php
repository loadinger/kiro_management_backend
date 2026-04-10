<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use App\Models\Job;
use App\Models\Keyword;
use App\Models\Language;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TranslationService
{
    public function __construct(
        private readonly LlmTranslationService $llmTranslationService,
    ) {}

    /**
     * Translate all (or limited) records for the given table.
     *
     * For `keywords`: only queries records WHERE name_zh IS NULL (resume support).
     * For `departments`, `jobs`, `languages`: queries all records.
     * For `jobs`: eager-loads department.name and passes it as context to LlmTranslationService.
     *
     * @param  string  $table  One of: departments, jobs, keywords, languages
     * @param  int  $batchSize  Number of records per LLM call
     * @param  int|null  $limit  Cap on total records processed; null means no cap
     * @param  callable|null  $onProgress  Called after each batch with (int $processed, int $total)
     * @return array{success: int, skipped_batches: int}
     */
    public function translateTable(
        string $table,
        int $batchSize = 20,
        ?int $limit = null,
        ?callable $onProgress = null,
    ): array {
        $stats = ['success' => 0, 'skipped_batches' => 0];

        $total = $this->countRecords($table, $limit);

        $processed = 0;
        $afterId = 0;

        while (true) {
            // Respect the $limit cap
            $remaining = $limit !== null ? $limit - $processed : PHP_INT_MAX;
            if ($remaining <= 0) {
                break;
            }

            $currentBatchSize = min($batchSize, $remaining);

            // Use cursor-based pagination (WHERE id > $afterId) so that partially-translated
            // batches don't cause infinite loops or skipped records.
            $records = $this->fetchBatch($table, $afterId, $currentBatchSize);

            if ($records->isEmpty()) {
                break;
            }

            // Build items array for LlmTranslationService
            // languages table uses english_name as the source field instead of name
            // Skip records where the source field is empty
            $items = $records
                ->map(fn ($record) => [
                    'id' => $record->id,
                    'text' => $table === 'languages' ? $record->english_name : $record->name,
                ])
                ->filter(fn ($item) => ! empty($item['text']))
                ->values()
                ->all();

            // Mark empty-source records as translated (with null name_zh) so they are
            // not picked up again on subsequent runs
            $emptyIds = $records
                ->filter(fn ($record) => empty($table === 'languages' ? $record->english_name : $record->name))
                ->pluck('id')
                ->all();

            if (! empty($emptyIds)) {
                DB::table($table)->whereIn('id', $emptyIds)->update(['translated_at' => now()]);
            }

            if (empty($items)) {
                $processed += $records->count();
                $afterId = $records->last()->id;
                if ($onProgress !== null) {
                    ($onProgress)($processed, $total);
                }

                continue;
            }

            // Build context for jobs table
            $context = null;
            if ($table === 'jobs') {
                $departmentName = $records->first()?->department?->name ?? '';
                // All jobs in a batch share the same department when chunked by department,
                // but since we do a flat offset query we use the first record's department.
                // The context is a hint; using the first record's department is acceptable.
                $context = "电影制作职位，所属部门：{$departmentName}";
            }

            $translations = $this->llmTranslationService->translateBatch($items, $table, $context);

            if (empty($translations)) {
                $stats['skipped_batches']++;
            } else {
                $this->writeBatch($table, $translations);
                $stats['success'] += count($translations);
            }

            $processed += $records->count();
            // Advance cursor to the last id in this batch
            $afterId = $records->last()->id;

            if ($onProgress !== null) {
                ($onProgress)($processed, $total);
            }

            // If we got fewer records than requested, we've reached the end
            if ($records->count() < $currentBatchSize) {
                break;
            }
        }

        return $stats;
    }

    /**
     * Count the total records that will be processed (respecting resume filter and limit).
     */
    private function countRecords(string $table, ?int $limit): int
    {
        $query = match ($table) {
            'keywords' => Keyword::query()->whereNull('translated_at'),
            'departments' => Department::query()->whereNull('translated_at'),
            'jobs' => Job::query()->whereNull('translated_at'),
            'languages' => Language::query()->whereNull('translated_at'),
            default => throw new \InvalidArgumentException("Unsupported table: {$table}"),
        };

        $count = $query->count();

        return $limit !== null ? min($count, $limit) : $count;
    }

    /**
     * Fetch a batch of untranslated records after the given id (cursor-based pagination).
     * Using WHERE id > $afterId ensures we always advance even when only some records
     * in a batch get translated, avoiding infinite loops and skipped records.
     */
    private function fetchBatch(string $table, int $afterId, int $limit): Collection
    {
        return match ($table) {
            'keywords' => Keyword::query()
                ->whereNull('translated_at')
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit($limit)
                ->get(),

            'departments' => Department::query()
                ->whereNull('translated_at')
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit($limit)
                ->get(),

            'jobs' => Job::query()
                ->whereNull('translated_at')
                ->where('id', '>', $afterId)
                ->with('department')
                ->orderBy('id')
                ->limit($limit)
                ->get(),

            'languages' => Language::query()
                ->whereNull('translated_at')
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit($limit)
                ->get(),

            default => throw new \InvalidArgumentException("Unsupported table: {$table}"),
        };
    }

    /**
     * Write translated results to the database inside a transaction.
     *
     * @param  array<int, array{id: int, translation: string}>  $translatedItems
     */
    private function writeBatch(string $table, array $translatedItems): void
    {
        DB::transaction(function () use ($table, $translatedItems): void {
            foreach ($translatedItems as $item) {
                DB::table($table)->where('id', $item['id'])->update([
                    'name_zh' => $item['translation'],
                    'translated_at' => now(),
                ]);
            }
        });
    }
}
