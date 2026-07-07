<?php

namespace App\Http\Controllers\AuthController;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class LogoutUserController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            //El middleware 'auth:sanctum' inyecta al usuario autenticado en el Request
            //currentAccessToken()->delete() destruye únicamente el token que el usuario usó para esta petición
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Sesión cerrada exitosamente.'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al intentar cerrar sesión.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}