<?php

namespace App\Application\DTOs\Product;

class SaveProductDTO
{
    public function __construct(
        public readonly int $categoryId,
        public readonly string $name,
        public readonly string $description,
        public readonly float $price,
        public readonly ?float $offerPrice,
        public readonly string $saleType,
        public readonly string $status
    ) {}
}