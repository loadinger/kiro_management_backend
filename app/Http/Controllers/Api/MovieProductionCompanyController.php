<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListMovieProductionCompanyRequest;
use App\Http\Resources\MovieProductionCompanyResource;
use App\Services\MovieProductionCompanyService;
use Illuminate\Http\JsonResponse;

class MovieProductionCompanyController extends BaseController
{
    public function __construct(
        private readonly MovieProductionCompanyService $movieProductionCompanyService
    ) {}

    public function index(ListMovieProductionCompanyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->listing(
            $this->movieProductionCompanyService->getByMovieId((int) $validated['movie_id']),
            MovieProductionCompanyResource::class
        );
    }
}
