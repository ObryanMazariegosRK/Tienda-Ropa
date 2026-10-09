<?php

namespace App\Application\UseCases\Order;

use App\Application\Abstractions\Order\IListMyOrdersUseCase;
use App\Application\DTOs\Order\OrderDTO;
use App\Application\DTOs\Order\OrderDetailDTO;
use App\Domain\Abstractions\IOrderRepository;
use App\Domain\Abstractions\IOrderDetailRepository;

class ListMyOrdersUseCase implements IListMyOrdersUseCase
{
    public function __construct(
        private IOrderRepository $orderRepository,
        private IOrderDetailRepository $orderDetailRepository
    ) {}

    public function execute(int $userId, int $page = 1, int $perPage = 10): array
    {
        // Paginado: el cliente carga sus pedidos por lotes con "Ver más"
        $result = $this->orderRepository->paginateByUserId($userId, $page, $perPage);

        $orders = array_map(function ($order) {
            $rows = $this->orderDetailRepository->findByOrderIdWithProductInfo($order->getId());

            $items = array_map(fn($row) => new OrderDetailDTO(
                productId: $row['detail']->getProductId(),
                productName: $row['productName'],
                productImage: $row['productImage'],
                quantity: $row['detail']->getQuantity(),
                unitPrice: $row['detail']->getUnitPrice(),
                subtotal: $row['detail']->getSubtotal()
            ), $rows);

            return new OrderDTO(
                id: $order->getId(),
                status: $order->getStatus()->value,
                total: $order->getTotal(),
                shippingAddress: $order->getShippingAddress(),
                createdAt: $order->getCreatedAt()->format('Y-m-d H:i:s'),
                items: $items,
                confirmedAt: $order->getConfirmedAt()?->format('Y-m-d H:i:s'),
            );
        }, $result['items']);

        return [
            'data' => $orders,
            'meta' => [
                'current_page' => $result['currentPage'],
                'last_page' => $result['lastPage'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
            ],
        ];
    }
}