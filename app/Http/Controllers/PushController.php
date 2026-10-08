<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PushController extends Controller
{
    // Servicios de push de los navegadores (Chrome/Edge/Opera/Brave, Firefox, Safari, Windows).
    // El servidor le hace POST al endpoint, así que no aceptamos cualquier URL.
    private const PUSH_HOSTS_PERMITIDOS = [
        'fcm.googleapis.com',
        'push.services.mozilla.com',
        'push.apple.com',
        'notify.windows.com',
    ];

    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'     => ['required', 'string', 'max:500', 'url:https', function ($attribute, $value, $fail) {
                if (!$this->esServicioPushValido($value)) {
                    $fail('El endpoint de notificaciones no es válido.');
                }
            }],
            'keys.p256dh'  => 'required|string|max:255',
            'keys.auth'    => 'required|string|max:255',
        ]);

        // El trait HasPushSubscriptions nos da este método
        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth']
        );

        return response()->json(['success' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => 'required|string']);
        $request->user()->deletePushSubscription($request->input('endpoint'));

        return response()->json(['success' => true]);
    }

    private function esServicioPushValido(string $endpoint): bool
    {
        $host = strtolower((string) parse_url($endpoint, PHP_URL_HOST));

        foreach (self::PUSH_HOSTS_PERMITIDOS as $permitido) {
            if ($host === $permitido || str_ends_with($host, '.' . $permitido)) {
                return true;
            }
        }

        return false;
    }
}