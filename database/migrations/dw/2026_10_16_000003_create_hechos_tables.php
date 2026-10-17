<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// HECHOS del esquema estrella: los eventos medibles del negocio.
return new class extends Migration
{
    public function up(): void
    {
        // Una fila por prenda vendida (línea de pedido) cuando el pedido se confirma.
        // Si después el pedido se cancela NO se borra: se agrega una fila "anulacion" con
        // cantidad y montos negativos, así las sumas dan el neto y la historia queda completa.
        Schema::create('hechos_ventas', function (Blueprint $table) {
            $table->id('venta_key');
            $table->string('tipo_movimiento', 12);              // venta | anulacion
            $table->unsignedBigInteger('pedido_item_id');
            $table->unsignedBigInteger('pedido_id')->index();
            $table->string('pedido_codigo', 12)->nullable();
            $table->unsignedInteger('fecha_id')->index();
            $table->unsignedBigInteger('producto_key')->index();
            $table->unsignedBigInteger('cliente_key')->index();
            $table->string('tipo_venta', 10);                   // direct | auction
            $table->integer('cantidad');
            $table->decimal('ingreso', 12, 2);
            $table->decimal('costo', 12, 2)->nullable();
            $table->decimal('ganancia', 12, 2)->nullable();
            $table->unsignedInteger('dias_para_vender')->nullable();
            $table->dateTime('ocurrido_en');                    // UTC
            $table->dateTime('cargado_en');

            $table->unique(['pedido_item_id', 'tipo_movimiento']); // la carga se puede repetir sin duplicar
            $table->foreign('fecha_id')->references('fecha_id')->on('dim_fecha');
            $table->foreign('producto_key')->references('producto_key')->on('dim_producto');
            $table->foreign('cliente_key')->references('cliente_key')->on('dim_cliente');
        });

        // Una fila por subasta terminada, cuando ya se sabe su resultado final
        Schema::create('hechos_subastas', function (Blueprint $table) {
            $table->id('subasta_key');
            $table->unsignedBigInteger('subasta_id')->unique();
            $table->unsignedBigInteger('producto_key')->index();
            $table->unsignedInteger('fecha_id')->index();       // día de cierre
            $table->string('resultado', 15);                    // vendida | sin_pujas | no_reclamada | cancelada
            $table->decimal('precio_inicial', 12, 2);
            $table->decimal('precio_final', 12, 2)->nullable();
            $table->decimal('incremento_pct', 8, 2)->nullable();
            $table->unsignedInteger('total_pujas');
            $table->unsignedInteger('postores');
            $table->dateTime('cargado_en');

            $table->foreign('fecha_id')->references('fecha_id')->on('dim_fecha');
            $table->foreign('producto_key')->references('producto_key')->on('dim_producto');
        });

        // Registro de cada ejecución del ETL (también inmutable: una fila por carga)
        Schema::create('etl_cargas', function (Blueprint $table) {
            $table->id();
            $table->dateTime('iniciado_en');
            $table->dateTime('terminado_en');
            $table->string('estado', 10);                       // ok | error
            $table->unsignedInteger('ventas');
            $table->unsignedInteger('anulaciones');
            $table->unsignedInteger('subastas');
            $table->unsignedInteger('versiones_dimension');
            $table->string('mensaje', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etl_cargas');
        Schema::dropIfExists('hechos_subastas');
        Schema::dropIfExists('hechos_ventas');
    }
};
