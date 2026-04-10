<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListTvShowNetworkRequest;
use App\Http\Resources\TvShowNetworkResource;
use App\Services\TvShowNetworkService;
use Illuminate\Http\JsonResponse;

class TvShowNetworkController extends BaseController
{
    public function __construct(
        private readonly TvShowNetworkService $tvShowNetworkService
    ) {}

    public function index(ListTvShowNetworkRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->listing(
            $this->tvShowNetworkService->getList((int) $validated['tv_show_id']),
            TvShowNetworkResource::class
        );
    }
}
