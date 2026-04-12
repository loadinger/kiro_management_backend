<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ArticleItem;
use App\Repositories\Contracts\ArticleItemRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ArticleItemRepository extends BaseRepository implements ArticleItemRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new ArticleItem);
    }

    /**
     * Paginate article items filtered by entity type and entity id, with article eager loaded.
     */
    public function paginateByEntity(string $entityType, int $entityId, array $filters): LengthAwarePaginator
    {
        return ArticleItem::with('article')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->paginate(
                perPage: (int) ($filters['per_page'] ?? 20),
                page: (int) ($filters['page'] ?? 1),
            );
    }

    /**
     * Delete all article items belonging to the given article.
     */
    public function deleteByArticleId(int $articleId): void
    {
        ArticleItem::where('article_id', $articleId)->delete();
    }

    /**
     * Batch insert article items for the given article.
     * Each item in $items must contain entity_type (enum string) and entity_id.
     *
     * @param  array<int, array{entity_type: string, entity_id: int}>  $items
     */
    public function insertBatch(int $articleId, array $items): void
    {
        if (empty($items)) {
            return;
        }

        $records = array_map(
            fn (array $item): array => [
                'article_id' => $articleId,
                'entity_type' => $item['entity_type'],
                'entity_id' => $item['entity_id'],
            ],
            $items,
        );

        ArticleItem::insert($records);
    }
}
