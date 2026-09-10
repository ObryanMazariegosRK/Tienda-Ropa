<?php

namespace App\Application\Abstractions\Auction;

interface IGetMyWonAuctionsUseCase{

    public function execute (int $userId): array;

}