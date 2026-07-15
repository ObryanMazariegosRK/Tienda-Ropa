<?php 

namespace App\Domain\Abstractions;
use App\Domain\Entities\Product;
use App\Domain\Entities\ProductImage;

interface IProductRepository{
    //listo xd
    public function save(Product $product):Product;
    //listo :v
    public function findById(int $id): ?Product;
    //listo :v
    public function update(Product $product): void;
    //Método para guardar las imagenes
    /**
     * @param Product $product
     * @return ProductImage[]  Ahora retornamos el array de entidades actualizadas
     */
    public function saveImagesForProduct(Product $product): array;
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

    public function findImagesByIds(array $imageIds): array;
    public function deleteImages(array $imageIds): void;

    


}
