<?php

namespace App\Application\Abstractions\Category;

use App\Application\DTOs\Category\CategoryDTO; 

interface IGetCategoriesUseCase
{
    /**
     * Ejecuta el caso de uso para obtener la lista de categorías.
     * @return CategoryDTO[]
     */
    public function execute(): array;
}