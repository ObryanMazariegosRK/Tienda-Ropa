<?php
namespace App\Application\UseCases\Auction;

use App\Application\Abstractions\Auction\IGetAuctionStatusUseCase;
use App\Application\DTOs\Auction\AuctionDTO;
use App\Domain\Abstractions\IAuctionRepository;

class GetAuctionStatusUseCase implements IGetAuctionStatusUseCase
{
    public function __construct(private IAuctionRepository $repo) {}

    public function execute(int $productId): ?AuctionDTO
    {
        $auction = $this->repo->findByProductId($productId);
        if (!$auction) return null;

        $secondsRemaining = max(0, $auction->getEndDate()->getTimestamp() - time());

        return new AuctionDTO(
            id: $auction->getId(),
            productId: $auction->getProductId(),
            currentPrice: $auction->getCurrentPrice(),
            minIncrement: $auction->getMinIncrement(),
            status: $auction->getStatus()->value,
            endDate: $auction->getEndDate()->format('Y-m-d H:i:s'),
            currentWinnerUserId: $auction->getCurrentWinnerUserId(),
            secondsRemaining: $secondsRemaining
        );
    }
}