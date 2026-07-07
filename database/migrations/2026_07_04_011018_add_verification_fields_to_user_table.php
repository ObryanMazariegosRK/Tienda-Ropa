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
            //Guarda la fecha en que el usuario se verificó con éxito
            $table->timestamp('email_verified_at')->nullable()->after('email');
            
            //Guarda el código temporal de 6 dígitos
            $table->string('verification_code', 6)->nullable()->after('email_verified_at');
            
            //Guarda la fecha y hora exacta en que el código caduca
            $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
        });
    }

    /**
     * Reverse the migrations, basicamente se indican las columnas que se 
     * quieren eliminar al hacer un rollback
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verified_at', 
                'verification_code', 
                'verification_code_expires_at'
            ]);
        });
    }
};
