<?php

namespace App\Application\Abstractions\Category;
use App\Application\DTOs\Category\CategoryDTO;

interface IGetCategoriesByParentIdUseCase
{
    /**
     * @return CategoryDTO[]
     */
    public function execute(?int $parentId): array;
}