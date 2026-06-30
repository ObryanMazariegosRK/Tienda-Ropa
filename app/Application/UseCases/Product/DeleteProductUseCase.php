<?php

namespace App\Application\UseCases\Product;

use App\Application\Abstractions\Product\IDeleteProductUseCase;
use App\Domain\Abstractions\IProductRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException; 

class DeleteProductUseCase implements IDeleteProductUseCase
{
    public function __construct(
        private IProductRepository $productRepository
    ) {}

    public function execute(int $id): void
    {
        
        $product = $this->productRepository->findById($id);

        if (!$product) {
            
            throw new NotFoundHttpException("El producto con ID {$id} 
            no se encontró para ser eliminado.");
        }

        $this->productRepository->delete($id);
    }
}