<?php

namespace App\Http\Controllers\Api\V1\Settings\Articles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\Articles\StoreArticleRequest;
use App\Http\Requests\Settings\Articles\UpdateArticleRequest;
use App\Http\Resources\Settings\Articles\ArticleResource;
use App\Models\Settings\ArticleModel;
use App\Services\Settings\Articles\ArticleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleControllerAPI extends Controller
{
    public function __construct(
        protected ArticleService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $articles = $this->service->paginate($request->only([
            'search',
            'page',
            'per_page',
            'sort',
            'direction',
            'active_only',
        ]));
        $serializedItems = ArticleResource::collection($articles->items())->resolve($request);

        return response()->json([
            'message' => 'Articles retrieved successfully',
            'data' => $serializedItems,
            'meta' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ],
        ], 200);
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $article = $this->service->create(
            $request->validated(),
            $request->file('photo')
        );

        return response()->json([
            'message' => 'Article created successfully',
            'data' => new ArticleResource($article),
        ], 201);
    }

    public function show(ArticleModel $article): JsonResponse
    {
        $article->load('vat:id,name,rate');

        return response()->json([
            'message' => 'Article retrieved successfully',
            'data' => new ArticleResource($article),
        ], 200);
    }

    public function update(UpdateArticleRequest $request, ArticleModel $article): JsonResponse
    {
        $updated = $this->service->update(
            $article,
            $request->validated(),
            $request->file('photo')
        );

        return response()->json([
            'message' => 'Article updated successfully',
            'data' => new ArticleResource($updated),
        ], 200);
    }

    public function destroy(ArticleModel $article): JsonResponse
    {
        $updated = $this->service->inactivate($article);

        return response()->json([
            'message' => 'Article inactivated successfully',
            'data' => new ArticleResource($updated),
        ], 200);
    }
}
