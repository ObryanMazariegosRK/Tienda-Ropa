<?php
namespace App\Application\Abstractions\Auction;

interface IGetMyAuctionBidStatusUseCase {
    public function execute(int $userId): array;
}