<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListGenreRequest;
use App\Http\Resources\GenreResource;
use App\Services\GenreService;
use Illuminate\Http\JsonResponse;

class GenreController extends BaseController
{
    public function __construct(
        private readonly GenreService $genreService
    ) {}

    public function index(ListGenreRequest $request): JsonResponse
    {
        return $this->paginate(
            $this->genreService->getList($request->validated()),
            GenreResource::class
        );
    }

    public function all(ListGenreRequest $request): JsonResponse
    {
        return $this->listing(
            $this->genreService->getAll($request->validated()),
            GenreResource::class
        );
    }
}
