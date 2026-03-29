<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Language;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class LanguageRepository extends BaseRepository implements LanguageRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Language);
    }

    /**
     * Paginate languages with optional prefix search on english_name or name.
     * Sort whitelist: id, english_name.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Language::query();

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('english_name', 'like', $q.'%')
                    ->orWhere('name', 'like', $q.'%');
            });
        }

        $allowedSorts = ['id', 'english_name'];
        $sort = in_array($filters['sort'] ?? null, $allowedSorts, true) ? $filters['sort'] : 'id';
        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sort, $order);

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }
}
