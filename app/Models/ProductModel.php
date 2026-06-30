<?php

namespace App\Models;

use App\Domain\Enum\ProductSaleType;
use App\Domain\Enum\ProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductModel extends Model{
    //Sirve para crear varios registros falsos pero realistas 
    //en la base de datos, son datos de prueba
    use HasFactory;

    //Indicamos explícitamente que se conecte con la tabla products de mysql
    protected $table='products';

    //Los campos que permitiremos llenar de manera masiva (fillable=rellenable)
    protected $fillable =[
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'offer_price',
        'sale_type',
        'status',
    ];

    //Transformamos los datos para que php los utilice con 
    //cacts=repartos
    protected $casts=[
        'price'=>'float',
        'offer_price'=>'float',
        //convertimos el string de la DB en un Enum de Dominio
        'sale_type'=> ProductSaleType::class,
        'status'=> ProductStatus::class,
    ];
    
    /**
     * Añadimos la relacion de un producto pertenece a una categoria
     */
    public function category(): BelongsTo{
        return $this->belongsTo(Category::class, 'category_id');
    }


}