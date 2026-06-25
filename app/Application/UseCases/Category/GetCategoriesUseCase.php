<?php

namespace App\Application\UseCases\Category;

use App\Application\Abstractions\Category\IGetCategoriesUseCase;
use App\Application\DTOs\Category\CategoryDTO;
use App\Domain\Abstractions\ICategoryRepository;

class GetCategoriesUseCase implements IGetCategoriesUseCase
{
    public function __construct(
        private ICategoryRepository $categoryRepository
    ) {}

    /**
     * @return CategoryDTO[]
     */
    public function execute(): array
    {
        $categories = $this->categoryRepository->getAll();

        //Convertimos las entidades a DTOs
        return array_map(function ($category) {
            return new CategoryDTO(
                $category->getId(),
                $category->getName(),
                $category->getDescription(),
                $category->getParentCategoryId(), // Getter de tu Entidad
                $category->getSlug(),             // Getter de tu Entidad
                $category->isActive()             // Getter de tu Entidad
            );
        }, $categories);
    }
}