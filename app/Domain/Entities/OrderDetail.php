<?php
namespace App\Domain\Entities;

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
        float $unitPrice,
        int $quantity = 1 
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

    //VALIDACIONES

    private function validateOrderId(int $orderId): void
    {
        if ($orderId <= 0) {
            throw new InvalidArgumentException('El pedido es obligatorio.');
        }
    }

    private function validateProductId(int $productId): void
    {
        if ($productId <= 0) {
            throw new InvalidArgumentException('El producto es obligatorio.');
        }
    }

    private function validateQuantity(int $quantity): void
    {
        
        if ($quantity !== 1) {
            throw new InvalidArgumentException('Solo se puede comprar 1 unidad de este producto porque es una pieza única.');
        }
    }

    private function validateUnitPrice(float $unitPrice): void
    {
        if ($unitPrice <= 0) {
            throw new InvalidArgumentException('El precio debe ser mayor a cero.');
        }
    }

    //METODOS DE NEGOCIO

    public function getSubtotal(): float
    {
         
        return $this->quantity * $this->unitPrice; 
    }

    //GETTERS

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