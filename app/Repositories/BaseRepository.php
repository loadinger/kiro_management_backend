<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    public function __construct(protected Model $model) {}

    public function findById(int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findByTmdbId(int $tmdbId): ?Model
    {
        return $this->model->where('tmdb_id', $tmdbId)->first();
    }

    public function paginate(int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        return $this->model->paginate($perPage, ['*'], 'page', $page);
    }
}
