<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListTvShowProductionCompanyRequest;
use App\Http\Resources\TvShowProductionCompanyResource;
use App\Services\TvShowProductionCompanyService;
use Illuminate\Http\JsonResponse;

class TvShowProductionCompanyController extends BaseController
{
    public function __construct(
        private readonly TvShowProductionCompanyService $tvShowProductionCompanyService
    ) {}

    public function index(ListTvShowProductionCompanyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->listing(
            $this->tvShowProductionCompanyService->getList((int) $validated['tv_show_id']),
            TvShowProductionCompanyResource::class
        );
    }
}
