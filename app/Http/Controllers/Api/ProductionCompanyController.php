<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListProductionCompanyRequest;
use App\Http\Resources\ProductionCompanyListResource;
use App\Http\Resources\ProductionCompanyResource;
use App\Services\ProductionCompanyService;
use Illuminate\Http\JsonResponse;

class ProductionCompanyController extends BaseController
{
    public function __construct(
        private readonly ProductionCompanyService $productionCompanyService
    ) {}

    public function index(ListProductionCompanyRequest $request): JsonResponse
    {
        return $this->paginate(
            $this->productionCompanyService->getList($request->validated()),
            ProductionCompanyListResource::class
        );
    }

    public function show(int $id): JsonResponse
    {
        $company = $this->productionCompanyService->findById($id);

        return $this->success(new ProductionCompanyResource($company));
    }
}
