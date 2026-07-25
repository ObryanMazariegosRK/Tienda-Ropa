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
        Schema::table('addresses', function (Blueprint $table) {
            // Quitamos el modelo estructurado viejo (nunca se llegó a usar)
            $table->dropColumn(['street', 'city', 'state', 'postal_code', 'country']);

            // Agregamos el modelo simple que usa la entidad Address actual
            $table->string('label', 50)->after('user_id');
            $table->string('address_line', 500)->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['label', 'address_line']);

            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
        });
    }
};
