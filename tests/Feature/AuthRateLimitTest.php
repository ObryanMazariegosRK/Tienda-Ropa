<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_email_bloquea_por_correo_aunque_cambie_la_ip(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.$i"])
                ->postJson('/api/verify-email', ['email' => 'victima@test.com', 'code' => '123456']);
            $this->assertNotSame(429, $response->status());
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->postJson('/api/verify-email', ['email' => 'victima@test.com', 'code' => '123456'])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.');
    }

    public function test_login_bloquea_despues_de_cinco_intentos(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/login', ['email' => 'alguien@test.com', 'password' => 'incorrecta']);
            $this->assertNotSame(429, $response->status());
        }

        $this->postJson('/api/login', ['email' => 'alguien@test.com', 'password' => 'incorrecta'])
            ->assertStatus(429);
    }
}
