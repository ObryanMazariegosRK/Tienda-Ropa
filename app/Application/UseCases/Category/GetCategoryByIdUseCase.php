<?php

namespace App\Application\UseCases\Category;

use App\Application\Abstractions\Category\IGetCategoryByIdUseCase;
use App\Application\DTOs\Category\CategoryDTO;
use App\Domain\Abstractions\ICategoryRepository;
use Exception;

class GetCategoryByIdUseCase implements IGetCategoryByIdUseCase
{
    public function __construct(
        private ICategoryRepository $categoryRepository
    ) {}

    public function execute(int $id): CategoryDTO
    {
        //Buscamos la categoría
        $category = $this->categoryRepository->findById($id);

        if (!$category) {
            throw new Exception("La categoría solicitada no fue encontrada.");
        }

        //Mapeamos la Entidad de Dominio al DTO de salida
        return new CategoryDTO(
            $category->getId(),
            $category->getName(),
            $category->getDescription(),
            $category->getParentCategoryId(),
            $category->getSlug(),
            $category->isActive()
        );
    }
}