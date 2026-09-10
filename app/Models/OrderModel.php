<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    protected $table = 'orders';
    protected $fillable = ['user_id', 'address_id', 'shipping_address', 'total', 'status', 'confirmed_at'];

    public function details()
    {
        return $this->hasMany(OrderDetailModel::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

}