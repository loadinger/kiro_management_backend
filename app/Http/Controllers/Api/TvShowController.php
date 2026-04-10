<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListTvShowRequest;
use App\Http\Resources\TvShowListResource;
use App\Http\Resources\TvShowResource;
use App\Services\TvShowService;
use Illuminate\Http\JsonResponse;

class TvShowController extends BaseController
{
    public function __construct(
        private readonly TvShowService $tvShowService
    ) {}

    public function index(ListTvShowRequest $request): JsonResponse
    {
        return $this->paginate(
            $this->tvShowService->getList($request->validated()),
            TvShowListResource::class
        );
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new TvShowResource($this->tvShowService->findById($id)));
    }
}
