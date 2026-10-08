<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    protected $table = 'orders';
    protected $fillable = ['user_id', 'address_id', 'shipping_address', 'total', 'status', 'confirmed_at', 'contact_phone', 'public_code'];

    //Se ejecuta cuando el modelo "arranca"
    protected static function boot()
    {
        parent::boot();

        //Justo antes de crear un pedido, le ponemos un código único
        static::creating(function ($order) {
            if (empty($order->public_code)) {
                $order->public_code = static::generarCodigoUnico();
            }
        });
    }

    private static function generarCodigoUnico(): string
    {
        //Caracteres sin 0/O/1/I/L para que nunca se confundan al leerlos
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        do {
            $codigo = '';
            for ($i = 0; $i < 8; $i++) {
                $codigo .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (self::where('public_code', $codigo)->exists());

        return $codigo;
    }

    public function details()
    {
        return $this->hasMany(OrderDetailModel::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}