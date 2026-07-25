<?php

namespace App\Application\DTOs\Order;

class OrderDetailDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly ?string $productImage,
        public readonly int $quantity,
        public readonly float $unitPrice,
        public readonly float $subtotal
    ) {}
}