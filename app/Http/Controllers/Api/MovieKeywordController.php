<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListMovieKeywordRequest;
use App\Http\Resources\MovieKeywordResource;
use App\Services\MovieKeywordService;
use Illuminate\Http\JsonResponse;

class MovieKeywordController extends BaseController
{
    public function __construct(
        private readonly MovieKeywordService $movieKeywordService
    ) {}

    public function index(ListMovieKeywordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->listing(
            $this->movieKeywordService->getByMovieId((int) $validated['movie_id']),
            MovieKeywordResource::class
        );
    }
}
