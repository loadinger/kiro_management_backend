<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListTvShowCreatorRequest;
use App\Http\Resources\TvShowCreatorResource;
use App\Services\TvShowCreatorService;
use Illuminate\Http\JsonResponse;

class TvShowCreatorController extends BaseController
{
    public function __construct(
        private readonly TvShowCreatorService $tvShowCreatorService
    ) {}

    public function index(ListTvShowCreatorRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->listing(
            $this->tvShowCreatorService->getList((int) $validated['tv_show_id']),
            TvShowCreatorResource::class
        );
    }
}
