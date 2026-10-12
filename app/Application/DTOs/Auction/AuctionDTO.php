<?php
namespace App\Application\DTOs\Auction;

use App\Domain\Entities\Auction;

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

    public static function fromEntity(Auction $auction): self
    {
        return new self(
            id: $auction->getId(),
            productId: $auction->getProductId(),
            currentPrice: $auction->getCurrentPrice(),
            minIncrement: $auction->getMinIncrement(),
            status: $auction->getStatus()->value,
            endDate: $auction->getEndDate()->format('Y-m-d H:i:s'),
            currentWinnerUserId: $auction->getCurrentWinnerUserId(),
            secondsRemaining: max(0, $auction->getEndDate()->getTimestamp() - time())
        );
    }
}