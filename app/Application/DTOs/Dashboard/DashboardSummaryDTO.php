<?php
namespace App\Application\DTOs\Dashboard;

class DashboardSummaryDTO
{
    public function __construct(
        public readonly float $monthlyRevenue,
        public readonly int $pendingOrders,
        public readonly int $activeProducts,
        public readonly int $activeAuctions
    ) {}
}