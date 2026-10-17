<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

// Imprime el SQL que crea la base tienda_dw y su usuario de solo SELECT/INSERT.
// Se ejecuta UNA vez como root de MySQL (la tienda no tiene esos permisos):
//   php artisan dw:sql-instalacion | mysql -u root -p
class DwSqlInstalacion extends Command
{
    protected $signature = 'dw:sql-instalacion';
    protected $description = 'Imprime el SQL (para root) que crea la base del data warehouse y sus usuarios.';

    public function handle(): int
    {
        $dw = config('database.connections.dw');
        $principal = config('database.connections.dw_admin.username');

        if (empty($dw['password'])) {
            $this->error('Falta DW_DB_PASSWORD en las variables de entorno.');
            return self::FAILURE;
        }

        $base = str_replace('`', '', $dw['database']);
        $usuario = addslashes($dw['username']);
        $clave = addslashes($dw['password']);

        $sql = [
            "-- Data warehouse y bitácora de Amerishop",
            "CREATE DATABASE IF NOT EXISTS `{$base}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
            "-- Usuario de la tienda para el warehouse: solo puede LEER y AGREGAR (nada de UPDATE/DELETE)",
            "CREATE USER IF NOT EXISTS '{$usuario}'@'%' IDENTIFIED BY '{$clave}';",
            "ALTER USER '{$usuario}'@'%' IDENTIFIED BY '{$clave}';",
            "GRANT SELECT, INSERT ON `{$base}`.* TO '{$usuario}'@'%';",
        ];
        if ($principal !== 'root') {
            $sql[] = "-- El usuario principal crea las tablas y triggers (php artisan dw:migrar)";
            $sql[] = "GRANT ALL PRIVILEGES ON `{$base}`.* TO '" . addslashes($principal) . "'@'%';";
        }
        $sql[] = "-- Permite crear triggers sin ser root (MySQL tiene el registro binario activo)";
        $sql[] = "SET PERSIST log_bin_trust_function_creators = 1;";
        $sql[] = "FLUSH PRIVILEGES;";

        $this->line(implode("\n", $sql));
        return self::SUCCESS;
    }
}
