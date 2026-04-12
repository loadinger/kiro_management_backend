<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Person;
use App\Repositories\Contracts\PersonRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PersonRepository extends BaseRepository implements PersonRepositoryInterface
{
    /** Allowed sort fields to prevent SQL injection via orderBy. */
    private const ALLOWED_SORTS = ['id', 'popularity', 'updated_at', 'created_at'];

    public function __construct()
    {
        parent::__construct(new Person);
    }

    /**
     * Paginate persons with optional filters.
     * Supports gender, adult, known_for_department, q (LIKE q%) filters.
     * Sort fields whitelist: id, popularity, updated_at, created_at. Default: id DESC.
     *
     * Large table constraint: persons table has 5M+ rows.
     * per_page <= 50 is enforced at the FormRequest layer; no unconditional full table scan.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Person::query();

        if (isset($filters['gender']) && $filters['gender'] !== '') {
            $query->where('gender', (int) $filters['gender']);
        }

        if (isset($filters['adult']) && $filters['adult'] !== '') {
            $query->where('adult', (bool) $filters['adult']);
        }

        if (! empty($filters['known_for_department'])) {
            $query->where('known_for_department', $filters['known_for_department']);
        }

        if (! empty($filters['q'])) {
            $query->where('name', 'like', $filters['q'].'%');
        }

        $sort = in_array($filters['sort'] ?? '', self::ALLOWED_SORTS, true)
            ? $filters['sort']
            : 'id';

        $order = ($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sort, $order);

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }

    /**
     * Find a person by its local id. Returns null when not found.
     */
    public function findById(int $id): ?Person
    {
        return Person::find($id);
    }

    /**
     * Check whether a person with the given local id exists.
     */
    public function existsById(int $id): bool
    {
        return Person::where('id', $id)->exists();
    }
}
