<?php

namespace App\Models;

use App\Domain\Enum\RoleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    //Sirve para crear varios registros falsos pero realistas en la base de datos, son datos de prueba.
    //Notifiable permite usar el sistema nativo de Laravel para correos
    use HasFactory, Notifiable;

    use HasApiTokens;

    //Indicamos explícitamente que se conecte con la tabla users de mysql
    protected $table = 'users';

    //Los campos que permitiremos llenar de manera masiva (fillable=rellenable)
    protected $fillable = [
        'name',
        'last_name',  
        'email',
        'password',
        'phone',     
        'role',   
        'email_verified_at',       
        'verification_code',           
        'verification_code_expires_at',   
    ];

    //Ocultamos campos sensibles para que no se muestren si imprimimos el modelo
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];

    //Transformamos los datos para que php los utilice con casts=repartos
    protected $casts = [
        'email_verified_at' => 'datetime',
        //Para tratarlo como fecha
        'verification_code_expires_at' => 'datetime',
        /**
         * En teoria, el password ya viene encriptado desde el caso de uso,
         * pero lo ponemos por si acaso
         */
        'password' => 'hashed',
        //Convertimos el string de la DB en tu Enum de Dominio
        'role' => RoleType::class,
    ];
}