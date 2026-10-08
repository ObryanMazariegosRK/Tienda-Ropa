<?php

namespace App\Application\DTOs\Order;

class OrderDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $status,
        public readonly float $total,
        public readonly string $shippingAddress,
        public readonly string $createdAt,
        public readonly array $items, // OrderDetailDTO[]
        public readonly ?string $confirmedAt = null,
        public readonly ?string $customerName = null,   
        public readonly ?string $contactPhone = null,
        public readonly ?string $publicCode = null    
    ) {}
}