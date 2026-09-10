<?php
namespace App\Domain\Abstractions;
use App\Domain\Entities\AuctionBid;

interface IAuctionBidRepository
{
    public function create(AuctionBid $bid): AuctionBid;
    public function findByAuctionId(int $auctionId): array;
    public function findHighestBidExcluding(int $auctionId, array $excludedUserIds): ?AuctionBid;
    public function countDistinctActiveAuctionsForUser(int $userId): int;
}