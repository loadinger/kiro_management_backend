<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface ArticleItemRepositoryInterface
{
    public function paginateByEntity(string $entityType, int $entityId, array $filters): LengthAwarePaginator;

    public function deleteByArticleId(int $articleId): void;

    public function insertBatch(int $articleId, array $items): void;
}
