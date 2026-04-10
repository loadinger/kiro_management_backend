<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Models\Collection;
use App\Repositories\Contracts\CollectionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class CollectionService
{
    public function __construct(
        private readonly CollectionRepositoryInterface $repository
    ) {}

    public function getList(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters);
    }

    /**
     * Find a collection by its local ID, with movies preloaded.
     *
     * @throws AppException when the record does not exist
     */
    public function findById(int $id): Collection
    {
        $collection = $this->repository->findByIdWithMovies($id);

        if ($collection === null) {
            throw new AppException('合集不存在', 404);
        }

        return $collection;
    }
}
