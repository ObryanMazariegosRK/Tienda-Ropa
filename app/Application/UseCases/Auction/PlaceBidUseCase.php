<?php
namespace App\Application\UseCases\Auction;

use App\Application\Abstractions\Auction\IPlaceBidUseCase;
use App\Application\DTOs\Auction\AuctionDTO;
use App\Domain\Abstractions\IAuctionRepository;
use App\Domain\Abstractions\IAuctionBidRepository;
use App\Domain\Entities\AuctionBid;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PlaceBidUseCase implements IPlaceBidUseCase
{
    public function __construct(
        private IAuctionRepository $auctionRepo,
        private IAuctionBidRepository $bidRepo
    ) {}

    public function execute(int $productId, int $userId, float $amount): AuctionDTO
    {
        // Bloqueamos la fila de la subasta: si dos personas pujan al mismo tiempo,
        // la segunda espera y se valida contra el precio que dejó la primera.
        $auction = DB::transaction(function () use ($productId, $userId, $amount) {
            $auction = $this->auctionRepo->findByProductIdForUpdate($productId);
            if (!$auction) {
                throw new InvalidArgumentException('No hay una subasta activa para este producto.');
            }

            if ($auction->hasExpired()) {
                throw new InvalidArgumentException('Esta subasta ya finalizó.');
            }

            // registerBid() valida internamente: activa, no ser ya el ganador, incremento mínimo
            $auction->registerBid($userId, $amount);

            $this->auctionRepo->update($auction);
            $this->bidRepo->create(new AuctionBid(id: null, auctionId: $auction->getId(), userId: $userId, amount: $amount));

            return $auction;
        });

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