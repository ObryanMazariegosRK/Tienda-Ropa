<?php

namespace App\Http\Controllers;

use App\Application\Abstractions\Cart\IAddToCartUseCase;
use App\Application\Abstractions\Cart\IGetCartUseCase;
use App\Application\Abstractions\Cart\IRemoveFromCartUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private IGetCartUseCase $getCartUseCase,
        private IAddToCartUseCase $addToCartUseCase,
        private IRemoveFromCartUseCase $removeFromCartUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $cart = $this->getCartUseCase->execute($request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $cart
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener tu carrito.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'productId' => ['required', 'integer', 'exists:products,id'],
        ]);

        try {
            $cart = $this->addToCartUseCase->execute(
                $request->user()->id,
                $request->input('productId')
            );

            return response()->json([
                'success' => true,
                'data' => $cart
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function destroy(Request $request, int $cartItemId): JsonResponse
    {
        try {
            $cart = $this->removeFromCartUseCase->execute(
                $request->user()->id,
                $cartItemId
            );

            return response()->json([
                'success' => true,
                'data' => $cart
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}