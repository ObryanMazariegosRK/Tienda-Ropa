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
            'phone'     => ['nullable', 'string', 'regex:/^[0-9]{8}$/'],  
        ]);

        try {
            $result = $this->checkoutUseCase->execute(
                $request->user()->id,
                $request->input('addressId'),
                $request->input('phone')        // 
            );

            return response()->json(['success' => true, 'data' => $result], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function myOrders(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(30, max(1, (int) $request->query('per_page', 10)));

        $result = $this->listMyOrdersUseCase->execute($request->user()->id, $page, $perPage);
        return response()->json(['success' => true, 'data' => $result['data'], 'meta' => $result['meta']], 200);
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
        $status = $request->query('status');
        $grupo  = $request->query('grupo');
        $codigo = $request->query('codigo');   // 👈 nuevo

        $orders = $this->listAllOrdersUseCase->execute($status, $grupo, $codigo);

        return response()->json(['success' => true, 'data' => $orders], 200);
    }
}