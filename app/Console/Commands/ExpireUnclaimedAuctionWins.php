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
                // Releemos con bloqueo: el ganador pudo confirmar o rechazar justo ahora
                $resultado = DB::transaction(function () use ($auction, $auctionRepo, $reassignmentService) {
                    $locked = $auctionRepo->findByIdForUpdate($auction->getId());
                    if (!$locked || !$locked->hasWinner() || $locked->hasBeenOrdered()
                        || !$locked->hasWinConfirmationExpired(self::DIAS_LIMITE)) {
                        return ['procesada' => false];
                    }

                    return [
                        'procesada' => true,
                        'nuevoGanadorId' => $reassignmentService->reassignExcluding($locked, $locked->getWinnerUserId()),
                    ];
                });

                if (!$resultado['procesada']) {
                    continue;
                }
                $nuevoGanadorId = $resultado['nuevoGanadorId'];

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