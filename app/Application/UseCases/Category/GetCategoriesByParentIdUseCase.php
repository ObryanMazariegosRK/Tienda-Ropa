<?php

namespace App\Application\UseCases\Category;

use App\Application\Abstractions\Category\IGetCategoriesByParentIdUseCase;
use App\Application\DTOs\Category\CategoryDTO;
use App\Domain\Abstractions\ICategoryRepository;

class GetCategoriesByParentIdUseCase implements IGetCategoriesByParentIdUseCase
{
    public function __construct(
        private ICategoryRepository $categoryRepository
    ) {}

    public function execute(?int $parentId): array
    {
        //Traemos las entidades del repositorio
        $categories = $this->categoryRepository->findByParentId($parentId);

        //Preparamos nuestro arreglo de salida
        $categoriesDTO = [];

        //Mapeamos cada entidad a su respectivo DTO
        foreach ($categories as $category) {
            $categoriesDTO[] = new CategoryDTO(
                $category->getId(),
                $category->getName(),
                $category->getDescription(),
                $category->getParentCategoryId(),
                $category->getSlug(),
                $category->isActive()
            );
        }

        return $categoriesDTO;
    }
}