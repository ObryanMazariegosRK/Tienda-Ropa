<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// Crea o actualiza las tablas del data warehouse (database/migrations/dw) en tienda_dw.
// El arranque del contenedor lo llama siempre: si la base aún no existe, solo avisa.
class DwMigrar extends Command
{
    protected $signature = 'dw:migrar {--estricto : Falla si la base del warehouse no está disponible}';
    protected $description = 'Aplica las migraciones del data warehouse y la bitácora en su propia base.';

    public function handle(): int
    {
        if (!config('dw.habilitado')) {
            $this->line('Data warehouse deshabilitado (DW_HABILITADO=false).');
            return self::SUCCESS;
        }

        try {
            DB::connection('dw_admin')->getPdo();
        } catch (\Throwable $e) {
            $this->warn('La base del data warehouse aún no está disponible: ' . $e->getMessage());
            $this->warn('Créala una vez con: php artisan dw:sql-instalacion | mysql -u root -p');
            return $this->option('estricto') ? self::FAILURE : self::SUCCESS;
        }

        return $this->call('migrate', [
            '--database' => 'dw_admin',
            '--path' => 'database/migrations/dw',
            '--force' => true,
        ]);
    }
}
