<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListMovieGenreRequest;
use App\Http\Resources\MovieGenreResource;
use App\Services\MovieGenreService;
use Illuminate\Http\JsonResponse;

class MovieGenreController extends BaseController
{
    public function __construct(
        private readonly MovieGenreService $movieGenreService
    ) {}

    public function index(ListMovieGenreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->listing(
            $this->movieGenreService->getByMovieId((int) $validated['movie_id']),
            MovieGenreResource::class
        );
    }
}
