<?php

namespace App\Application\UseCases\Category;


use App\Application\Abstractions\Category\IUpdateCategoryUseCase;

use App\Application\DTOs\Category\CategoryDTO;
use App\Application\DTOs\Category\UpdateCategoryDTO;
use App\Domain\Abstractions\ICategoryRepository;
use App\Domain\Entities\Category; 
//Herramienta de Laravel para el slug
use Illuminate\Support\Str; 

class UpdateCategoryUseCase implements IUpdateCategoryUseCase
{
    public function __construct(
        private ICategoryRepository $categoryRepository
    ) {}




    public function execute(UpdateCategoryDTO $dto): CategoryDTO
    {

        $existeCategoria = $this->categoryRepository->findById($dto->id);

        //Si es null, el ! lo convierte en true y entra al if
        if (!$existeCategoria) {
            //Detenemos la ejecución y lanzamos un error
            throw new \Exception("La categoría con el ID {$dto->id} no existe.");
        }

        //Generamos el slug a partir del nombre que viene en el DTO
        $slugGenerado = Str::slug($dto->name);

        //Fabricamos la Entidad de Dominio 
        $categoryEntity = new Category(
            $dto->id, 
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