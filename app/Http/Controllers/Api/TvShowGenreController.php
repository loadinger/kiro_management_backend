<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListTvShowGenreRequest;
use App\Http\Resources\TvShowGenreResource;
use App\Services\TvShowGenreService;
use Illuminate\Http\JsonResponse;

class TvShowGenreController extends BaseController
{
    public function __construct(
        private readonly TvShowGenreService $tvShowGenreService
    ) {}

    public function index(ListTvShowGenreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->listing(
            $this->tvShowGenreService->getList((int) $validated['tv_show_id']),
            TvShowGenreResource::class
        );
    }
}
