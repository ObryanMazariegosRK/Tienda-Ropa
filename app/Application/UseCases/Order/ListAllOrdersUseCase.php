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
        private IOrderDetailRepository $orderDetailRepository,
        private \App\Domain\Abstractions\User\IUserRepository $userRepository
    ) {}

    public function execute(?string $status = null, ?string $grupo = null, ?string $codigo = null): array
    {
        $orders = $this->orderRepository->findAll($status, $grupo, $codigo);

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

            // El nombre viene del usuario dueño del pedido (join lógico)
            $customer = $this->userRepository->findUserById($order->getUserId());

            return new OrderDTO(
                id: $order->getId(),
                status: $order->getStatus()->value,
                total: $order->getTotal(),
                shippingAddress: $order->getShippingAddress(),
                createdAt: $order->getCreatedAt()->format('Y-m-d H:i:s'),
                items: $items,
                confirmedAt: $order->getConfirmedAt()?->format('Y-m-d H:i:s'),
                customerName: $customer?->getFullName(),      
                contactPhone: $order->getContactPhone(),
                publicCode: $order->getPublicCode(),      
            );
        }, $orders);
    }
}