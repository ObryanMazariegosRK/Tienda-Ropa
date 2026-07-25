<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banner_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('type', 20); // 'image' o 'video', fijo para todo el grupo
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_groups');
    }
};