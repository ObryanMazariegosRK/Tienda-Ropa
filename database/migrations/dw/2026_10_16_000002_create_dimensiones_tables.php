<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// DIMENSIONES del esquema estrella: el "contexto" para agrupar los hechos.
// Producto y cliente son dimensiones de cambio lento tipo 2 SIN updates: si cambia un dato
// (ej. el nombre o la categoría) se agrega una versión nueva con su "valido_desde";
// las ventas viejas siguen apuntando a la versión que existía cuando ocurrieron.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dim_fecha', function (Blueprint $table) {
            $table->unsignedInteger('fecha_id')->primary();     // AAAAMMDD
            $table->date('fecha');
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('trimestre');
            $table->unsignedTinyInteger('mes');
            $table->string('nombre_mes', 15);
            $table->unsignedTinyInteger('dia');
            $table->unsignedTinyInteger('dia_semana');          // 1 = lunes ... 7 = domingo
            $table->string('nombre_dia', 15);
            $table->boolean('es_fin_de_semana');
        });

        Schema::create('dim_producto', function (Blueprint $table) {
            $table->id('producto_key');
            $table->unsignedBigInteger('producto_id')->index(); // id en la base de la tienda
            $table->string('nombre', 200);
            $table->string('categoria', 100);
            $table->string('categoria_principal', 100);
            $table->dateTime('publicado_en')->nullable();
            $table->dateTime('valido_desde');
        });

        Schema::create('dim_cliente', function (Blueprint $table) {
            $table->id('cliente_key');
            $table->unsignedBigInteger('usuario_id')->index();
            $table->dateTime('registrado_en')->nullable();
            $table->string('metodo_registro', 20);              // correo | google
            $table->dateTime('valido_desde');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_cliente');
        Schema::dropIfExists('dim_producto');
        Schema::dropIfExists('dim_fecha');
    }
};
