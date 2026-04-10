<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListTvShowImageRequest;
use App\Http\Resources\TvShowImageResource;
use App\Services\TvShowImageService;
use Illuminate\Http\JsonResponse;

class TvShowImageController extends BaseController
{
    public function __construct(
        private readonly TvShowImageService $tvShowImageService
    ) {}

    public function index(ListTvShowImageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->paginate(
            $this->tvShowImageService->getList((int) $validated['tv_show_id'], $validated),
            TvShowImageResource::class
        );
    }
}
