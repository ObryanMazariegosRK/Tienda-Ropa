<?php
namespace App\Application\Abstractions\Auction;
use App\Application\DTOs\Auction\AuctionDTO;

interface IGetAuctionStatusesUseCase {
    /** @return array<int, AuctionDTO> indexado por productId */
    public function execute(array $productIds): array;
}
