<?php

namespace App\Application\UseCases\Category;

use App\Application\Abstractions\Category\IDeleteCategoryUseCase;
use App\Domain\Abstractions\ICategoryRepository;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Exceptions\BusinessRuleException;
use Exception;

class DeleteCategoryUseCase implements IDeleteCategoryUseCase
{
    public function __construct(
        private ICategoryRepository $categoryRepository,
        private IProductRepository $productRepository
    ) {}

    public function execute(int $id): void
    {
        $category = $this->categoryRepository->findById($id);

        if (!$category) {
            throw new Exception("La categoría que intentas eliminar no existe.");
        }

        $subcategorias = $this->categoryRepository->findByParentId($id);
        if (count($subcategorias) > 0) {
            throw new BusinessRuleException("No se puede eliminar: esta categoría tiene " . count($subcategorias) . " subcategoría(s) asociada(s). Elimínalas primero.");
        }

        $productos = $this->productRepository->findByCategoryId($id);
        if (count($productos) > 0) {
            throw new BusinessRuleException("No se puede eliminar: esta categoría tiene " . count($productos) . " producto(s) asociado(s).");
        }

        $this->categoryRepository->delete($id);
    }
}