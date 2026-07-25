<?php

namespace App\Application\UseCases\Order;

use App\Application\Abstractions\Order\IUpdateOrderStatusUseCase;
use App\Application\DTOs\Order\OrderDTO;
use App\Application\DTOs\Order\OrderDetailDTO;
use App\Domain\Abstractions\IOrderRepository;
use App\Domain\Abstractions\IOrderDetailRepository;
use App\Domain\Enum\OrderStatus;
use Exception;

class UpdateOrderStatusUseCase implements IUpdateOrderStatusUseCase
{
    public function __construct(
        private IOrderRepository $orderRepository,
        private IOrderDetailRepository $orderDetailRepository
    ) {}

    public function execute(int $orderId, string $newStatus): OrderDTO
    {
        // Validamos que sea un valor válido del enum antes de tocar la BD
        $statusEnum = OrderStatus::tryFrom($newStatus);
        if (!$statusEnum) {
            throw new Exception('Estado de orden inválido.');
        }

        $order = $this->orderRepository->updateStatus($orderId, $statusEnum->value);
        $rows = $this->orderDetailRepository->findByOrderIdWithProductInfo($orderId);

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
    }
}