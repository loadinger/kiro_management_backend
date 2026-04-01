<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListKeywordRequest;
use App\Http\Resources\KeywordResource;
use App\Services\KeywordService;
use Illuminate\Http\JsonResponse;

class KeywordController extends BaseController
{
    public function __construct(
        private readonly KeywordService $keywordService
    ) {}

    public function index(ListKeywordRequest $request): JsonResponse
    {
        return $this->paginate(
            $this->keywordService->getList($request->validated()),
            KeywordResource::class
        );
    }

    public function all(ListKeywordRequest $request): JsonResponse
    {
        return $this->listing(
            $this->keywordService->getAll($request->validated()),
            KeywordResource::class
        );
    }
}
