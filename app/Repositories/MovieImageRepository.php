<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\MovieImage;
use App\Repositories\Contracts\MovieImageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class MovieImageRepository extends BaseRepository implements MovieImageRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new MovieImage);
    }

    /**
     * Paginate images for a given movie, optionally filtered by image_type.
     */
    public function paginateByMovieId(int $movieId, array $filters): LengthAwarePaginator
    {
        $query = MovieImage::where('movie_id', $movieId);

        if (! empty($filters['image_type'])) {
            $query->where('image_type', $filters['image_type']);
        }

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }
}
