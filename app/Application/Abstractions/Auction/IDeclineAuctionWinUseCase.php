<?php

namespace App\Application\Abstractions\Auction;

interface IDeclineAuctionWinUseCase
{
    public function execute(int $auctionId, int $userId): void;
}