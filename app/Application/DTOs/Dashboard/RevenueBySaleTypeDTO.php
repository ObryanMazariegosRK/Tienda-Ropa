<?php
namespace App\Application\DTOs\Dashboard;

class RevenueBySaleTypeDTO
{
    public function __construct(
        public readonly float $directRevenue,
        public readonly float $auctionRevenue
    ) {}
}