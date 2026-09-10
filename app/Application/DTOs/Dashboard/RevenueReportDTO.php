<?php
namespace App\Application\DTOs\Dashboard;

class RevenueReportDTO
{
    public function __construct(
        public readonly float $totalRevenue,
        public readonly float $totalProfit,
        public readonly float $previousPeriodRevenue,
        public readonly float $percentChange,
        public readonly array $dailyBreakdown // [{date, revenue}]
    ) {}
}