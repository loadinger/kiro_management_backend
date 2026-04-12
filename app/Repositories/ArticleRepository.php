<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ArticleRepository extends BaseRepository implements ArticleRepositoryInterface
{
    /** Allowed sort fields to prevent SQL injection via orderBy. */
    private const ALLOWED_SORTS = ['sort_order', 'created_at', 'published_at'];

    public function __construct()
    {
        parent::__construct(new Article);
    }

    /**
     * Paginate articles with optional status filter and sort/order.
     * status: exact match on status field (ArticleStatus enum value).
     * sort/order: whitelist-validated, default sort_order asc.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Article::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sort = in_array($filters['sort'] ?? '', self::ALLOWED_SORTS, true)
            ? $filters['sort']
            : 'sort_order';

        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sort, $order);

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }

    /**
     * Find an article by its local id without eager loading.
     */
    public function findById(int $id): ?Article
    {
        return Article::find($id);
    }

    /**
     * Find an article by its slug.
     */
    public function findBySlug(string $slug): ?Article
    {
        return Article::where('slug', $slug)->first();
    }

    /**
     * Create a new article record.
     * fresh() ensures database-level defaults (e.g. status enum) are loaded back.
     */
    public function create(array $data): Article
    {
        return Article::create($data)->fresh();
    }

    /**
     * Update an existing article and return the refreshed instance.
     */
    public function update(Article $article, array $data): Article
    {
        $article->update($data);

        return $article->fresh();
    }

    /**
     * Delete an article by its local id.
     */
    public function delete(int $id): void
    {
        Article::destroy($id);
    }
}
