<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

// Prepara el data warehouse en una SQLite en memoria aparte de la base de la tienda
trait UsaDataWarehouse
{
    protected function prepararDataWarehouse(): void
    {
        config(['dw.habilitado' => true]);

        // "dw" y "dw_admin" son dos usuarios de la misma base: en los tests, la misma conexión
        DB::purge('dw');
        DB::purge('dw_admin');
        DB::connection('dw_admin')->setPdo(DB::connection('dw')->getPdo());

        $this->artisan('migrate', ['--database' => 'dw', '--path' => 'database/migrations/dw', '--force' => true])->assertSuccessful();
    }

    protected function dw()
    {
        return DB::connection('dw');
    }
}
