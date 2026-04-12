<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Repositories\Contracts\PersonMovieRepositoryInterface;
use App\Repositories\Contracts\PersonRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PersonMovieService
{
    public function __construct(
        private readonly PersonRepositoryInterface $personRepository,
        private readonly PersonMovieRepositoryInterface $personMovieRepository,
    ) {}

    /**
     * Return a paginated list of movie credits for a given person.
     *
     * @throws AppException when the person does not exist
     */
    public function getList(int $personId, array $filters): LengthAwarePaginator
    {
        if (! $this->personRepository->existsById($personId)) {
            throw new AppException('人物不存在', 404);
        }

        return $this->personMovieRepository->paginateByPersonId($personId, $filters);
    }
}
