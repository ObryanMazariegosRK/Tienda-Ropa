<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Inmutabilidad a nivel de base de datos: cualquier UPDATE o DELETE en estas tablas falla,
// aunque alguien tuviera permisos para intentarlo. (Además, el usuario "dw" que usa la
// tienda solo tiene permisos de SELECT e INSERT.)
return new class extends Migration
{
    private const TABLAS = ['bitacora', 'dim_fecha', 'dim_producto', 'dim_cliente', 'hechos_ventas', 'hechos_subastas', 'etl_cargas'];

    public function up(): void
    {
        $sqlite = DB::connection()->getDriverName() === 'sqlite';

        foreach (self::TABLAS as $tabla) {
            foreach (['UPDATE' => 'modificar', 'DELETE' => 'borrar'] as $operacion => $verbo) {
                $nombre = "{$tabla}_no_" . strtolower($operacion);
                $mensaje = "El data warehouse es inmutable: no se puede {$verbo} en {$tabla}";

                DB::unprepared($sqlite
                    ? "CREATE TRIGGER {$nombre} BEFORE {$operacion} ON {$tabla} BEGIN SELECT RAISE(ABORT, '{$mensaje}'); END"
                    : "CREATE TRIGGER {$nombre} BEFORE {$operacion} ON {$tabla} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$mensaje}'");
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$tabla}_no_update");
            DB::unprepared("DROP TRIGGER IF EXISTS {$tabla}_no_delete");
        }
    }
};
