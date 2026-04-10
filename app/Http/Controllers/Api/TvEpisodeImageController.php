<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListTvEpisodeImageRequest;
use App\Http\Resources\TvEpisodeImageResource;
use App\Services\TvEpisodeImageService;
use Illuminate\Http\JsonResponse;

class TvEpisodeImageController extends BaseController
{
    public function __construct(
        private readonly TvEpisodeImageService $tvEpisodeImageService
    ) {}

    public function index(ListTvEpisodeImageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->paginate(
            $this->tvEpisodeImageService->getList((int) $validated['tv_episode_id'], $validated),
            TvEpisodeImageResource::class
        );
    }
}
