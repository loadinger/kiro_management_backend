<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Models\Person;
use App\Repositories\Contracts\PersonRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PersonService
{
    public function __construct(
        private readonly PersonRepositoryInterface $repository
    ) {}

    /**
     * Return a paginated list of persons with optional filters.
     * Supported filters: q, gender, adult, known_for_department, sort, order, page, per_page.
     */
    public function getList(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters);
    }

    /**
     * Find a person by its local ID.
     *
     * @throws AppException when the record does not exist
     */
    public function findById(int $id): Person
    {
        $person = $this->repository->findById($id);

        if ($person === null) {
            throw new AppException('人物不存在', 404);
        }

        return $person;
    }
}
