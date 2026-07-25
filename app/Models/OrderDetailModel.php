<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetailModel extends Model
{
    protected $table = 'order_items';
    protected $fillable = ['order_id', 'product_id', 'quantity', 'unit_price'];

    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}