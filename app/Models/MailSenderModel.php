<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSenderModel extends Model
{
    protected $table = 'mail_senders';
    protected $fillable = ['from_name', 'email', 'password', 'host', 'port', 'is_active', 'last_tested_at'];
    protected $hidden = ['password'];

    protected $casts = [
        // Se guarda cifrada con la APP_KEY: si la APP_KEY cambia, hay que volver a escribir la contraseña
        'password' => 'encrypted',
        'port' => 'integer',
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
    ];
}
