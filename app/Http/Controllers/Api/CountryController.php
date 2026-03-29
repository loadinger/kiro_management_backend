<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListCountryRequest;
use App\Http\Resources\CountryResource;
use App\Services\CountryService;
use Illuminate\Http\JsonResponse;

class CountryController extends BaseController
{
    public function __construct(
        private readonly CountryService $countryService
    ) {}

    public function index(ListCountryRequest $request): JsonResponse
    {
        return $this->paginate(
            $this->countryService->getList($request->validated()),
            CountryResource::class
        );
    }
}
