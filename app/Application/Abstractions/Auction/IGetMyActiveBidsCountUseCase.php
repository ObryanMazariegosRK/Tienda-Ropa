<?php
namespace App\Application\Abstractions\Auction;

interface IGetMyActiveBidsCountUseCase {
    public function execute(int $userId): int;
}