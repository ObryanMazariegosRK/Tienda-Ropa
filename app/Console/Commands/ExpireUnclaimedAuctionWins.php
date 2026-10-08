<?php
namespace App\Console\Commands;

use App\Application\Services\AuctionReassignmentService;
use App\Domain\Abstractions\IAuctionRepository;
use App\Domain\Abstractions\IPushNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUnclaimedAuctionWins extends Command
{
    private const DIAS_LIMITE = 3;

    protected $signature = 'auctions:expire-unclaimed-wins';
    protected $description = 'Reasigna al siguiente postor las subastas ganadas que nadie confirmó dentro del plazo permitido.';

    public function handle(IAuctionRepository $auctionRepo, AuctionReassignmentService $reassignmentService, IPushNotifier $pushNotifier): int
    {
        $pendientes = $auctionRepo->findUnclaimedWins(); // status=finished, winner_user_id no nulo, order_id nulo
        $procesadas = 0;

        foreach ($pendientes as $auction) {
            if ($auction->hasWinConfirmationExpired(self::DIAS_LIMITE)) {
                $winnerId = $auction->getWinnerUserId();

                $nuevoGanadorId = DB::transaction(
                    fn() => $reassignmentService->reassignExcluding($auction, $winnerId)
                );

                if ($nuevoGanadorId) {
                    $pushNotifier->notificarSubastaGanada($nuevoGanadorId);            // 🔔
                }

                $this->info("Subasta #{$auction->getId()}: se venció el plazo, reasignada.");
                $procesadas++;
            }            
        }

        $this->info("{$procesadas} subasta(s) reasignada(s) por vencimiento.");
        return self::SUCCESS;
    }
}