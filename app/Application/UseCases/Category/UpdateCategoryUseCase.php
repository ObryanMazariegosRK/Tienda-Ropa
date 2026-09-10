<?php

namespace App\Application\UseCases\Category;


use App\Application\Abstractions\Category\IUpdateCategoryUseCase;

use App\Application\DTOs\Category\CategoryDTO;
use App\Application\DTOs\Category\UpdateCategoryDTO;
use App\Domain\Abstractions\ICategoryRepository;
use App\Domain\Entities\Category; 
//Herramienta de Laravel para el slug
use Illuminate\Support\Str; 
use App\Domain\Exceptions\BusinessRuleException;
use Exception;


class UpdateCategoryUseCase implements IUpdateCategoryUseCase
{
    public function __construct(
        private ICategoryRepository $categoryRepository
    ) {}




    public function execute(UpdateCategoryDTO $dto): CategoryDTO
    {
        $existeCategoria = $this->categoryRepository->findById($dto->id);

        if (!$existeCategoria) {
            throw new Exception("La categoría con el ID {$dto->id} no existe.");
        }

        // Mismo chequeo que al crear, pero ignorando la categoría actual
        // (si no, siempre "chocaría consigo misma" al editarla sin cambiar el nombre).
        $hermanas = $this->categoryRepository->findByParentId($dto->parentCategoryId);
        foreach ($hermanas as $hermana) {
            if ($hermana->getId() !== $dto->id && mb_strtolower(trim($hermana->getName())) === mb_strtolower(trim($dto->name))) {
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
            $dto->id,
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