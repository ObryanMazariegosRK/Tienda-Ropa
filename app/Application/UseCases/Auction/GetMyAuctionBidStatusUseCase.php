<?php
namespace App\Application\UseCases\Auction;

use App\Application\Abstractions\Auction\IGetMyAuctionBidStatusUseCase;
use App\Application\Abstractions\Auction\IGetMyWonAuctionsUseCase;
use App\Domain\Abstractions\IAuctionBidRepository;

class GetMyAuctionBidStatusUseCase implements IGetMyAuctionBidStatusUseCase
{
    public function __construct(
        private IGetMyWonAuctionsUseCase $getMyWonAuctions,
        private IAuctionBidRepository $bidRepo
    ) {}

    public function execute(int $userId): array
    {
        $wins = $this->getMyWonAuctions->execute($userId);
        $activeBidsCount = $this->bidRepo->countDistinctActiveAuctionsForUser($userId);

        return [
            'wins' => $wins,
            'activeBidsCount' => $activeBidsCount,
        ];
    }
}