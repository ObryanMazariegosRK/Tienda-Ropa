<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\IProductRepository;
use App\Domain\Entities\Product;
use App\Domain\Entities\ProductImage;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use Override;

class ProductRepository implements IProductRepository{


    //Guardamos un producto nuevo y la db le asigna un id
    public function save(Product $product): Product{
        $model= new ProductModel();

        $model->category_Id=$product->getCategoryId();
        $model->name=$product->getName();
        $model->description=$product->getDescription();
        $model->slug = $product->getSlug();
        $model->price = $product->getPrice();
        $model->offer_price = $product->getOfferPrice();

        //Gracisas a los casts podemos pasar los Enum directamente
        $model->sale_type=$product->getSaleType();
        $model->status=$product->getStatus();
        $model->save();

        //Retornamos la entidad con el Id asingado por mysql
        return $this->mapToDomain($model);

    }

    //Buscar el producto por su Id
    public function findById(int $id): ?Product
    {
        $model=ProductModel::find($id);

        if(!$model){
            return null;
        }

        return $this->mapToDomain($model);
    }

    //para actualizar
    public function update(Product $product):void{
        $model= ProductModel::findOrFail($product->getId());

        $model->category_Id=$product->getCategoryId();
        $model->name = $product->getName();
        $model->description = $product->getDescription();
        $model->slug = $product->getSlug();
        $model->price = $product->getPrice();
        $model->offer_price = $product->getOfferPrice();
        $model->sale_type = $product->getSaleType();
        $model->status = $product->getStatus();

        $model->save();
    }

    //Método para obtener imágenes por sus IDs (nos servirá para borrarlas del disco duro)
    public function findImagesByIds(array $imageIds): array
    {
        return ProductImageModel::whereIn('id', $imageIds)->get()->toArray();
    }

    //Método para eliminar imágenes de la base de datos
    public function deleteImages(array $imageIds): void
    {
        ProductImageModel::whereIn('id', $imageIds)->delete();
    }



    //Para eliminar un producto
    public function delete(int $id): void{

        ProductModel::destroy($id);

    }

    //Para obtener todos los productos
    public function findAll():array{
        //Colección, es como un array pero con poderes xd
        $models=ProductModel::with('images')->get();

       //mapeamos el array para convertirlos en entidades Product
       //(fn($model)=>) es una funcion flecha, el $model representa cada 
       //producto indivisual, y la => significa que retornara el resultado que sigue
       return $models->map(fn($model)=>$this->mapToDomain($model))->toArray(); 

    }

    //Para obtener los productos por categoria
    public function findByCategoryId(int $categoryId): array
    {
        $models=ProductModel::where('category_id', $categoryId)->with('images')->get();

        //mapeamos el array que vamos a devolver
        return $models->map(fn($model)=>$this->mapToDomain($model))->toArray();
        

    }

    //Método para guardar las imagenes en la tabla product_image
    /**
     * @param Product $product
     * @return ProductImage[]  Ahora retornamos el array de entidades actualizadas
     */
    public function saveImagesForProduct(Product $product): array
    {
        $imageEntities = $product->getImages();
        //Aquí guardaremos las entidades ya con ID
        $savedImages = []; 
        
        if (empty($imageEntities)) {
            return [];
        }

        foreach ($imageEntities as $imageEntity) {
            $model = new ProductImageModel();
            
            //Le pasamos los datos
            $model->product_id = $imageEntity->getProductId();
            $model->image_url = $imageEntity->getImageUrl();
            
            $model->save(); 

            //Lo convertimos de vuelta a una Entidad de Dominio 
            $savedImages[] = $this->mapImageToDomain($model);
        }

        return $savedImages;
    }

    // Método de ayuda dentro del Repositorio
    private function mapImageToDomain(ProductImageModel $model): ProductImage 
    {
        return new ProductImage(
            //Una vez ya obtenido el id de la db...
            id: $model->id, 
            productId: $model->product_id,
            imageUrl: $model->image_url
        );
    }

    //Convertimos un model de Eloquent en una Entidad del dominio
    private function mapToDomain(ProductModel $model):Product{

        $product=new  Product(
            $model->id,
            $model->category_Id,
            $model->name,
            $model->description,
            $model->slug,
            (float) $model->price,
            $model->offer_price !==null?(float) $model->offer_price: null,
            //Convertidos previamente a Enum en el archivo model
            $model->sale_type,
            $model->status
        );

        //Mapeamos las imágenes que ya vienen cargadas en el modelo de Eloquent
        //para convertirlas en entidades ProductImage
        //
        $domainImages = $model->images->map(fn($imageModel) => new ProductImage(
            id: $imageModel->id,
            productId: $imageModel->product_id,
            imageUrl: $imageModel->image_url
        ))->toArray();

        //Le pasamos el array de imágenes a la entidad Product usando el setter 
        $product->setImages($domainImages);

        return $product;
    }



}