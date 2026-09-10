<?php
namespace App\Application\Abstractions\Product;
use App\Application\DTOs\Product\AdminProductDTO;
use App\Application\DTOs\Product\UpdateProductDTO;

interface IUpdateProductUseCase
{
    public function execute(UpdateProductDTO $dto): AdminProductDTO;
}