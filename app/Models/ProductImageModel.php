<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImageModel extends Model
{
    use HasFactory;

    //indicamos tabla de la base de datos va a usar
    protected $table = 'product_images'; 

    //Permitimos que estas columnas se puedan llenar con datos
    protected $fillable = [
        'product_id',
        'image_url',
    ];

    
    public $timestamps = false; 

    //La relación inversa "imagen pertenece a un Producto"
    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}