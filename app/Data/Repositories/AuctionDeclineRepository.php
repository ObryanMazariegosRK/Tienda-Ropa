<?php
namespace App\Data\Repositories;

use App\Domain\Abstractions\IAuctionDeclineRepository;
use App\Models\AuctionDeclineModel;

class AuctionDeclineRepository implements IAuctionDeclineRepository
{
    public function record(int $auctionId, int $userId): void
    {
        AuctionDeclineModel::create(['auction_id' => $auctionId, 'user_id' => $userId]);
    }

    public function getDeclinedUserIds(int $auctionId): array
    {
        return AuctionDeclineModel::where('auction_id', $auctionId)->pluck('user_id')->toArray();
    }
}