<?php

namespace App\Application\DTOs\Cart;

class CartDTO
{
    public function __construct(
        public readonly array $items, // CartItemDTO[]
        public readonly float $total,
        public readonly int $itemCount
    ) {}
}