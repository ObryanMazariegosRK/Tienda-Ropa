<?php
namespace App\Data\Repositories;

use App\Domain\Abstractions\IAuctionRepository;
use App\Domain\Entities\Auction;
use App\Domain\Enum\AuctionStatus;
use App\Models\AuctionModel;
use DateTimeImmutable;

class AuctionRepository implements IAuctionRepository
{
    public function create(Auction $auction): Auction
    {
        $model = AuctionModel::create([
            'product_id' => $auction->getProductId(),
            'starting_price' => $auction->getStartingPrice(),
            'current_price' => $auction->getCurrentPrice(),
            'min_increment' => $auction->getMinIncrement(),
            'start_date' => $auction->getStartDate()->format('Y-m-d H:i:s'),
            'end_date' => $auction->getEndDate()->format('Y-m-d H:i:s'),
            'status' => $auction->getStatus()->value,
        ]);
        return $this->mapToDomain($model);
    }

    public function findById(int $id): ?Auction
    {
        $model = AuctionModel::find($id);
        return $model ? $this->mapToDomain($model) : null;
    }

    public function findByProductId(int $productId): ?Auction
    {
        $model = AuctionModel::where('product_id', $productId)->latest()->first();
        return $model ? $this->mapToDomain($model) : null;
    }

    public function update(Auction $auction): Auction
    {
        $model = AuctionModel::findOrFail($auction->getId());
        $model->update([
            'current_price' => $auction->getCurrentPrice(),
            'status' => $auction->getStatus()->value,
            'current_winner_user_id' => $auction->getCurrentWinnerUserId(),
            'winner_user_id' => $auction->getWinnerUserId(),
            'order_id' => $auction->getOrderId(),
            'end_date' => $auction->getEndDate()->format('Y-m-d H:i:s'), 
            'won_at' => $auction->getWonAt()?->format('Y-m-d H:i:s'),
        ]);
        return $this->mapToDomain($model->fresh());
    }

    public function findExpiredActive(): array
    {
        $models = AuctionModel::where('status', AuctionStatus::ACTIVE->value)
            ->where('end_date', '<=', now())
            ->get();
        return $models->map(fn($m) => $this->mapToDomain($m))->toArray();
    }

    private function mapToDomain(AuctionModel $model): Auction
    {
        return new Auction(
            id: $model->id,
            productId: $model->product_id,
            startingPrice: (float) $model->starting_price,
            startDate: new DateTimeImmutable($model->start_date),
            endDate: new DateTimeImmutable($model->end_date),
            status: AuctionStatus::from($model->status),
            currentWinnerUserId: $model->current_winner_user_id,
            winnerUserId: $model->winner_user_id,
            currentPrice: (float) $model->current_price,
            minIncrement: (float) $model->min_increment,
            orderId: $model->order_id,
            wonAt: $model->won_at ? new DateTimeImmutable($model->won_at) : null, 
        );
    }

    public function findUnclaimedWins(): array
    {
        $models = AuctionModel::where('status', 'finished')
            ->whereNotNull('winner_user_id')
            ->whereNull('order_id')
            ->get();
        return $models->map(fn($m) => $this->mapToDomain($m))->toArray();
    }

    public function findByOrderId(int $orderId): ?Auction
    {
        $model = AuctionModel::where('order_id', $orderId)->first();
        return $model ? $this->mapToDomain($model) : null;
    }


    
}