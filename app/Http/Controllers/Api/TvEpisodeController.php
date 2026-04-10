<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListTvEpisodeRequest;
use App\Http\Resources\TvEpisodeListResource;
use App\Http\Resources\TvEpisodeResource;
use App\Services\TvEpisodeService;
use Illuminate\Http\JsonResponse;

class TvEpisodeController extends BaseController
{
    public function __construct(
        private readonly TvEpisodeService $tvEpisodeService
    ) {}

    public function index(ListTvEpisodeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->paginate(
            $this->tvEpisodeService->getList((int) $validated['tv_season_id'], $validated),
            TvEpisodeListResource::class
        );
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new TvEpisodeResource($this->tvEpisodeService->findById($id)));
    }
}
