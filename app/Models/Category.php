<?php

namespace App\Models;

//Importamos la clase base del ORM de laravel (Eloquent)
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    //Para permitir que guarde todo, no protege ninguna columna
    protected $guarded = [];
}
