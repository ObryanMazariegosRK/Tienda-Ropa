<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\IProductRepository;
use App\Domain\Entities\Product;
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

    //Para eliminar un producto
    public function delete(int $id): void{

        ProductModel::destroy($id);

    }

    //Para obtener todos los productos
    public function findAll():array{
        //Colección, es como un array pero con poderes xd
        $models=ProductModel::all();

       //mapeamos el array para convertirlos en entidades Product
       //(fn($model)=>) es una funcion flecha, el $model representa cada 
       //producto indivisual, y la => significa que retornara el resultado que sigue
       return $models->map(fn($model)=>$this->mapToDomain($model))->toArray(); 

    }

    //Para obtener los productos por categoria
    public function findByCategoryId(int $categoryId): array
    {
        $models=ProductModel::where('category_id', $categoryId)->get();

        //mapeamos el array que vamos a devolver
        return $models->map(fn($model)=>$this->mapToDomain($model))->toArray();
        

    }

    //Convertimos un model de Eloquent en una Entidad del dominio
    private function mapToDomain(ProductModel $model):Product{

        return new Product(
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
    }

}