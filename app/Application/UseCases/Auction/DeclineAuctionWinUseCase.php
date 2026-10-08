<?php
namespace App\Application\UseCases\Auction;

use App\Application\Abstractions\Auction\IDeclineAuctionWinUseCase;
use App\Application\Services\AuctionReassignmentService;
use App\Domain\Abstractions\IAuctionRepository;
use App\Domain\Abstractions\IAuctionBidRepository;
use App\Domain\Abstractions\IAuctionDeclineRepository;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Abstractions\IPushNotifier;
use App\Domain\Enum\ProductStatus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeclineAuctionWinUseCase implements IDeclineAuctionWinUseCase
{

    public function __construct(
        private IAuctionRepository $auctionRepo,
        private AuctionReassignmentService $reassignmentService,
        private IPushNotifier $pushNotifier
    ) {}

    public function execute(int $auctionId, int $userId): void
    {
        // Validamos con la fila bloqueada para no chocar con un checkout
        // o con la expiración automática que ocurran al mismo tiempo
        $nuevoGanadorId = DB::transaction(function () use ($auctionId, $userId) {
            $auction = $this->auctionRepo->findByIdForUpdate($auctionId);
            if (!$auction) throw new InvalidArgumentException('Subasta no encontrada.');

            if ($auction->getWinnerUserId() !== $userId) {
                throw new InvalidArgumentException('No eres el ganador de esta subasta.');
            }
            if ($auction->hasBeenOrdered()) {
                throw new InvalidArgumentException('Ya confirmaste este pedido; no se puede cancelar desde aquí.');
            }

            return $this->reassignmentService->reassignExcluding($auction, $userId);
        });

        // 🔔 Si hubo un siguiente postor, él es el nuevo ganador
        if ($nuevoGanadorId) {
            $this->pushNotifier->notificarSubastaGanada($nuevoGanadorId);
        }        

        
    }

    
}