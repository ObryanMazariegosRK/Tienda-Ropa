<?php

namespace App\Http\Controllers;

use App\Application\Abstractions\Order\ICheckoutUseCase;
use App\Application\Abstractions\Order\IListMyOrdersUseCase;
use App\Application\Abstractions\Order\IUpdateOrderStatusUseCase;
use App\Application\Abstractions\Order\IListAllOrdersUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private ICheckoutUseCase $checkoutUseCase,
        private IListMyOrdersUseCase $listMyOrdersUseCase,
        private IUpdateOrderStatusUseCase $updateOrderStatusUseCase,
        private IListAllOrdersUseCase $listAllOrdersUseCase
    ) {}

    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'addressId' => ['required', 'integer'],
        ]);

        try {
            $result = $this->checkoutUseCase->execute(
                $request->user()->id,
                $request->input('addressId')
            );

            return response()->json(['success' => true, 'data' => $result], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function myOrders(Request $request): JsonResponse
    {
        $orders = $this->listMyOrdersUseCase->execute($request->user()->id);
        return response()->json(['success' => true, 'data' => $orders], 200);
    }

    // Endpoint para el panel del administrador
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string'],
        ]);

        try {
            $order = $this->updateOrderStatusUseCase->execute($id, $request->input('status'));
            return response()->json(['success' => true, 'data' => $order], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function allOrders(Request $request): JsonResponse
    {
        $status = $request->query('status'); // opcional: ?status=pending_payment

        $orders = $this->listAllOrdersUseCase->execute($status);

        return response()->json(['success' => true, 'data' => $orders], 200);
    }
}