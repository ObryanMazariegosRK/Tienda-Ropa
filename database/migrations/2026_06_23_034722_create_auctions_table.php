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
       Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('starting_price', 10, 2);
            $table->decimal('current_price', 10, 2);
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('status', 20)->default('active'); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
