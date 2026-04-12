<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\ArticleItemRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ArticleItemService
{
    public function __construct(
        private readonly ArticleItemRepositoryInterface $articleItemRepository,
    ) {}

    /**
     * Return a paginated list of article_items for the given entity.
     * Requires entity_type and entity_id in $filters.
     */
    public function getByEntity(array $filters): LengthAwarePaginator
    {
        $entityType = (string) ($filters['entity_type'] ?? '');
        $entityId = (int) ($filters['entity_id'] ?? 0);

        return $this->articleItemRepository->paginateByEntity($entityType, $entityId, $filters);
    }
}
