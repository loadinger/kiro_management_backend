<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListPersonTvShowRequest;
use App\Http\Resources\PersonTvShowResource;
use App\Services\PersonTvShowService;
use Illuminate\Http\JsonResponse;

class PersonTvShowController extends BaseController
{
    public function __construct(
        private readonly PersonTvShowService $personTvShowService
    ) {}

    public function index(ListPersonTvShowRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $personId = (int) $validated['person_id'];
        $filters = collect($validated)->except('person_id')->toArray();

        $result = $this->personTvShowService->getList($personId, $filters);

        return $this->paginate($result, PersonTvShowResource::class);
    }
}
