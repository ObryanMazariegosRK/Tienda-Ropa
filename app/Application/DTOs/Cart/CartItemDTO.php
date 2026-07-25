<?php

namespace App\Application\DTOs\Cart;

class CartItemDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly string $productName,
        public readonly ?string $productImage,
        public readonly float $price,
        public readonly ?float $originalPrice
    ) {}
}