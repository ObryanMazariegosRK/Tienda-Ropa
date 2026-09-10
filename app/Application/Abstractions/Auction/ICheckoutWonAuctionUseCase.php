<?php
namespace App\Application\Abstractions\Auction;
use App\Application\DTOs\Order\CheckoutResultDTO;

interface ICheckoutWonAuctionUseCase {
    public function execute(int $auctionId, int $userId, int $addressId): CheckoutResultDTO;
}