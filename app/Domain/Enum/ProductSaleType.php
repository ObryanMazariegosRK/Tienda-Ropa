<?php

namespace App\Domain\Enum;

//Definimos los tipos de venta, directa o por subasta
enum ProductSaleType:string
{
    case DIRECT='direct';
    case AUCTION='auction';
}