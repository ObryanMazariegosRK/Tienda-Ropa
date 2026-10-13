<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MakeUserAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $email, bool $verificado = true): void
    {
        DB::table('users')->insert([
            'name' => 'Ana', 'last_name' => 'López', 'email' => $email, 'password' => bcrypt('Secreta123!'),
            'email_verified_at' => $verificado ? now() : null,
        ]);
    }

    public function test_da_y_quita_el_rol_de_administrador(): void
    {
        $this->usuario('ana@test.com');

        $this->artisan('users:make-admin', ['email' => 'ANA@test.com'])->assertSuccessful();
        $this->assertSame('admin', DB::table('users')->where('email', 'ana@test.com')->value('role'));

        $this->artisan('users:make-admin', ['email' => 'ana@test.com', '--revoke' => true])->assertSuccessful();
        $this->assertSame('customer', DB::table('users')->where('email', 'ana@test.com')->value('role'));
    }

    public function test_no_acepta_cuentas_inexistentes_ni_sin_verificar(): void
    {
        $this->usuario('nueva@test.com', verificado: false);

        $this->artisan('users:make-admin', ['email' => 'nadie@test.com'])->assertFailed();
        $this->artisan('users:make-admin', ['email' => 'nueva@test.com'])->assertFailed();
        $this->assertNotSame('admin', DB::table('users')->where('email', 'nueva@test.com')->value('role'));
    }
}
