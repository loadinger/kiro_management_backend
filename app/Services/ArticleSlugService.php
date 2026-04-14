<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ArticleSlugService
{
    public function __construct(
        private readonly LlmTranslationService $llmTranslationService,
    ) {}

    /**
     * Generate slugs for articles where slug IS NULL.
     *
     * Iterates through pending articles using cursor pagination, calls the LLM
     * to translate titles, formats the results, resolves uniqueness conflicts,
     * and writes back to the database in transactions.
     *
     * @return array{success: int, skipped_batches: int}
     */
    public function generateSlugs(
        int $batchSize = 10,
        ?int $limit = null,
        ?callable $onProgress = null,
    ): array {
        $stats = ['success' => 0, 'skipped_batches' => 0];
        $afterId = 0;
        $processed = 0;

        while (true) {
            // Respect limit: fetch only as many as still needed
            $fetchSize = $limit !== null
                ? min($batchSize, $limit - $processed)
                : $batchSize;

            if ($fetchSize <= 0) {
                break;
            }

            $batch = $this->fetchBatch($afterId, $fetchSize);

            if ($batch->isEmpty()) {
                break;
            }

            // Advance cursor to last id in this batch
            $afterId = $batch->last()->id;

            // Filter out records with empty/null titles (requirement 6.3)
            $validItems = $batch
                ->filter(fn (object $row): bool => isset($row->title) && $row->title !== '')
                ->map(fn (object $row): array => ['id' => $row->id, 'text' => $row->title])
                ->values()
                ->all();

            if (empty($validItems)) {
                $processed += $batch->count();
                if ($onProgress !== null) {
                    ($onProgress)($processed);
                }

                continue;
            }

            // Call LLM for translations (requirement 4.5)
            $translations = $this->llmTranslationService->translateBatch($validItems, 'articles', null);

            // Empty result means the whole batch failed (requirement 5.3)
            if (empty($translations)) {
                $stats['skipped_batches']++;
                $processed += $batch->count();
                if ($onProgress !== null) {
                    ($onProgress)($processed);
                }

                continue;
            }

            // Build slug map: id => slug, skipping empty formatted slugs
            $slugMap = [];
            foreach ($translations as $entry) {
                $formatted = $this->formatSlug($entry['translation']);

                // Skip records where formatting yields empty string (requirement 4.4)
                if ($formatted === '') {
                    continue;
                }

                $resolved = $this->resolveUniqueSlug($formatted, $entry['id']);

                // Skip if all suffixes are taken (requirement 5.2)
                if ($resolved === null) {
                    continue;
                }

                $slugMap[$entry['id']] = $resolved;
            }

            if (! empty($slugMap)) {
                $this->writeBatch($slugMap);
                $stats['success'] += count($slugMap);
            }

            $processed += $batch->count();

            if ($onProgress !== null) {
                ($onProgress)($processed);
            }
        }

        return $stats;
    }

    /**
     * Format a raw LLM translation into a valid URL slug.
     *
     * Steps in order:
     *   1. Lowercase (mb_strtolower)
     *   2. Replace spaces and underscores with hyphens
     *   3. Remove all characters that are not ASCII lowercase letters or hyphens
     *   4. Collapse consecutive hyphens into one
     *   5. Trim leading and trailing hyphens
     *   6. Truncate to 120 characters, then trim trailing hyphens again
     */
    public function formatSlug(string $raw): string
    {
        $slug = mb_strtolower($raw);
        $slug = str_replace([' ', '_'], '-', $slug);
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug) ?? '';
        $slug = preg_replace('/-+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        if (strlen($slug) > 120) {
            $slug = substr($slug, 0, 120);
            $slug = rtrim($slug, '-');
        }

        return $slug;
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

    /**
     * Fetch a batch of articles with slug IS NULL using cursor pagination.
     *
     * @return Collection<int, object>
     */
    private function fetchBatch(int $afterId, int $batchSize): Collection
    {
        return DB::table('articles')
            ->select(['id', 'title'])
            ->whereNull('slug')
            ->where('id', '>', $afterId)
            ->orderBy('id', 'asc')
            ->limit($batchSize)
            ->get();
    }

    /**
     * Resolve a unique slug for the given article, excluding the article itself.
     *
     * Tries the base slug first, then appends -2 through -99.
     * Returns null if all candidates are taken.
     */
    private function resolveUniqueSlug(string $slug, int $excludeId): ?string
    {
        $isTaken = DB::table('articles')
            ->where('slug', $slug)
            ->where('id', '!=', $excludeId)
            ->exists();

        if (! $isTaken) {
            return $slug;
        }

        for ($suffix = 2; $suffix <= 99; $suffix++) {
            $candidate = "{$slug}-{$suffix}";

            $isTaken = DB::table('articles')
                ->where('slug', $candidate)
                ->where('id', '!=', $excludeId)
                ->exists();

            if (! $isTaken) {
                return $candidate;
            }
        }

        // All suffixes 2-99 are taken; skip this record
        return null;
    }

    /**
     * Write a batch of slug updates inside a database transaction.
     *
     * Only the slug field is updated; all other article fields are untouched.
     * On failure the transaction is rolled back and the exception is re-thrown.
     *
     * @param  array<int, string>  $slugMap  Map of article id => slug
     */
    private function writeBatch(array $slugMap): void
    {
        DB::transaction(function () use ($slugMap): void {
            foreach ($slugMap as $id => $slug) {
                DB::table('articles')
                    ->where('id', $id)
                    ->update(['slug' => $slug]);
            }
        });
    }
}
