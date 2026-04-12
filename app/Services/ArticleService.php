<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ArticleEntityType;
use App\Exceptions\AppException;
use App\Models\Article;
use App\Repositories\Contracts\ArticleItemRepositoryInterface;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ArticleService
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ArticleItemRepositoryInterface $articleItemRepository,
    ) {}

    /**
     * Return a paginated list of articles with optional filters.
     */
    public function getList(array $filters): LengthAwarePaginator
    {
        return $this->articleRepository->paginateWithFilters($filters);
    }

    /**
     * Find an article by its local ID, including the entities map.
     *
     * @throws AppException when the record does not exist
     */
    public function findById(int $id): Article
    {
        $article = $this->articleRepository->findById($id);

        if ($article === null) {
            throw new AppException('专题不存在', 404);
        }

        $article->entities = $this->buildEntitiesMap($article);

        return $article;
    }

    /**
     * Create a new article and sync article_items from content placeholders.
     *
     * @throws AppException when the slug is already taken
     */
    public function create(array $data, int $userId): Article
    {
        return DB::transaction(function () use ($data, $userId): Article {
            $data['created_by'] = $userId;

            if (isset($data['slug']) && $data['slug'] !== null) {
                if ($this->articleRepository->findBySlug($data['slug']) !== null) {
                    throw new AppException('slug 已被使用', 422);
                }
            }

            $article = $this->articleRepository->create($data);

            $items = $this->parsePlaceholders($data['content'] ?? '');
            $this->syncArticleItems($article->id, $items);

            return $article;
        });
    }

    /**
     * Update an existing article and re-sync article_items when content changes.
     *
     * @throws AppException when the article does not exist or slug is already taken
     */
    public function update(int $id, array $data): Article
    {
        // Use repository directly to avoid loading entities map unnecessarily
        $article = $this->articleRepository->findById($id);

        if ($article === null) {
            throw new AppException('专题不存在', 404);
        }

        return DB::transaction(function () use ($article, $data): Article {
            if (isset($data['slug']) && $data['slug'] !== null) {
                $existing = $this->articleRepository->findBySlug($data['slug']);
                if ($existing !== null && $existing->id !== $article->id) {
                    throw new AppException('slug 已被使用', 422);
                }
            }

            $article = $this->articleRepository->update($article, $data);

            if (array_key_exists('content', $data)) {
                $items = $this->parsePlaceholders($data['content'] ?? '');
                $this->syncArticleItems($article->id, $items);
            }

            return $article;
        });
    }

    /**
     * Delete an article and all its associated article_items within a transaction.
     *
     * @throws AppException when the article does not exist
     */
    public function delete(int $id): void
    {
        $article = $this->articleRepository->findById($id);

        if ($article === null) {
            throw new AppException('专题不存在', 404);
        }

        DB::transaction(function () use ($id): void {
            $this->articleItemRepository->deleteByArticleId($id);
            $this->articleRepository->delete($id);
        });
    }

    /**
     * Parse ::media{type="..." id="..."} placeholders from content.
     * Supports both type-before-id and id-before-type attribute orders.
     * Invalid type or non-positive-integer id entries are silently ignored.
     * Duplicate (entity_type, entity_id) pairs are deduplicated.
     *
     * @return array<int, array{entity_type: string, entity_id: int}>
     */
    private function parsePlaceholders(string $content): array
    {
        $patterns = [
            // type before id
            '/::\s*media\s*\{[^}]*type\s*=\s*"([^"]+)"[^}]*id\s*=\s*"([^"]+)"[^}]*\}/',
            // id before type
            '/::\s*media\s*\{[^}]*id\s*=\s*"([^"]+)"[^}]*type\s*=\s*"([^"]+)"[^}]*\}/',
        ];

        $seen = [];
        $results = [];

        foreach ($patterns as $index => $pattern) {
            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                // For pattern 0: $match[1]=type, $match[2]=id
                // For pattern 1: $match[1]=id,   $match[2]=type
                if ($index === 0) {
                    $type = $match[1];
                    $rawId = $match[2];
                } else {
                    $rawId = $match[1];
                    $type = $match[2];
                }

                // Validate entity type
                if (ArticleEntityType::tryFrom($type) === null) {
                    continue;
                }

                // Validate entity id is a positive integer
                if (!ctype_digit($rawId) || (int) $rawId <= 0) {
                    continue;
                }

                $entityId = (int) $rawId;
                $key = $type . ':' . $entityId;

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $results[] = [
                    'entity_type' => $type,
                    'entity_id' => $entityId,
                ];
            }
        }

        return $results;
    }

    /**
     * Build a grouped items map for the given article.
     * Groups article_items by entity_type, then issues one whereIn query per type.
     * Each entity type returns an array of entity objects (empty array if none referenced).
     * Missing entities are omitted from their type's array.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function buildEntitiesMap(Article $article): array
    {
        $article->load('items');

        /** @var array<string, int[]> $grouped */
        $grouped = [];
        foreach ($article->items as $item) {
            $grouped[$item->entity_type->value][] = $item->entity_id;
        }

        // Model class and select fields for each entity type
        $typeConfig = [
            ArticleEntityType::Movie->value => [
                'model' => \App\Models\Movie::class,
                'fields' => ['id', 'title', 'poster_path'],
                'key' => 'movies',
            ],
            ArticleEntityType::Collection->value => [
                'model' => \App\Models\Collection::class,
                'fields' => ['id', 'name', 'poster_path'],
                'key' => 'collections',
            ],
            ArticleEntityType::TvShow->value => [
                'model' => \App\Models\TvShow::class,
                'fields' => ['id', 'name', 'poster_path'],
                'key' => 'tv_shows',
            ],
            ArticleEntityType::TvSeason->value => [
                'model' => \App\Models\TvSeason::class,
                'fields' => ['id', 'name', 'poster_path', 'season_number'],
                'key' => 'tv_seasons',
            ],
            ArticleEntityType::TvEpisode->value => [
                'model' => \App\Models\TvEpisode::class,
                'fields' => ['id', 'name', 'still_path', 'episode_number'],
                'key' => 'tv_episodes',
            ],
            ArticleEntityType::Person->value => [
                'model' => \App\Models\Person::class,
                'fields' => ['id', 'name', 'profile_path'],
                'key' => 'persons',
            ],
            ArticleEntityType::ProductionCompany->value => [
                'model' => \App\Models\ProductionCompany::class,
                'fields' => ['id', 'name', 'logo_path'],
                'key' => 'production_companies',
            ],
            ArticleEntityType::TvNetwork->value => [
                'model' => \App\Models\TvNetwork::class,
                'fields' => ['id', 'name', 'logo_path'],
                'key' => 'tv_networks',
            ],
            ArticleEntityType::Genre->value => [
                'model' => \App\Models\Genre::class,
                'fields' => ['id', 'name'],
                'key' => 'genres',
            ],
            ArticleEntityType::Keyword->value => [
                'model' => \App\Models\Keyword::class,
                'fields' => ['id', 'name'],
                'key' => 'keywords',
            ],
        ];

        // Initialize all keys with empty arrays so the structure is always complete
        $map = [];
        foreach ($typeConfig as $config) {
            $map[$config['key']] = [];
        }

        foreach ($grouped as $entityType => $entityIds) {
            if (!isset($typeConfig[$entityType])) {
                continue;
            }

            $config = $typeConfig[$entityType];
            /** @var \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model> $entities */
            $entities = $config['model']::whereIn('id', $entityIds)
                ->select($config['fields'])
                ->get();

            $map[$config['key']] = $entities->map(fn ($e) => $e->toArray())->values()->all();
        }

        return $map;
    }

    /**
     * Fully replace article_items for the given article (delete then batch insert).
     */
    private function syncArticleItems(int $articleId, array $items): void
    {
        $this->articleItemRepository->deleteByArticleId($articleId);
        $this->articleItemRepository->insertBatch($articleId, $items);
    }
}
