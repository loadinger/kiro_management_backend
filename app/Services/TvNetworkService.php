<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Models\TvNetwork;
use App\Repositories\Contracts\TvNetworkRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TvNetworkService
{
    public function __construct(
        private readonly TvNetworkRepositoryInterface $repository
    ) {}

    public function getList(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters);
    }

    /**
     * Find a TV network by its local ID.
     *
     * @throws AppException when the record does not exist
     */
    public function findById(int $id): TvNetwork
    {
        $network = $this->repository->findById($id);

        if ($network === null) {
            throw new AppException('电视网络不存在', 404);
        }

        return $network;
    }
}
