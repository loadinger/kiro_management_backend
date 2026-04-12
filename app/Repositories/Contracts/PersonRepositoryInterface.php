<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Person;
use Illuminate\Pagination\LengthAwarePaginator;

interface PersonRepositoryInterface
{
    /**
     * Paginate persons with optional filters.
     * Supports gender, adult, known_for_department, q (LIKE q%) filters.
     * Sort fields whitelist: id, popularity, updated_at, created_at. Default: id DESC.
     * Large table constraint: per_page <= 50, no unconditional full table scan.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator;

    /**
     * Find a person by its local id. Returns null when not found.
     */
    public function findById(int $id): ?Person;

    /**
     * Check whether a person with the given local id exists.
     */
    public function existsById(int $id): bool;
}
