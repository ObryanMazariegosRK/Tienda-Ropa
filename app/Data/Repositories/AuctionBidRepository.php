<?php
namespace App\Data\Repositories;

use App\Domain\Abstractions\IAuctionBidRepository;
use App\Domain\Entities\AuctionBid;
use App\Models\AuctionBidModel;
use DateTimeImmutable;

class AuctionBidRepository implements IAuctionBidRepository
{
    public function create(AuctionBid $bid): AuctionBid
    {
        $model = AuctionBidModel::create([
            'auction_id' => $bid->getAuctionId(),
            'user_id' => $bid->getUserId(),
            'amount' => $bid->getAmount(),
        ]);
        return $this->mapToDomain($model);
    }

    public function findByAuctionId(int $auctionId): array
    {
        $models = AuctionBidModel::with('user')->where('auction_id', $auctionId)->orderByDesc('created_at')->limit(20)->get();
        return $models->map(fn($m) => $this->mapToDomain($m))->toArray();
    }

    private function mapToDomain(AuctionBidModel $model): AuctionBid
    {
        return new AuctionBid(
            id: $model->id,
            auctionId: $model->auction_id,
            userId: $model->user_id,
            amount: (float) $model->amount,
            createdAt: new DateTimeImmutable($model->created_at)
        );
    }

    public function findHighestBidExcluding(int $auctionId, array $excludedUserIds): ?AuctionBid
    {
        $model = AuctionBidModel::where('auction_id', $auctionId)
            ->whereNotIn('user_id', $excludedUserIds)
            ->orderByDesc('amount')
            ->first();
        return $model ? $this->mapToDomain($model) : null;
    }
    public function countDistinctActiveAuctionsForUser(int $userId): int
    {
        return AuctionBidModel::where('user_id', $userId)
            ->whereHas('auction', fn($q) => $q->where('status', 'active'))
            ->distinct('auction_id')
            ->count('auction_id');
    }
}