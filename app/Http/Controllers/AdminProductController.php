<?php

namespace App\Http\Controllers;

use App\Application\Abstractions\Product\IGetPaginatedProductsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Application\Abstractions\Product\IGetPaginatedProductsForAdminUseCase;

class AdminProductController extends Controller
{
    public function __construct(
        private IGetPaginatedProductsForAdminUseCase $getPaginatedProductsForAdminUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->query('page', 1));
            $perPage = (int) $request->query('per_page', 24);
            $status = $request->query('status');
            $categoryId = $request->query('category_id') ? (int) $request->query('category_id') : null;

            $result = $this->getPaginatedProductsForAdminUseCase->execute($page, $perPage, $status, $categoryId);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'meta' => $result['meta'],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener el catálogo de productos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}