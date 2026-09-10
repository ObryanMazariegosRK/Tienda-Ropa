<?php
namespace App\Application\DTOs\Dashboard;

class OrderStatsDTO
{
    public function __construct(
        public readonly array $statusCounts, // ['pending_payment' => 3, 'confirmed' => 5, ...]
        public readonly int $newToday,
        public readonly int $newThisWeek,
        public readonly float $cancellationRate,
        public readonly array $stuckOrders // StuckOrderDTO[]
    ) {}
}