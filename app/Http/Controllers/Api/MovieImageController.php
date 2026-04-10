<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListMovieImageRequest;
use App\Http\Resources\MovieImageResource;
use App\Services\MovieImageService;
use Illuminate\Http\JsonResponse;

class MovieImageController extends BaseController
{
    public function __construct(
        private readonly MovieImageService $movieImageService
    ) {}

    public function index(ListMovieImageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->paginate(
            $this->movieImageService->getList((int) $validated['movie_id'], $validated),
            MovieImageResource::class
        );
    }
}
