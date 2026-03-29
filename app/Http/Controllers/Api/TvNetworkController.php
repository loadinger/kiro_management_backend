<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListTvNetworkRequest;
use App\Http\Resources\TvNetworkListResource;
use App\Http\Resources\TvNetworkResource;
use App\Services\TvNetworkService;
use Illuminate\Http\JsonResponse;

class TvNetworkController extends BaseController
{
    public function __construct(
        private readonly TvNetworkService $tvNetworkService
    ) {}

    public function index(ListTvNetworkRequest $request): JsonResponse
    {
        return $this->paginate(
            $this->tvNetworkService->getList($request->validated()),
            TvNetworkListResource::class
        );
    }

    public function show(int $id): JsonResponse
    {
        $network = $this->tvNetworkService->findById($id);

        return $this->success(new TvNetworkResource($network));
    }
}
