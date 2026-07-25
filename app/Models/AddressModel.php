<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddressModel extends Model
{
    protected $table = 'addresses';

    protected $fillable = ['user_id', 'label', 'address_line', 'is_default'];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}