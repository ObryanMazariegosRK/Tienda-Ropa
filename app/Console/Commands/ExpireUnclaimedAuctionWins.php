<?php
namespace App\Console\Commands;

use App\Application\Services\AuctionReassignmentService;
use App\Domain\Abstractions\IAuctionRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUnclaimedAuctionWins extends Command
{
    private const DIAS_LIMITE = 3;

    protected $signature = 'auctions:expire-unclaimed-wins';
    protected $description = 'Reasigna al siguiente postor las subastas ganadas que nadie confirmó dentro del plazo permitido.';

    public function handle(IAuctionRepository $auctionRepo, AuctionReassignmentService $reassignmentService): int
    {
        $pendientes = $auctionRepo->findUnclaimedWins(); // status=finished, winner_user_id no nulo, order_id nulo
        $procesadas = 0;

        foreach ($pendientes as $auction) {
            if ($auction->hasWinConfirmationExpired(self::DIAS_LIMITE)) {
                $winnerId = $auction->getWinnerUserId();
                DB::transaction(fn() => $reassignmentService->reassignExcluding($auction, $winnerId));
                $this->info("Subasta #{$auction->getId()}: se venció el plazo, reasignada.");
                $procesadas++;
            }
        }

        $this->info("{$procesadas} subasta(s) reasignada(s) por vencimiento.");
        return self::SUCCESS;
    }
}