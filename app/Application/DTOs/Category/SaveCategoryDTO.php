<?php

namespace App\Application\DTOs\Category;

class SaveCategoryDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly ?int $parentCategoryId,
        public readonly bool $isActive
    ) {}
}