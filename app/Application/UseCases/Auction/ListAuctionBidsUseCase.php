<?php
namespace App\Application\UseCases\Auction;

use App\Application\Abstractions\Auction\IListAuctionBidsUseCase;
use App\Application\DTOs\Auction\AuctionBidDTO;
use App\Domain\Abstractions\IAuctionRepository;
use App\Domain\Abstractions\IAuctionBidRepository;
use InvalidArgumentException;

class ListAuctionBidsUseCase implements IListAuctionBidsUseCase
{
    public function __construct(
        private IAuctionRepository $auctionRepo,
        private IAuctionBidRepository $bidRepo
    ) {}

    public function execute(int $productId): array
    {
        $auction = $this->auctionRepo->findByProductId($productId);
        if (!$auction) throw new InvalidArgumentException('No hay subasta para este producto.');

        // Usamos el modelo directo para traer el nombre de usuario ya cargado;
        // simplificado aquí por brevedad del repositorio de bids
        $bids = \App\Models\AuctionBidModel::with('user')
            ->where('auction_id', $auction->getId())
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return $bids->map(fn($b) => new AuctionBidDTO(
            id: $b->id,
            userName: $b->user->name ?? 'Usuario',
            amount: (float) $b->amount,
            createdAt: $b->created_at->format('Y-m-d H:i:s')
        ))->toArray();
    }
}