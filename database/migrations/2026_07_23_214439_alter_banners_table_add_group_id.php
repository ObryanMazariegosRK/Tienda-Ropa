<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Antes de alterar, borramos los banners de prueba existentes —
        // no tienen forma de encajar en la nueva estructura de grupos.
        DB::table('banners')->truncate();

        Schema::table('banners', function (Blueprint $table) {
            $table->foreignId('banner_group_id')
                  ->after('id')
                  ->constrained('banner_groups')
                  ->onDelete('cascade');

            $table->dropColumn(['title', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('banner_group_id');

            $table->string('title', 150)->default('');
            $table->string('type', 20)->default('image');
            $table->boolean('is_active')->default(true);
        });
    }
};