<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BITÁCORA (base tienda_dw): quién hizo qué y cuándo. Solo se agregan filas;
// los triggers de la migración 000005 rechazan cualquier UPDATE o DELETE.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->dateTime('ocurrido_en')->index();          // UTC
            $table->unsignedBigInteger('usuario_id')->nullable()->index();
            $table->string('usuario_correo', 150)->nullable();
            $table->string('usuario_rol', 20)->nullable();
            $table->string('accion', 60)->index();              // ej. pedido.estado_cambiado
            $table->string('entidad', 40)->nullable();          // pedido, producto, categoria...
            $table->string('entidad_id', 40)->nullable();
            $table->string('descripcion', 500);
            $table->string('resultado', 20);                    // exito | rechazado
            $table->json('datos_antes')->nullable();
            $table->json('datos_despues')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
