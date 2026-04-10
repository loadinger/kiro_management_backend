<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListMovieRequest;
use App\Http\Resources\MovieListResource;
use App\Http\Resources\MovieResource;
use App\Services\MovieService;
use Illuminate\Http\JsonResponse;

class MovieController extends BaseController
{
    public function __construct(
        private readonly MovieService $movieService
    ) {}

    public function index(ListMovieRequest $request): JsonResponse
    {
        return $this->paginate(
            $this->movieService->getList($request->validated()),
            MovieListResource::class
        );
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new MovieResource($this->movieService->findById($id)));
    }
}
