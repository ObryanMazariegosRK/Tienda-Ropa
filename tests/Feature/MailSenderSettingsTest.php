<?php

namespace Tests\Feature;

use App\Mail\PasswordRecoveryMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MailSenderSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::find(DB::table('users')->insertGetId([
            'name' => 'Admin', 'last_name' => 'Tienda', 'email' => 'admin@test.com',
            'password' => 'x', 'email_verified_at' => now(), 'role' => 'admin',
        ])));
    }

    private function crearCuenta(array $datos = []): int
    {
        return $this->postJson('/api/admin/mail-senders', array_merge([
            'fromName' => 'Amerishop Tecpán', 'email' => 'Tienda@Gmail.com', 'password' => 'abcd efgh ijkl mnop',
        ], $datos))->assertCreated()->json('data.id');
    }

    public function test_la_contrasena_se_guarda_cifrada_y_nunca_se_devuelve(): void
    {
        $id = $this->crearCuenta();

        $guardada = DB::table('mail_senders')->find($id);
        $this->assertSame('tienda@gmail.com', $guardada->email);
        $this->assertNotSame('abcdefghijklmnop', $guardada->password);
        $this->assertSame('abcdefghijklmnop', decrypt($guardada->password, false)); // sin los espacios de Gmail

        $respuesta = $this->getJson('/api/admin/mail-senders')->assertOk();
        $this->assertStringNotContainsString('abcdefghijklmnop', $respuesta->getContent());
        $respuesta->assertJsonPath('data.senders.0.host', 'smtp.gmail.com')
            ->assertJsonPath('data.senders.0.port', 587)
            ->assertJsonPath('data.senders.0.isActive', false);
    }

    public function test_quita_los_espacios_duros_con_los_que_google_separa_la_clave(): void
    {
        $id = $this->crearCuenta(['password' => "abcd\u{00A0}efgh\u{00A0}ijkl\u{00A0}mnop"]);

        $this->assertSame('abcdefghijklmnop', decrypt(DB::table('mail_senders')->find($id)->password, false));
    }

    public function test_no_se_puede_usar_una_cuenta_sin_probarla(): void
    {
        $id = $this->crearCuenta();

        $this->putJson('/api/admin/mail-senders/active', ['id' => $id])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Primero envía un correo de prueba con esta cuenta para comprobar que funciona.');

        DB::table('mail_senders')->where('id', $id)->update(['last_tested_at' => now()]);
        $this->putJson('/api/admin/mail-senders/active', ['id' => $id])->assertOk()
            ->assertJsonPath('data.senders.0.isActive', true);

        // Volver al correo del .env
        $this->putJson('/api/admin/mail-senders/active', ['id' => null])->assertOk()
            ->assertJsonPath('data.senders.0.isActive', false);
    }

    public function test_cambiar_la_contrasena_obliga_a_probarla_otra_vez(): void
    {
        $id = $this->crearCuenta();
        DB::table('mail_senders')->where('id', $id)->update(['last_tested_at' => now()]);

        // Solo el nombre: la prueba sigue valiendo y la contraseña no cambia
        $this->putJson("/api/admin/mail-senders/$id", ['fromName' => 'Amerishop', 'email' => 'tienda@gmail.com'])->assertOk();
        $this->assertNotNull(DB::table('mail_senders')->find($id)->last_tested_at);
        $this->assertSame('abcdefghijklmnop', decrypt(DB::table('mail_senders')->find($id)->password, false));

        $this->putJson("/api/admin/mail-senders/$id", ['fromName' => 'Amerishop', 'email' => 'tienda@gmail.com', 'password' => 'nueva clave aqui'])->assertOk();
        $this->assertNull(DB::table('mail_senders')->find($id)->last_tested_at);
    }

    public function test_no_se_repite_la_misma_cuenta(): void
    {
        $this->crearCuenta();
        $this->postJson('/api/admin/mail-senders', [
            'fromName' => 'Otra', 'email' => 'tienda@gmail.com', 'password' => 'x',
        ])->assertStatus(422)->assertJsonPath('message', 'Esa cuenta de correo ya está registrada.');
    }

    public function test_la_prueba_explica_cuando_no_se_puede_conectar(): void
    {
        $id = $this->crearCuenta(['host' => '127.0.0.1', 'port' => 1]);

        $this->postJson("/api/admin/mail-senders/$id/test", ['to' => 'admin@test.com'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se pudo conectar con 127.0.0.1:1. Revisa el servidor y el puerto (Gmail: smtp.gmail.com, puerto 587).');

        $this->assertNull(DB::table('mail_senders')->find($id)->last_tested_at);
    }

    public function test_si_la_cuenta_en_uso_falla_el_codigo_sale_con_el_correo_del_servidor(): void
    {
        Mail::fake();
        $id = $this->crearCuenta(['host' => '127.0.0.1', 'port' => 1]);
        DB::table('mail_senders')->where('id', $id)->update(['last_tested_at' => now(), 'is_active' => true]);

        DB::table('users')->insert([
            'name' => 'Cliente', 'last_name' => 'Prueba', 'email' => 'cliente@test.com',
            'password' => bcrypt('Secreta123!'), 'email_verified_at' => now(),
        ]);

        $this->postJson('/api/forgot-password', ['email' => 'cliente@test.com'])->assertOk();

        Mail::assertSent(PasswordRecoveryMail::class, fn($mail) => $mail->hasTo('cliente@test.com'));
    }

    public function test_un_cliente_no_puede_ver_ni_cambiar_las_cuentas(): void
    {
        Sanctum::actingAs(User::find(DB::table('users')->insertGetId([
            'name' => 'Cliente', 'last_name' => 'Prueba', 'email' => 'cliente@test.com',
            'password' => 'x', 'email_verified_at' => now(),
        ])));

        $this->getJson('/api/admin/mail-senders')->assertForbidden();
        $this->postJson('/api/admin/mail-senders', ['fromName' => 'x', 'email' => 'a@b.com', 'password' => 'x'])->assertForbidden();
    }
}
