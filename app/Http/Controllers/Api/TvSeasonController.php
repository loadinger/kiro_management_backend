<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListTvSeasonRequest;
use App\Http\Resources\TvSeasonListResource;
use App\Http\Resources\TvSeasonResource;
use App\Services\TvSeasonService;
use Illuminate\Http\JsonResponse;

class TvSeasonController extends BaseController
{
    public function __construct(
        private readonly TvSeasonService $tvSeasonService
    ) {}

    public function index(ListTvSeasonRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->listing(
            $this->tvSeasonService->getAll((int) $validated['tv_show_id'], $validated),
            TvSeasonListResource::class
        );
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new TvSeasonResource($this->tvSeasonService->findById($id)));
    }
}
