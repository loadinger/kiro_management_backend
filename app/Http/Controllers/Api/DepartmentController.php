<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;

class DepartmentController extends BaseController
{
    public function __construct(
        private readonly DepartmentService $departmentService
    ) {}

    public function index(ListDepartmentRequest $request): JsonResponse
    {
        return $this->paginate(
            $this->departmentService->getList($request->validated()),
            DepartmentResource::class
        );
    }
}
