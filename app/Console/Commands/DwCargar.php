<?php
namespace App\Console\Commands;

use App\Application\Abstractions\DataWarehouse\ICargarDataWarehouseUseCase;
use Illuminate\Console\Command;

// ETL del data warehouse. El scheduler lo corre cada hora; también se puede correr a mano
// o con el botón "Actualizar ahora" de la pantalla Análisis.
class DwCargar extends Command
{
    protected $signature = 'dw:cargar';
    protected $description = 'Carga en el data warehouse las ventas, anulaciones y subastas nuevas.';

    public function handle(ICargarDataWarehouseUseCase $cargar): int
    {
        if (!config('dw.habilitado')) {
            return self::SUCCESS;
        }

        $r = $cargar->execute();
        $this->info("Ventas: {$r['ventas']} · Anulaciones: {$r['anulaciones']} · Subastas: {$r['subastas']} · Versiones nuevas de dimensiones: {$r['versiones_dimension']}");
        return self::SUCCESS;
    }
}
