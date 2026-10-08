<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function iniciarSesion(): void
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Cliente', 'last_name' => 'Prueba', 'email' => 'cliente@test.com',
            'password' => 'x', 'email_verified_at' => now(),
        ]);
        Sanctum::actingAs(User::find($id));
    }

    public function test_acepta_endpoints_de_servicios_push_reales(): void
    {
        $this->iniciarSesion();

        $this->postJson('/api/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => 'clave', 'auth' => 'secreto'],
        ])->assertOk();
    }

    public function test_rechaza_endpoints_que_no_son_servicios_push(): void
    {
        $this->iniciarSesion();

        foreach (['http://fcm.googleapis.com/x', 'https://atacante.com/x', 'https://fcm.googleapis.com.atacante.com/x'] as $endpoint) {
            $this->postJson('/api/push/subscribe', [
                'endpoint' => $endpoint,
                'keys' => ['p256dh' => 'clave', 'auth' => 'secreto'],
            ])->assertStatus(422);
        }
    }
}
