<?php

namespace App\Domain\Entities;

class CartItem
{
    public function __construct(
        private ?int $id,
        private int $userId,
        private int $productId,
        private float $priceSnapshot,
        private ?string $createdAt = null
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getProductId(): int { return $this->productId; }
    public function getPriceSnapshot(): float { return $this->priceSnapshot; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
}