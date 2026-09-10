<?php
namespace App\Application\UseCases\Auction;

use App\Application\Abstractions\Auction\IGetMyActiveBidsCountUseCase;
use App\Domain\Abstractions\IAuctionBidRepository;

class GetMyActiveBidsCountUseCase implements IGetMyActiveBidsCountUseCase
{
    public function __construct(private IAuctionBidRepository $bidRepo) {}

    public function execute(int $userId): int
    {
        return $this->bidRepo->countDistinctActiveAuctionsForUser($userId);
    }
}