<?php
namespace App\Domain\Abstractions;

interface IAuctionDeclineRepository
{
    public function record(int $auctionId, int $userId): void;
    public function getDeclinedUserIds(int $auctionId): array;
}