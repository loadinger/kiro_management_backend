<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListTvEpisodeCreditRequest;
use App\Http\Resources\TvEpisodeCreditResource;
use App\Services\TvEpisodeCreditService;
use Illuminate\Http\JsonResponse;

class TvEpisodeCreditController extends BaseController
{
    public function __construct(
        private readonly TvEpisodeCreditService $tvEpisodeCreditService
    ) {}

    public function index(ListTvEpisodeCreditRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->paginate(
            $this->tvEpisodeCreditService->getList((int) $validated['tv_episode_id'], $validated),
            TvEpisodeCreditResource::class
        );
    }
}
