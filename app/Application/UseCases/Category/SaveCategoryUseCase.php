<?php

namespace App\Application\UseCases\Category;

use App\Application\Abstractions\Category\ISaveCategoryUseCase;
use App\Application\DTOs\Category\SaveCategoryDTO;
use App\Application\DTOs\Category\CategoryDTO;
use App\Domain\Abstractions\ICategoryRepository;
use App\Domain\Entities\Category; 
//Herramienta de Laravel para el slug
use Illuminate\Support\Str; 

class SaveCategoryUseCase implements ISaveCategoryUseCase
{
    public function __construct(
        private ICategoryRepository $categoryRepository
    ) {}

    public function execute(SaveCategoryDTO $dto): CategoryDTO
    {
        //Generamos el slug a partir del nombre que viene en el DTO
        $slugGenerado = Str::slug($dto->name);

        //Fabricamos la Entidad de Dominio 
        $categoryEntity = new Category(
            null, // El ID es nulo porque es nueva
            $dto->name,
            $dto->description,
            $dto->parentCategoryId,
            $slugGenerado, 
            $dto->isActive
        );

        //Mandamos la entidad al repositorio 
        $savedEntity = $this->categoryRepository->save($categoryEntity);

        //Mapeamos la entidad guardada
        return new CategoryDTO(
            $savedEntity->getId(), 
            $savedEntity->getName(),
            $savedEntity->getDescription(),
            $savedEntity->getParentCategoryId(),
            $savedEntity->getSlug(),
            $savedEntity->isActive()
        );
    }
}