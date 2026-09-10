<?php
namespace App\Application\UseCases\Auction;

use App\Application\Abstractions\Auction\IDeclineAuctionWinUseCase;
use App\Application\Services\AuctionReassignmentService;
use App\Domain\Abstractions\IAuctionRepository;
use App\Domain\Abstractions\IAuctionBidRepository;
use App\Domain\Abstractions\IAuctionDeclineRepository;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Enum\ProductStatus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeclineAuctionWinUseCase implements IDeclineAuctionWinUseCase
{

    public function __construct(
        private IAuctionRepository $auctionRepo,
        private AuctionReassignmentService $reassignmentService
    ) {}

    public function execute(int $auctionId, int $userId): void
    {
        $auction = $this->auctionRepo->findById($auctionId);
        if (!$auction) throw new InvalidArgumentException('Subasta no encontrada.');

        if ($auction->getWinnerUserId() !== $userId) {
            throw new InvalidArgumentException('No eres el ganador de esta subasta.');
        }
        if ($auction->hasBeenOrdered()) {
            throw new InvalidArgumentException('Ya confirmaste este pedido; no se puede cancelar desde aquí.');
        }

        DB::transaction(fn() => $this->reassignmentService->reassignExcluding($auction, $userId));
    }

    
}