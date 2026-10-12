<?php
namespace App\Application\UseCases\Auction;

use App\Application\Abstractions\Auction\IGetAuctionStatusesUseCase;
use App\Application\DTOs\Auction\AuctionDTO;
use App\Domain\Abstractions\IAuctionRepository;

// Estado de varias subastas en una sola consulta (la grilla de subastas lo pide cada pocos segundos)
class GetAuctionStatusesUseCase implements IGetAuctionStatusesUseCase
{
    public function __construct(private IAuctionRepository $repo) {}

    public function execute(array $productIds): array
    {
        return array_map(
            fn($auction) => AuctionDTO::fromEntity($auction),
            $this->repo->findLatestByProductIds($productIds)
        );
    }
}
