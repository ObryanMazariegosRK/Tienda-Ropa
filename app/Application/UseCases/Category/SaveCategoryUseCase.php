<?php

namespace App\Application\UseCases\Category;

use App\Application\Abstractions\Category\ISaveCategoryUseCase;
use App\Application\DTOs\Category\SaveCategoryDTO;
use App\Application\DTOs\Category\CategoryDTO;
use App\Domain\Abstractions\ICategoryRepository;
use App\Domain\Entities\Category; 
//Herramienta de Laravel para el slug
use Illuminate\Support\Str;
use App\Domain\Exceptions\BusinessRuleException;
use Exception; 

class SaveCategoryUseCase implements ISaveCategoryUseCase
{
    public function __construct(
        private ICategoryRepository $categoryRepository
    ) {}

    public function execute(SaveCategoryDTO $dto): CategoryDTO
    {
        // Evitamos dos categorías/subcategorías con el mismo nombre
        // dentro del mismo nivel (mismo padre, o ambas sin padre).
        $hermanas = $this->categoryRepository->findByParentId($dto->parentCategoryId);
        foreach ($hermanas as $hermana) {
            if (mb_strtolower(trim($hermana->getName())) === mb_strtolower(trim($dto->name))) {
                throw new BusinessRuleException("Ya existe una categoría llamada \"{$dto->name}\" en este mismo nivel. Usa otro nombre.");
            }
        }

        $textoParaSlug = $dto->name;

        if ($dto->parentCategoryId !== null) {
            $parentCategory = $this->categoryRepository->findById($dto->parentCategoryId);
            if ($parentCategory) {
                $textoParaSlug = $parentCategory->getName() . ' ' . $dto->name;
            }
        }

        $slugGenerado = Str::slug($textoParaSlug);

        $categoryEntity = new Category(
            null,
            $dto->name,
            $dto->description,
            $dto->parentCategoryId,
            $slugGenerado,
            $dto->isActive
        );

        $savedEntity = $this->categoryRepository->save($categoryEntity);

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

