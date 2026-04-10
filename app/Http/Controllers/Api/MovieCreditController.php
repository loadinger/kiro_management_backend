<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListMovieCreditRequest;
use App\Http\Resources\MovieCreditResource;
use App\Services\MovieCreditService;
use Illuminate\Http\JsonResponse;

class MovieCreditController extends BaseController
{
    public function __construct(
        private readonly MovieCreditService $movieCreditService
    ) {}

    public function index(ListMovieCreditRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->paginate(
            $this->movieCreditService->getList((int) $validated['movie_id'], $validated),
            MovieCreditResource::class
        );
    }
}
