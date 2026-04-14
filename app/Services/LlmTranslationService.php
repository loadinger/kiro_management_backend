<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LlmTranslationService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
你是一个专业的影视行业术语翻译助手，专门翻译 TMDB 数据库中的英文词条为中文。

规则：
1. 译文必须是词或短语，不能是完整句子
2. keywords/departments/languages 的译文不超过 8 个汉字
3. jobs 的译文不超过 12 个汉字
4. 保持影视行业专业性，使用业内通用译法
5. 必须翻译输入中的每一条，不能遗漏

输入格式：{"task":"translate_to_chinese","items":[{"id":1,"text":"Visual Effects"},{"id":2,"text":"Directing"}]}

输出格式（严格按此 JSON 数组，字段名必须是 id 和 translation，必须包含所有输入条目）：
[{"id":1,"translation":"视效"},{"id":2,"translation":"导演"}]

错误示例（禁止）：
✗ 只返回部分条目
✗ {"items":[{"id":1,"text":"视效"}]}
✗ [{"id":1,"text":"视效"}]
✓ [{"id":1,"translation":"视效"},{"id":2,"translation":"导演"}]
PROMPT;

    private readonly string $systemPrompt;

    public function __construct(?string $systemPrompt = null)
    {
        $this->systemPrompt = $systemPrompt ?? self::SYSTEM_PROMPT;
    }

    /**
     * Translate a batch of items via Ollama.
     *
     * Internally retries with smaller sub-batches on JSON parse failure:
     *   Attempt 1: full batch as passed in
     *   Attempt 2: sub-batches of 5 items
     *   Attempt 3: sub-batches of 1 item
     *
     * @param  array<int, array{id: int, text: string}>  $items
     * @param  string  $tableType  One of: departments, jobs, keywords, languages
     * @param  string|null  $context  For jobs table: "电影制作职位，所属部门：<department_name>"
     * @return array<int, array{id: int, translation: string}>
     *
     * @throws ConnectionException When Ollama is unreachable
     */
    public function translateBatch(array $items, string $tableType, ?string $context = null): array
    {
        // Attempt 1: full batch
        $result = $this->attemptTranslate($items, $context);
        if ($result !== null) {
            return $result;
        }

        // Attempt 2: sub-batches of 5
        $result = $this->attemptInSubBatches($items, $context, 5);
        if ($result !== null) {
            return $result;
        }

        // Attempt 3: sub-batches of 1
        $result = $this->attemptInSubBatches($items, $context, 1);
        if ($result !== null) {
            return $result;
        }

        // All retries exhausted — log and return empty
        Log::warning('LlmTranslationService: all retries failed, skipping batch', [
            'item_count' => count($items),
        ]);

        return [];
    }

    /**
     * Attempt to translate a single batch of items.
     * Returns mapped results on success, null on parse failure.
     *
     * @param  array<int, array{id: int, text: string}>  $items
     * @return array<int, array{id: int, translation: string}>|null
     *
     * @throws ConnectionException
     */
    private function attemptTranslate(array $items, ?string $context): ?array
    {
        $baseUrl = config('services.ollama.base_url');
        $model = config('services.ollama.model');

        $userMessage = $this->buildUserMessage($items, $context);

        Log::debug('Ollama request', [
            'model' => $model,
            'user_message' => $userMessage,
        ]);

        try {
            $response = Http::timeout(config('services.ollama.timeout', 120))->post("{$baseUrl}/api/chat", [
                'model' => $model,
                'format' => 'json',
                'stream' => false,
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);
        } catch (ConnectionException $e) {
            Log::error('Ollama service unreachable', [
                'base_url' => $baseUrl,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $raw = $response->json('message.content') ?? '';

        Log::debug('Ollama raw response', [
            'status' => $response->status(),
            'body' => mb_substr($response->body(), 0, 1000),
        ]);

        $extracted = $this->extractJsonBlock((string) $raw);
        if ($extracted === null) {
            Log::warning('LlmTranslationService: JSON parse failed', [
                'raw_response' => mb_substr((string) $raw, 0, 500),
            ]);

            return null;
        }

        $decoded = json_decode($extracted, true);
        if (! is_array($decoded)) {
            Log::warning('LlmTranslationService: decoded value is not an array', [
                'raw_response' => mb_substr((string) $raw, 0, 500),
            ]);

            return null;
        }

        $mapped = $this->mapById($decoded);

        // If json_decode lost entries due to duplicate keys (flat object format from small models),
        // fall back to regex extraction directly from the raw string.
        if (count($mapped) < count($items)) {
            $fallback = $this->extractByRegex((string) $raw);
            if (count($fallback) > count($mapped)) {
                Log::debug('LlmTranslationService: using regex fallback due to duplicate-key object format', [
                    'json_decoded_count' => count($mapped),
                    'regex_count' => count($fallback),
                ]);

                return $fallback;
            }
        }

        return $mapped;
    }

    /**
     * Split items into sub-batches of $size and attempt each sub-batch.
     * Merges all successful results; returns null only if every sub-batch fails.
     *
     * @param  array<int, array{id: int, text: string}>  $items
     * @return array<int, array{id: int, translation: string}>|null
     *
     * @throws ConnectionException
     */
    private function attemptInSubBatches(array $items, ?string $context, int $size): ?array
    {
        $chunks = array_chunk($items, $size);
        $merged = [];
        $anyFail = false;

        foreach ($chunks as $chunk) {
            $result = $this->attemptTranslate($chunk, $context);
            if ($result === null) {
                $anyFail = true;
            } else {
                foreach ($result as $entry) {
                    $merged[] = $entry;
                }
            }
        }

        // Return null only when every chunk failed (nothing was collected)
        if ($anyFail && count($merged) === 0) {
            return null;
        }

        return $merged;
    }

    /**
     * Extract the first JSON array `[...]` or object `{...}` block from a string.
     * Handles markdown code fences, prefixed text, and other LLM output pollution.
     *
     * Returns the raw JSON string on success, or null if nothing is found.
     */
    private function extractJsonBlock(string $text): ?string
    {
        // Try to find a [...] or {...} block (non-greedy won't work for nested,
        // so we use a simple bracket-depth scan after locating the opening char).
        foreach ([['[', ']'], ['{', '}']] as [$open, $close]) {
            $start = strpos($text, $open);
            if ($start === false) {
                continue;
            }

            $depth = 0;
            $length = strlen($text);

            for ($i = $start; $i < $length; $i++) {
                if ($text[$i] === $open) {
                    $depth++;
                } elseif ($text[$i] === $close) {
                    $depth--;
                    if ($depth === 0) {
                        $candidate = substr($text, $start, $i - $start + 1);
                        // Validate it is parseable JSON before returning
                        json_decode($candidate);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            return $candidate;
                        }
                        // Not valid — keep scanning for the next opening bracket
                        break;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Map a decoded JSON array to `[['id' => int, 'translation' => string], ...]`
     * keyed by the `id` field in each element.  Items without a valid `id` or
     * `translation` are silently dropped.
     *
     * @param  array<mixed>  $decoded
     * @return array<int, array{id: int, translation: string}>
     */
    private function mapById(array $decoded): array
    {
        // Handle wrapped format: {"items": [...]}
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $decoded = $decoded['items'];
        }

        // Handle single object format: {"id": 1, "translation": "..."}
        if (isset($decoded['id'])) {
            $decoded = [$decoded];
        }

        $results = [];

        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = $item['id'] ?? null;
            // Accept both 'translation' (correct) and 'text' (model fallback)
            $translation = $item['translation'] ?? $item['text'] ?? null;

            if (! is_int($id) && ! is_numeric($id)) {
                continue;
            }

            if (! is_string($translation)) {
                continue;
            }

            $results[] = [
                'id' => (int) $id,
                'translation' => $translation,
            ];
        }

        return $results;
    }

    /**
     * Fallback parser for flat object format with duplicate keys, e.g.:
     * {"id":1,"translation":"foo","id":2,"translation":"bar"}
     *
     * Uses regex to extract all id+translation pairs in order from the raw string.
     *
     * @return array<int, array{id: int, translation: string}>
     */
    private function extractByRegex(string $raw): array
    {
        // Match all occurrences of "id": <number> followed (anywhere nearby) by "translation": "..."
        // or "text": "..." to handle model fallback field name.
        preg_match_all('/"id"\s*:\s*(\d+)/', $raw, $idMatches);
        preg_match_all('/"(?:translation|text)"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/', $raw, $translationMatches);

        $ids = $idMatches[1] ?? [];
        $translations = $translationMatches[1] ?? [];

        // Filter out the task-level "id" fields that aren't item ids by pairing positionally
        $results = [];
        $count = min(count($ids), count($translations));

        for ($i = 0; $i < $count; $i++) {
            $results[] = [
                'id' => (int) $ids[$i],
                'translation' => stripslashes($translations[$i]),
            ];
        }

        return $results;
    }

    /**
     * Build the JSON user message for the translation request.
     *
     * @param  array<int, array{id: int, text: string}>  $items
     */
    private function buildUserMessage(array $items, ?string $context): string
    {
        $payload = [
            'task' => 'translate_to_chinese',
            'items' => $items,
        ];

        if ($context !== null) {
            $payload['context'] = $context;
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
