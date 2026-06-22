<?php

namespace App\Domain\Entitis;

use InvalidArgumentException;

class OrderDetail
{
    private ?int $id;
    private int $orderId;
    private int $productId;
    private int $quantity;
    private float $unitPrice;

    public function __construct(
        ?int $id,
        int $orderId,
        int $productId,
        int $quantity,
        float $unitPrice
    ) {
        $this->validateOrderId($orderId);
        $this->validateProductId($productId);
        $this->validateQuantity($quantity);
        $this->validateUnitPrice($unitPrice);

        $this->id = $id;
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
    }

    private function validateOrderId(int $orderId): void
    {
        if ($orderId <= 0) {
            throw new InvalidArgumentException(
                'El pedido es obligatorio.'
            );
        }
    }

    private function validateProductId(int $productId): void
    {
        if ($productId <= 0) {
            throw new InvalidArgumentException(
                'El producto es obligatorio.'
            );
        }
    }

    private function validateQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException(
                'La cantidad debe ser mayor a cero.'
            );
        }
    }

    private function validateUnitPrice(float $unitPrice): void
    {
        if ($unitPrice <= 0) {
            throw new InvalidArgumentException(
                'El precio debe ser mayor a cero.'
            );
        }
    }

    public function getSubtotal(): float
    {
        return $this->quantity * $this->unitPrice;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }
}