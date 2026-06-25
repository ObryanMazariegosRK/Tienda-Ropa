<?php

namespace App\Application\Abstractions\Category;

interface IDeleteCategoryUseCase
{
    public function execute(int $id): void;
}