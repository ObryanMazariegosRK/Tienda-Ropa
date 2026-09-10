<?php

namespace App\Console\Commands;

use App\Domain\Abstractions\IAuctionRepository;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Enum\ProductStatus;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

class CloseExpiredAuctions extends Command
{
    protected $signature = 'auctions:close-expired';
    protected $description = 'Finaliza subastas activas cuya fecha de fin ya pasó, y reserva el producto para el ganador (si hubo pujas).';

    public function handle(IAuctionRepository $auctionRepo, IProductRepository $productRepo): int
    {
        $expired = $auctionRepo->findExpiredActive();

        foreach ($expired as $auction) {
            $auction->finish();
            $auctionRepo->update($auction);

            // Si hubo al menos una puja, el producto queda reservado esperando
            // que el ganador complete el pago vía WhatsApp (mismo flujo que checkout normal).
            // Si nadie pujó, el producto vuelve a estar disponible.
            if ($auction->hasWinner()) {
                $productRepo->updateStatus($auction->getProductId(), ProductStatus::RESERVED->value);
            } else {
                $productRepo->updateStatus($auction->getProductId(), ProductStatus::AVAILABLE->value);
                $productRepo->updateSaleType($auction->getProductId(), 'direct');
            }

            $this->info("Subasta #{$auction->getId()} finalizada.");
        }

        $this->info(count($expired) . ' subasta(s) procesada(s).');
        return self::SUCCESS;
    }
}
