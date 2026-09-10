<?php
namespace App\Application\DTOs\Auction;

class AuctionBidDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $userName,
        public readonly float $amount,
        public readonly string $createdAt
    ) {}
}