<?php
namespace App\Application\UseCases\Auction;

use App\Application\Abstractions\Auction\IExtendAuctionDurationUseCase;
use App\Application\DTOs\Auction\AuctionDTO;
use App\Domain\Abstractions\IAuctionRepository;
use DateTimeImmutable;
use InvalidArgumentException;

class ExtendAuctionDurationUseCase implements IExtendAuctionDurationUseCase
{
    public function __construct(private IAuctionRepository $repo) {}

    public function execute(int $auctionId, string $newEndDate): AuctionDTO
    {
        $auction = $this->repo->findById($auctionId);
        if (!$auction) throw new InvalidArgumentException('Subasta no encontrada.');

        $auction->extendEndDate(new DateTimeImmutable($newEndDate));
        $updated = $this->repo->update($auction);

        $secondsRemaining = max(0, $updated->getEndDate()->getTimestamp() - time());

        return new AuctionDTO(
            id: $updated->getId(),
            productId: $updated->getProductId(),
            currentPrice: $updated->getCurrentPrice(),
            minIncrement: $updated->getMinIncrement(),
            status: $updated->getStatus()->value,
            endDate: $updated->getEndDate()->format('Y-m-d H:i:s'),
            currentWinnerUserId: $updated->getCurrentWinnerUserId(),
            secondsRemaining: $secondsRemaining
        );
    }
}