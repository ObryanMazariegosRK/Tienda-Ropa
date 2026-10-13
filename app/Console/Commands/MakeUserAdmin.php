<?php
namespace App\Console\Commands;

use App\Domain\Enum\RoleType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// En producción no hay phpMyAdmin: así se da acceso al panel a alguien que ya se registró
class MakeUserAdmin extends Command
{
    protected $signature = 'users:make-admin {email : Correo de una cuenta ya registrada y verificada}
                            {--revoke : Quita el rol de administrador en lugar de darlo}';
    protected $description = 'Da (o quita con --revoke) el rol de administrador a un usuario existente.';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $usuario = DB::table('users')->where('email', $email)->first();

        if (!$usuario) {
            $this->error("No existe ninguna cuenta con el correo $email. Primero regístrate en la tienda.");
            return self::FAILURE;
        }
        if (!$usuario->email_verified_at) {
            $this->error("La cuenta $email aún no verificó su correo.");
            return self::FAILURE;
        }

        $rol = $this->option('revoke') ? RoleType::CUSTOMER->value : RoleType::ADMIN->value;
        DB::table('users')->where('id', $usuario->id)->update(['role' => $rol, 'updated_at' => now()]);

        $this->info($this->option('revoke')
            ? "$email ya no es administrador."
            : "$email ahora es administrador. Entra en /admin/login.");

        return self::SUCCESS;
    }
}
