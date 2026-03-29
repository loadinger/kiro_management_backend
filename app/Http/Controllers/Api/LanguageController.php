<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListLanguageRequest;
use App\Http\Resources\LanguageResource;
use App\Services\LanguageService;
use Illuminate\Http\JsonResponse;

class LanguageController extends BaseController
{
    public function __construct(
        private readonly LanguageService $languageService
    ) {}

    public function index(ListLanguageRequest $request): JsonResponse
    {
        return $this->paginate(
            $this->languageService->getList($request->validated()),
            LanguageResource::class
        );
    }
}
