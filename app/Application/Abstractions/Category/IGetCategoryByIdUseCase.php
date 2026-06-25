<?php

namespace App\Application\Abstractions\Category;

use App\Application\DTOs\Category\CategoryDTO;

interface IGetCategoryByIdUseCase
{
    public function execute(int $id): CategoryDTO;
}