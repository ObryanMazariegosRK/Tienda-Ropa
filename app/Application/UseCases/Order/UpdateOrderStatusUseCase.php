<?php

namespace App\Application\UseCases\Order;

use App\Application\Abstractions\Order\IUpdateOrderStatusUseCase;
use App\Application\DTOs\Order\OrderDTO;
use App\Application\DTOs\Order\OrderDetailDTO;
use App\Domain\Abstractions\IAuctionRepository;
use App\Domain\Abstractions\IOrderRepository;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Abstractions\IOrderDetailRepository;
use App\Domain\Enum\OrderStatus;
use App\Domain\Enum\ProductStatus;
use Exception;

class UpdateOrderStatusUseCase implements IUpdateOrderStatusUseCase
{
    //Mapeo fijo: estado de la orden -> estado que deben tener sus productos.
    private const PRODUCT_STATUS_MAP = [
        'pending_payment' => ProductStatus::RESERVED,
        'confirmed'        => ProductStatus::SOLD,
        'preparing'        => ProductStatus::SOLD,
        'on_route'         => ProductStatus::SOLD,
        'delivered'        => ProductStatus::SOLD,
        'cancelled'        => ProductStatus::AVAILABLE,
    ];

    public function __construct(
        private IOrderRepository $orderRepository,
        private IOrderDetailRepository $orderDetailRepository,
        private IProductRepository $productRepository,
        private IAuctionRepository $auctionRepository
    ) {}

    public function execute(int $orderId, string $newStatus): OrderDTO
    {
        $statusEnum = OrderStatus::tryFrom($newStatus);
        if (!$statusEnum) {
            throw new Exception('Estado de orden inválido.');
        }

        $currentOrder = $this->orderRepository->findById($orderId);
        if (!$currentOrder) {
            throw new Exception('Orden no encontrada.');
        }

        // Estados finales: una vez aquí, el pedido no se puede mover a ningún otro lado.
        if (in_array($currentOrder->getStatus(), [OrderStatus::CANCELLED, OrderStatus::DELIVERED], true)) {
            $nombreEstado = $currentOrder->getStatus() === OrderStatus::CANCELLED ? 'cancelado' : 'entregado';
            throw new Exception("Este pedido ya está {$nombreEstado} y no se puede modificar.");
        }

        $order = $this->orderRepository->updateStatus($orderId, $statusEnum->value);

        $rows = $this->orderDetailRepository->findByOrderIdWithProductInfo($orderId);
        $productStatus = self::PRODUCT_STATUS_MAP[$statusEnum->value];

        foreach ($rows as $row) {
            $this->productRepository->updateStatus($row['detail']->getProductId(), $productStatus->value);

            if ($statusEnum === OrderStatus::CANCELLED) {
                $auction = $this->auctionRepository->findByOrderId($orderId);
                if ($auction) {
                    $this->productRepository->updateSaleType($row['detail']->getProductId(), 'direct');
                }
            }
        }

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
    }
}