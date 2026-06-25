<?php

namespace App\Application\DTOs\Category;

class CategoryDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly ?int $parentCategoryId,
        public readonly string $slug,
        public readonly bool $isActive
    ) {}
}