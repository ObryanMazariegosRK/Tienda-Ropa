<?php

namespace App\Application\UseCases\Category;

use App\Application\Abstractions\Category\IDeleteCategoryUseCase;
use App\Domain\Abstractions\ICategoryRepository;
use Exception;

class DeleteCategoryUseCase implements IDeleteCategoryUseCase
{
    public function __construct(
        private ICategoryRepository $categoryRepository
    ) {}

    public function execute(int $id): void
    {
        //Verificamos si la categoría existe
        $category = $this->categoryRepository->findById($id);

        if (!$category) {
            //Detenemos el proceso si no existe
            throw new Exception("La categoría que intentas eliminar no existe.");
        }

        //Si existe, procedemos a borrarla
        $this->categoryRepository->delete($id);
    }
}