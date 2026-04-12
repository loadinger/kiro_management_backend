<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListArticleItemRequest;
use App\Http\Resources\ArticleItemResource;
use App\Services\ArticleItemService;
use Illuminate\Http\JsonResponse;

class ArticleItemController extends BaseController
{
    public function __construct(
        private readonly ArticleItemService $articleItemService
    ) {}

    public function index(ListArticleItemRequest $request): JsonResponse
    {
        return $this->paginate(
            $this->articleItemService->getByEntity($request->validated()),
            ArticleItemResource::class
        );
    }
}
