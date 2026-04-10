<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListTvShowKeywordRequest;
use App\Http\Resources\TvShowKeywordResource;
use App\Services\TvShowKeywordService;
use Illuminate\Http\JsonResponse;

class TvShowKeywordController extends BaseController
{
    public function __construct(
        private readonly TvShowKeywordService $tvShowKeywordService
    ) {}

    public function index(ListTvShowKeywordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->listing(
            $this->tvShowKeywordService->getList((int) $validated['tv_show_id']),
            TvShowKeywordResource::class
        );
    }
}
