<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_Id')
                  ->constrained('categories')
                  //No se puede eliminar una cateogia si tiene un producto 
                  ->restrictOnDelete();

            $table->string('name',200);
            $table->text('description');
            $table->string('slug', 250)->unique();

            $table->decimal('price', 8,2);
            $table->decimal('offer_price', 8,2)->nullable();

            $table->string('sale_type',20)->default('direct');
            $table->string('status', 20)->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
