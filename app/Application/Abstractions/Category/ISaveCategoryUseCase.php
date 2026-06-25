<?php

namespace App\Application\Abstractions\Category;
// El DTO que entra
use App\Application\DTOs\Category\SaveCategoryDTO; 
// El DTO que sale
use App\Application\DTOs\Category\CategoryDTO;     

interface ISaveCategoryUseCase
{
    //Recibe los datos de guardado y retorna la categoría completa con su ID
    public function execute(SaveCategoryDTO $dto): CategoryDTO;
}