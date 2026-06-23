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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name',100);
            $table->string('description',2000);
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            //Llave foranea recurvisa, puede ser nula si la 
            //categoria es principal
            $table->foreignId('parent_category_id')
                ->nullable()
                ->contrained('categories')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
