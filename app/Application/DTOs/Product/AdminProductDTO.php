<?php

namespace App\Application\DTOs\Product;

class AdminProductDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $categoryId,
        public readonly string $name,
        public readonly string $description,
        public readonly string $slug,
        public readonly float $price,
        public readonly ?float $offerPrice,
        public readonly ?float $cost,
        public readonly string $saleType,
        public readonly string $status,
        public readonly ?array $images
    ) {}
}