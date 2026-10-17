<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\IBitacoraRepository;
use Illuminate\Support\Facades\DB;

// Conexión "dw": su usuario de MySQL solo tiene SELECT e INSERT en tienda_dw
class BitacoraRepository implements IBitacoraRepository
{
    private function tabla()
    {
        return DB::connection('dw')->table('bitacora');
    }

    public function registrar(array $evento): void
    {
        $evento['datos_antes'] = isset($evento['datos_antes']) ? json_encode($evento['datos_antes'], JSON_UNESCAPED_UNICODE) : null;
        $evento['datos_despues'] = isset($evento['datos_despues']) ? json_encode($evento['datos_despues'], JSON_UNESCAPED_UNICODE) : null;
        $this->tabla()->insert($evento);
    }

    public function listar(array $filtros, int $pagina, int $porPagina): array
    {
        $consulta = $this->tabla()
            ->when($filtros['desde'] ?? null, fn($q, $v) => $q->where('ocurrido_en', '>=', $v))
            ->when($filtros['hasta'] ?? null, fn($q, $v) => $q->where('ocurrido_en', '<', $v))
            ->when($filtros['accion'] ?? null, fn($q, $v) => $q->where('accion', $v))
            ->when($filtros['usuario'] ?? null, fn($q, $v) => $q->where('usuario_correo', 'like', '%' . addcslashes($v, '%_') . '%'))
            ->orderByDesc('id');

        $total = (clone $consulta)->count();
        $filas = $consulta->forPage($pagina, $porPagina)->get()->map(function ($fila) {
            $fila->datos_antes = $fila->datos_antes ? json_decode($fila->datos_antes, true) : null;
            $fila->datos_despues = $fila->datos_despues ? json_decode($fila->datos_despues, true) : null;
            return $fila;
        })->all();

        return [
            'data' => $filas,
            'meta' => [
                'current_page' => $pagina,
                'per_page' => $porPagina,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $porPagina)),
            ],
        ];
    }

    public function acciones(): array
    {
        return $this->tabla()->distinct()->orderBy('accion')->pluck('accion')->all();
    }
}
