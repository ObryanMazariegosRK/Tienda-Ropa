<?php

namespace App\Http\Controllers;

use App\Application\Abstractions\DataWarehouse\ICargarDataWarehouseUseCase;
use App\Application\Abstractions\DataWarehouse\IObtenerAnalisisUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Panel: reportes del data warehouse
class AdminAnalisisController extends Controller
{
    public function __construct(
        private IObtenerAnalisisUseCase $obtener,
        private ICargarDataWarehouseUseCase $cargar
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate(['meses' => ['nullable', 'integer', 'in:0,3,6,12,24']]);

        try {
            return response()->json(['success' => true, 'data' => $this->obtener->execute((int) $request->input('meses', 12))]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'No se pudo leer el data warehouse. Revisa que esté instalado (php artisan dw:migrar).'], 422);
        }
    }

    // Corre el ETL en el momento (normalmente lo hace el scheduler cada hora)
    public function actualizar(): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->cargar->execute()]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'No se pudo actualizar el data warehouse: ' . $e->getMessage()], 422);
        }
    }
}
