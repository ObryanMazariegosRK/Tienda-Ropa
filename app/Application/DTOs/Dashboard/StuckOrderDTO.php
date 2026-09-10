<?php
namespace App\Application\DTOs\Dashboard;

class StuckOrderDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $status,
        public readonly int $daysSinceUpdate
    ) {}
}