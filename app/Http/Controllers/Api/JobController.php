<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListJobRequest;
use App\Http\Resources\JobResource;
use App\Services\JobService;
use Illuminate\Http\JsonResponse;

class JobController extends BaseController
{
    public function __construct(
        private readonly JobService $jobService
    ) {}

    public function index(ListJobRequest $request): JsonResponse
    {
        return $this->paginate(
            $this->jobService->getList($request->validated()),
            JobResource::class
        );
    }
}
