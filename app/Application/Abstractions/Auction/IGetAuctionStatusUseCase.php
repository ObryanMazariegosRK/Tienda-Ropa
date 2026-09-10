<?php
namespace App\Application\Abstractions\Auction;
use App\Application\DTOs\Auction\AuctionDTO;

interface IGetAuctionStatusUseCase {
    public function execute(int $productId): ?AuctionDTO;
}