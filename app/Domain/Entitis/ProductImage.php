<?php

namespace App\Domain\Entitis;

class ProductImage{

    public int $id;
    public int $productId;
    public string $imageUrl;
    //Para ordenar las imagenes de un producto
    public int $sortOrder;

}