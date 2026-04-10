<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BaseController extends Controller
{
    protected function success(mixed $data = null, string $message = 'success'): JsonResponse
    {
        return response()->json([
            'code' => 0,
            'message' => $message,
            'data' => $data,
        ]);
    }

    protected function error(string $message, int $code = 500, mixed $data = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ]);
    }

    protected function paginate(LengthAwarePaginator $paginator, string $resourceClass): JsonResponse
    {
        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'list' => $resourceClass::collection($paginator->items()),
                'pagination' => [
                    'total' => $paginator->total(),
                    'page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Return a flat array response for small tables that don't require pagination.
     * Use for /xxx/all style endpoints only.
     */
    protected function listing(Collection $collection, string $resourceClass): JsonResponse
    {
        return $this->success($resourceClass::collection($collection));
    }
}
