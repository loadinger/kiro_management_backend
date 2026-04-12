<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListPersonMovieRequest;
use App\Http\Resources\PersonMovieResource;
use App\Services\PersonMovieService;
use Illuminate\Http\JsonResponse;

class PersonMovieController extends BaseController
{
    public function __construct(
        private readonly PersonMovieService $personMovieService
    ) {}

    public function index(ListPersonMovieRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $personId = (int) $validated['person_id'];
        $filters = collect($validated)->except('person_id')->toArray();

        $result = $this->personMovieService->getList($personId, $filters);

        return $this->paginate($result, PersonMovieResource::class);
    }
}
