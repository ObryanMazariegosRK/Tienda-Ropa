<?php

namespace App\Application\Abstractions\Category;
// El DTO que entra
use App\Application\DTOs\Category\UpdateCategoryDTO; 
// El DTO que sale
use App\Application\DTOs\Category\CategoryDTO;     

interface IUpdateCategoryUseCase
{
    //Recibe los datos a actualizar y retorna la categoría completa 
    public function execute(UpdateCategoryDTO $dto): CategoryDTO;
}