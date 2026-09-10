<?php
namespace App\Application\DTOs\Dashboard;

class TopCategoryDTO
{
    public function __construct(
        public readonly int $categoryId,
        public readonly string $categoryName,
        public readonly float $revenue
    ) {}
}