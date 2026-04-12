<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Article;
use Illuminate\Pagination\LengthAwarePaginator;

interface ArticleRepositoryInterface
{
    public function paginateWithFilters(array $filters): LengthAwarePaginator;

    public function findById(int $id): ?Article;

    public function findBySlug(string $slug): ?Article;

    public function create(array $data): Article;

    public function update(Article $article, array $data): Article;

    public function delete(int $id): void;
}
