<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListCollectionRequest;
use App\Http\Resources\CollectionListResource;
use App\Http\Resources\CollectionResource;
use App\Services\CollectionService;
use Illuminate\Http\JsonResponse;

class CollectionController extends BaseController
{
    public function __construct(
        private readonly CollectionService $collectionService
    ) {}

    public function index(ListCollectionRequest $request): JsonResponse
    {
        return $this->paginate(
            $this->collectionService->getList($request->validated()),
            CollectionListResource::class
        );
    }

    public function show(int $id): JsonResponse
    {
        $collection = $this->collectionService->findById($id);

        return $this->success(new CollectionResource($collection));
    }
}
