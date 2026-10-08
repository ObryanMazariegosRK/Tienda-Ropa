<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PushController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'     => 'required|string',
            'keys.p256dh'  => 'required|string',
            'keys.auth'    => 'required|string',
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
}