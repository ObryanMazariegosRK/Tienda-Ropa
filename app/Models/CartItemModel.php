<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItemModel extends Model
{
    protected $table = 'cart_items';

    protected $fillable = ['user_id', 'product_id', 'price_snapshot'];

    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}