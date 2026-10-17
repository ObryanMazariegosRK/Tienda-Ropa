<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\IAnalisisRepository;
use Illuminate\Support\Facades\DB;

// Consultas OLAP sobre el esquema estrella. Las fechas se filtran por fecha_id (AAAAMMDD)
// y los montos se suman tal cual: las anulaciones son negativas, así el resultado es neto.
class AnalisisRepository implements IAnalisisRepository
{
    private function ventas()
    {
        return DB::connection('dw')->table('hechos_ventas as h')->join('dim_fecha as f', 'f.fecha_id', '=', 'h.fecha_id');
    }

    public function ventasPorMes(int $desdeFechaId): array
    {
        return $this->ventas()->where('h.fecha_id', '>=', $desdeFechaId)
            ->groupBy('f.anio', 'f.mes')
            ->orderBy('f.anio')->orderBy('f.mes')
            ->get([
                'f.anio', 'f.mes',
                DB::raw('SUM(h.ingreso) as ingreso'),
                DB::raw('SUM(h.ganancia) as ganancia'),
                DB::raw("COUNT(DISTINCT CASE WHEN h.tipo_movimiento = 'venta' THEN h.pedido_id END) as pedidos"),
            ])->all();
    }

    public function ventasPorDiaSemana(int $desde, int $hasta): array
    {
        return $this->ventas()->whereBetween('h.fecha_id', [$desde, $hasta])
            ->groupBy('f.dia_semana', 'f.nombre_dia')
            ->orderBy('f.dia_semana')
            ->get(['f.dia_semana', 'f.nombre_dia', DB::raw('SUM(h.ingreso) as ingreso'), DB::raw('SUM(h.cantidad) as unidades')])
            ->all();
    }

    public function margenPorCategoria(int $desde, int $hasta): array
    {
        return $this->ventas()->join('dim_producto as p', 'p.producto_key', '=', 'h.producto_key')
            ->whereBetween('h.fecha_id', [$desde, $hasta])
            ->groupBy('p.categoria_principal', 'p.categoria')
            ->orderByDesc(DB::raw('SUM(h.ganancia)'))
            ->get([
                'p.categoria_principal', 'p.categoria',
                DB::raw('SUM(h.cantidad) as unidades'),
                DB::raw('SUM(h.ingreso) as ingreso'),
                DB::raw('SUM(CASE WHEN h.costo IS NOT NULL THEN h.ingreso ELSE 0 END) as ingreso_con_costo'),
                DB::raw('SUM(h.ganancia) as ganancia'),
            ])->all();
    }

    public function subastasPorResultado(int $desde, int $hasta): array
    {
        return DB::connection('dw')->table('hechos_subastas')
            ->whereBetween('fecha_id', [$desde, $hasta])
            ->groupBy('resultado')
            ->get([
                'resultado',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('AVG(precio_inicial) as precio_inicial'),
                DB::raw('AVG(precio_final) as precio_final'),
                DB::raw('AVG(incremento_pct) as incremento_pct'),
                DB::raw('AVG(total_pujas) as pujas'),
                DB::raw('AVG(postores) as postores'),
            ])->all();
    }

    public function primeraCompraPorCliente(): array
    {
        return DB::connection('dw')->table('hechos_ventas as h')
            ->join('dim_cliente as c', 'c.cliente_key', '=', 'h.cliente_key')
            ->where('h.tipo_movimiento', 'venta')
            ->groupBy('c.usuario_id')
            ->get(['c.usuario_id', DB::raw('MIN(h.fecha_id) as primera')])
            ->pluck('primera', 'usuario_id')
            ->map(fn($v) => (int) $v)->all();
    }

    public function clientesConCompras(int $desde, int $hasta): array
    {
        return DB::connection('dw')->table('hechos_ventas as h')
            ->join('dim_cliente as c', 'c.cliente_key', '=', 'h.cliente_key')
            ->where('h.tipo_movimiento', 'venta')
            ->whereBetween('h.fecha_id', [$desde, $hasta])
            ->distinct()->pluck('c.usuario_id')->map(fn($v) => (int) $v)->all();
    }

    public function totalesDelRango(int $desde, int $hasta): object
    {
        return $this->ventas()->whereBetween('h.fecha_id', [$desde, $hasta])->first([
            DB::raw('COALESCE(SUM(h.ingreso), 0) as ingreso'),
            DB::raw('COALESCE(SUM(h.ganancia), 0) as ganancia'),
            DB::raw('COALESCE(SUM(h.cantidad), 0) as unidades'),
            DB::raw("COUNT(DISTINCT CASE WHEN h.tipo_movimiento = 'venta' THEN h.pedido_id END) as pedidos"),
        ]);
    }

    public function tiempoDeVentaPorCategoria(int $desde, int $hasta): array
    {
        return DB::connection('dw')->table('hechos_ventas as h')
            ->join('dim_producto as p', 'p.producto_key', '=', 'h.producto_key')
            ->where('h.tipo_movimiento', 'venta')
            ->whereNotNull('h.dias_para_vender')
            ->whereBetween('h.fecha_id', [$desde, $hasta])
            ->groupBy('p.categoria_principal')
            ->orderBy(DB::raw('AVG(h.dias_para_vender)'))
            ->get(['p.categoria_principal', DB::raw('AVG(h.dias_para_vender) as dias'), DB::raw('COUNT(*) as ventas')])
            ->all();
    }
}
