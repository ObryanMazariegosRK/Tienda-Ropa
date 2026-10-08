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
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->string('provider')->nullable()->after('role');       // 'google' | 'facebook'
            $table->string('provider_id')->nullable()->after('provider'); // el id que da el proveedor
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['provider', 'provider_id']);
            //revertir password a NOT NULL fallaría si ya hay usuarios OAuth sin password.
            // $table->string('password')->nullable(false)->change();
        });
    }
};
