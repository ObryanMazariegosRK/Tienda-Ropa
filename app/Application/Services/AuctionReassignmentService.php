<?php
namespace App\Application\Services;

use App\Domain\Abstractions\IAuctionRepository;
use App\Domain\Abstractions\IAuctionBidRepository;
use App\Domain\Abstractions\IAuctionDeclineRepository;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Entities\Auction;
use App\Domain\Enum\ProductStatus;

class AuctionReassignmentService
{
    public function __construct(
        private IAuctionRepository $auctionRepo,
        private IAuctionBidRepository $bidRepo,
        private IAuctionDeclineRepository $declineRepo,
        private IProductRepository $productRepo
    ) {}

    // Quita al ganador actual (por rechazo o por vencimiento) y pasa al siguiente postor.
    public function reassignExcluding(Auction $auction, int $userIdAExcluir): void
    {
        $this->declineRepo->record($auction->getId(), $userIdAExcluir);

        $excluidos = $this->declineRepo->getDeclinedUserIds($auction->getId());
        $siguiente = $this->bidRepo->findHighestBidExcluding($auction->getId(), $excluidos);

        if ($siguiente) {
            $auction->reassignWinner($siguiente->getUserId(), $siguiente->getAmount());
        } else {
            $auction->clearWinner();
            $this->productRepo->updateStatus($auction->getProductId(), ProductStatus::AVAILABLE->value);
            $this->productRepo->updateSaleType($auction->getProductId(), 'direct');
        }

        $this->auctionRepo->update($auction);
    }
}