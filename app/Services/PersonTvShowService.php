<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Repositories\Contracts\PersonRepositoryInterface;
use App\Repositories\Contracts\PersonTvShowRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PersonTvShowService
{
    public function __construct(
        private readonly PersonRepositoryInterface $personRepository,
        private readonly PersonTvShowRepositoryInterface $personTvShowRepository,
    ) {}

    /**
     * Return a paginated list of TV show credits for a given person.
     *
     * @throws AppException when the person does not exist
     */
    public function getList(int $personId, array $filters): LengthAwarePaginator
    {
        if (! $this->personRepository->existsById($personId)) {
            throw new AppException('人物不存在', 404);
        }

        return $this->personTvShowRepository->paginateByPersonId($personId, $filters);
    }
}
