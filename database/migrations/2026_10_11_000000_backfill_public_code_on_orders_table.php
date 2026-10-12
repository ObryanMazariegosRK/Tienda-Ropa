<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Los pedidos creados antes de agregar public_code quedaron sin código, y el admin
// no podía cambiarles el estado (la notificación al cliente necesita el código).
// Generamos uno con el mismo formato que OrderModel: 8 caracteres sin 0/O/1/I/L.
return new class extends Migration
{
    public function up(): void
    {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        DB::table('orders')->whereNull('public_code')->orderBy('id')->pluck('id')
            ->each(function ($id) use ($chars) {
                do {
                    $codigo = '';
                    for ($i = 0; $i < 8; $i++) {
                        $codigo .= $chars[random_int(0, strlen($chars) - 1)];
                    }
                } while (DB::table('orders')->where('public_code', $codigo)->exists());

                DB::table('orders')->where('id', $id)->update(['public_code' => $codigo]);
            });
    }

    public function down(): void
    {
        // No se revierte: borrar los códigos rompería los enlaces /pedido/{codigo} ya compartidos
    }
};
