<?php
namespace App\Application\DTOs\Auction;

class WonAuctionDTO
{
    public function __construct(
        public readonly int $auctionId,
        public readonly int $productId,
        public readonly string $productName,
        public readonly ?string $productImage,
        public readonly float $finalPrice
    ) {}
}