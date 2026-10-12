<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cuentas de correo desde las que la tienda puede enviar los códigos (verificación y
// recuperación de contraseña). El admin escoge cuál está en uso; si ninguna lo está,
// se usa la cuenta configurada en el .env.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_senders', function (Blueprint $table) {
            $table->id();
            $table->string('from_name', 100);
            $table->string('email', 150)->unique();
            $table->text('password'); // cifrada con la APP_KEY (cast "encrypted" en el modelo)
            $table->string('host', 150)->default('smtp.gmail.com');
            $table->unsignedSmallInteger('port')->default(587);
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_senders');
    }
};
