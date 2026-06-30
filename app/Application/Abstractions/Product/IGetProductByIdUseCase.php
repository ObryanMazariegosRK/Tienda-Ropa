<?php

namespace App\Application\Abstractions\Product;
use App\Application\DTOs\Product\ProductDTO;

interface IGetProductByIdUseCase{

    public function execute(int $id): ?ProductDTO;

}