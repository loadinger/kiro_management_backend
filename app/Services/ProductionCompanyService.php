<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Models\ProductionCompany;
use App\Repositories\Contracts\ProductionCompanyRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductionCompanyService
{
    public function __construct(
        private readonly ProductionCompanyRepositoryInterface $repository
    ) {}

    public function getList(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters);
    }

    /**
     * Find a production company by its local ID.
     *
     * @throws AppException when the record does not exist
     */
    public function findById(int $id): ProductionCompany
    {
        $company = $this->repository->findById($id);

        if ($company === null) {
            throw new AppException('制作公司不存在', 404);
        }

        return $company;
    }
}
