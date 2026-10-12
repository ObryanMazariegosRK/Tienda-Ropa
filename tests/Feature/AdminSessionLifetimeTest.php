<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminSessionLifetimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('users')->insert([
            'name' => 'Admin', 'last_name' => 'Tienda', 'email' => 'admin@test.com',
            'password' => bcrypt('Secreta123!'), 'email_verified_at' => now(), 'role' => 'admin',
        ]);
    }

    private function login(bool $mantenerSesion): string
    {
        return $this->postJson('/api/login', [
            'email' => 'admin@test.com', 'password' => 'Secreta123!', 'remember_me' => $mantenerSesion,
        ])->assertOk()->json('token');
    }

    private function diasDeVidaDelUltimoToken(): int
    {
        $token = DB::table('personal_access_tokens')->latest('id')->first();
        return (int) round(now()->diffInHours($token->expires_at) / 24);
    }

    public function test_con_la_casilla_marcada_la_sesion_del_admin_dura_30_dias(): void
    {
        $this->login(true);
        $this->assertSame(30, $this->diasDeVidaDelUltimoToken());
    }

    public function test_sin_la_casilla_la_sesion_del_admin_dura_1_dia(): void
    {
        $this->login(false);
        $this->assertSame(1, $this->diasDeVidaDelUltimoToken());
    }

    public function test_el_token_sigue_valido_a_los_29_dias_y_vence_despues_de_30(): void
    {
        $token = $this->login(true);

        $this->travel(29)->days();
        $this->withToken($token)->getJson('/api/profile')->assertOk()->assertJsonPath('role', 'admin');

        $this->travel(2)->days();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/profile')->assertUnauthorized();
    }
}
