<?php

namespace App\Application\Abstractions\Product;

use App\Application\DTOs\Product\SaveProductDTO;
use App\Application\DTOs\Product\ProductDTO;

interface ISaveProductUseCase
{
    public function execute(SaveProductDTO $dto): ProductDTO;
}