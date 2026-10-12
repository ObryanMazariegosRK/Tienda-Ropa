<?php
namespace App\Application\UseCases\Auction;

use App\Application\Abstractions\Auction\IGetAuctionStatusUseCase;
use App\Application\DTOs\Auction\AuctionDTO;
use App\Domain\Abstractions\IAuctionRepository;

class GetAuctionStatusUseCase implements IGetAuctionStatusUseCase
{
    public function __construct(private IAuctionRepository $repo) {}

    public function execute(int $productId): ?AuctionDTO
    {
        $auction = $this->repo->findByProductId($productId);
        return $auction ? AuctionDTO::fromEntity($auction) : null;
    }
}