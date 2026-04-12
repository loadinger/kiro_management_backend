<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ListType;
use App\Http\Requests\GetMediaListRequest;
use App\Services\MediaListSnapshotService;
use Illuminate\Http\JsonResponse;

class MediaListSnapshotController extends BaseController
{
    public function __construct(private readonly MediaListSnapshotService $service) {}

    public function movieNowPlaying(GetMediaListRequest $request): JsonResponse
    {
        $result = $this->service->getMovieList(ListType::MovieNowPlaying, $request->validated('snapshot_date'));

        return $this->success($result);
    }

    public function movieUpcoming(GetMediaListRequest $request): JsonResponse
    {
        $result = $this->service->getMovieList(ListType::MovieUpcoming, $request->validated('snapshot_date'));

        return $this->success($result);
    }

    public function movieTrendingDay(GetMediaListRequest $request): JsonResponse
    {
        $result = $this->service->getMovieList(ListType::MovieTrendingDay, $request->validated('snapshot_date'));

        return $this->success($result);
    }

    public function movieTrendingWeek(GetMediaListRequest $request): JsonResponse
    {
        $result = $this->service->getMovieList(ListType::MovieTrendingWeek, $request->validated('snapshot_date'));

        return $this->success($result);
    }

    public function tvAiringToday(GetMediaListRequest $request): JsonResponse
    {
        $result = $this->service->getTvShowList(ListType::TvAiringToday, $request->validated('snapshot_date'));

        return $this->success($result);
    }

    public function tvOnTheAir(GetMediaListRequest $request): JsonResponse
    {
        $result = $this->service->getTvShowList(ListType::TvOnTheAir, $request->validated('snapshot_date'));

        return $this->success($result);
    }

    public function tvTrendingDay(GetMediaListRequest $request): JsonResponse
    {
        $result = $this->service->getTvShowList(ListType::TvTrendingDay, $request->validated('snapshot_date'));

        return $this->success($result);
    }

    public function tvTrendingWeek(GetMediaListRequest $request): JsonResponse
    {
        $result = $this->service->getTvShowList(ListType::TvTrendingWeek, $request->validated('snapshot_date'));

        return $this->success($result);
    }

    public function personTrendingDay(GetMediaListRequest $request): JsonResponse
    {
        $result = $this->service->getPersonList(ListType::PersonTrendingDay, $request->validated('snapshot_date'));

        return $this->success($result);
    }

    public function personTrendingWeek(GetMediaListRequest $request): JsonResponse
    {
        $result = $this->service->getPersonList(ListType::PersonTrendingWeek, $request->validated('snapshot_date'));

        return $this->success($result);
    }
}
