<?php

namespace App\Application\UseCases\Order;

use App\Application\Abstractions\Order\IListAllOrdersUseCase;
use App\Application\DTOs\Order\OrderDTO;
use App\Application\DTOs\Order\OrderDetailDTO;
use App\Domain\Abstractions\IOrderRepository;
use App\Domain\Abstractions\IOrderDetailRepository;

class ListAllOrdersUseCase implements IListAllOrdersUseCase
{
    public function __construct(
        private IOrderRepository $orderRepository,
        private IOrderDetailRepository $orderDetailRepository
    ) {}

    public function execute(?string $status = null): array
    {
        $orders = $this->orderRepository->findAll($status);

        return array_map(function ($order) {
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
                items: $items
            );
        }, $orders);
    }
}