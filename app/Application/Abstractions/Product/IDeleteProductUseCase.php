<?php

namespace App\Application\Abstractions\Product;

interface IDeleteProductUseCase
{
   
    public function execute(int $id): void;
}