<?php
namespace App\Application\DTOs\Auction;

class AuctionDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly float $currentPrice,
        public readonly float $minIncrement,
        public readonly string $status,
        public readonly string $endDate,
        public readonly ?int $currentWinnerUserId,
        public readonly int $secondsRemaining
    ) {}
}