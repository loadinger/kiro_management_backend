<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListTvSeasonImageRequest;
use App\Http\Resources\TvSeasonImageResource;
use App\Services\TvSeasonImageService;
use Illuminate\Http\JsonResponse;

class TvSeasonImageController extends BaseController
{
    public function __construct(
        private readonly TvSeasonImageService $tvSeasonImageService
    ) {}

    public function index(ListTvSeasonImageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->paginate(
            $this->tvSeasonImageService->getList((int) $validated['tv_season_id'], $validated),
            TvSeasonImageResource::class
        );
    }
}
