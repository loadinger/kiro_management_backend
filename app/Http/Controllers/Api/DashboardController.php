<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\GetTrendsRequest;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseController
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    /**
     * Return all aggregated dashboard statistics.
     * Results are cached by DashboardService (TTL 10 minutes).
     */
    public function stats(): JsonResponse
    {
        $data = $this->dashboardService->getStats();

        return $this->success($data);
    }

    /**
     * Return daily trend data for the specified entities and time range.
     * Results are cached by DashboardService (TTL 5 minutes).
     */
    public function trends(GetTrendsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $data      = $this->dashboardService->getTrends($validated['days'], $validated['entities']);

        return $this->success($data);
    }
}
