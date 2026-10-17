<?php

namespace App\Http\Controllers;

use App\Application\Abstractions\Bitacora\IListarBitacoraUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Panel: consulta de la bitácora (solo lectura)
class AdminBitacoraController extends Controller
{
    public function __construct(private IListarBitacoraUseCase $listar) {}

    public function index(Request $request): JsonResponse
    {
        $hoy = now(config('dw.zona_horaria'))->format('Y-m-d');
        $request->validate([
            'desde' => ['nullable', 'date', 'before_or_equal:' . $hoy],
            'hasta' => ['nullable', 'date', 'before_or_equal:' . $hoy, 'after_or_equal:desde'],
            'accion' => ['nullable', 'string', 'max:60'],
            'usuario' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'desde.before_or_equal' => 'Las fechas no pueden ser futuras.',
            'hasta.before_or_equal' => 'Las fechas no pueden ser futuras.',
            'hasta.after_or_equal' => 'La fecha "Desde" no puede ser posterior a la fecha "Hasta".',
        ]);

        try {
            $resultado = $this->listar->execute($request->only(['desde', 'hasta', 'accion', 'usuario']), (int) $request->input('page', 1));
            return response()->json(['success' => true] + $resultado);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'No se pudo leer la bitácora. Revisa que el data warehouse esté instalado (php artisan dw:migrar).'], 422);
        }
    }
}
