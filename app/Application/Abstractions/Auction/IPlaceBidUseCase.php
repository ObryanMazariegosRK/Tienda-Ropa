<?php
namespace App\Application\Abstractions\Auction;
use App\Application\DTOs\Auction\AuctionDTO;

interface IPlaceBidUseCase {
    public function execute(int $productId, int $userId, float $amount): AuctionDTO;
}