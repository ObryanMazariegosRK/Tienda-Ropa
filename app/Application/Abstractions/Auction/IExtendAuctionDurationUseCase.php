<?php
namespace App\Application\Abstractions\Auction;
use App\Application\DTOs\Auction\AuctionDTO;

interface IExtendAuctionDurationUseCase {
    public function execute(int $auctionId, string $newEndDate): AuctionDTO;
}