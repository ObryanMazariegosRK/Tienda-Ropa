<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Guardamos el costo del producto en el momento de la venta (como ya se hace con el precio).
// Así la ganancia de un mes no cambia si después se modifica el costo del producto.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->nullable()->after('unit_price');
        });

        // Ventas anteriores: el mejor dato disponible es el costo actual del producto
        DB::table('order_items')->whereNull('unit_cost')->orderBy('id')->each(function ($item) {
            $costo = DB::table('products')->where('id', $item->product_id)->value('cost');
            if ($costo !== null) {
                DB::table('order_items')->where('id', $item->id)->update(['unit_cost' => $costo]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
