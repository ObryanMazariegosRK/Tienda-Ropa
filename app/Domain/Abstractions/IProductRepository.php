<?php 

namespace App\Domain\Abstractions;
use App\Domain\Entities\Product;

interface IProductRepository{
    //listo xd
    public function save(Product $product):Product;
    //listo :v
    public function findById(int $id): ?Product;
    //listo :v
    public function update(Product $product): void;
    //listo XD
    public function delete(int $id): void;
    //Listo XD
    /**
     * @return Product[]
     */
    public function findAll(): array;

    /**
     * @return Product[]
     */
    public function findByCategoryId(int $categoryId): array;


}
