<?php
namespace App\Application\Abstractions\Auction;

interface IListAuctionBidsUseCase {
    public function execute(int $productId): array;
}