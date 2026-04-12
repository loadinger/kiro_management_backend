<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListArticleRequest;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleListResource;
use App\Http\Resources\ArticleResource;
use App\Services\ArticleService;
use Illuminate\Http\JsonResponse;

class ArticleController extends BaseController
{
    public function __construct(
        private readonly ArticleService $articleService
    ) {}

    public function index(ListArticleRequest $request): JsonResponse
    {
        return $this->paginate(
            $this->articleService->getList($request->validated()),
            ArticleListResource::class
        );
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $article = $this->articleService->create($request->validated(), $request->user()->id);

        return $this->success(new ArticleResource($article));
    }

    public function show(int $id): JsonResponse
    {
        $article = $this->articleService->findById($id);

        return $this->success(new ArticleResource($article));
    }

    public function update(UpdateArticleRequest $request, int $id): JsonResponse
    {
        $article = $this->articleService->update($id, $request->validated());

        return $this->success(new ArticleResource($article));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->articleService->delete($id);

        return $this->success(['success' => true]);
    }
}
