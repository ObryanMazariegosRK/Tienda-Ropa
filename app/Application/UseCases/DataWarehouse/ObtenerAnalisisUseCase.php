<?php
namespace App\Application\UseCases\DataWarehouse;

use App\Application\Abstractions\DataWarehouse\IObtenerAnalisisUseCase;
use App\Domain\Abstractions\IAnalisisRepository;
use App\Domain\Abstractions\IDataWarehouseRepository;
use Carbon\CarbonImmutable;

// Análisis que el dashboard no tiene: tendencias, días de venta, margen, subastas, clientes y tiempo de venta
class ObtenerAnalisisUseCase implements IObtenerAnalisisUseCase
{
    private const MESES_CORTOS = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    private const DIAS = [1 => 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    public function __construct(
        private IAnalisisRepository $analisis,
        private IDataWarehouseRepository $dw
    ) {}

    public function execute(int $meses): array
    {
        $hoy = CarbonImmutable::now(config('dw.zona_horaria'));
        $hasta = (int) $hoy->format('Ymd');
        $desde = $meses > 0 ? (int) $hoy->subMonthsNoOverflow($meses)->addDay()->format('Ymd') : 19000101;

        $ultima = $this->dw->ultimaCarga();

        return [
            'ultimaCarga' => $ultima ? [
                'terminadoEn' => $ultima->terminado_en,
                'estado' => $ultima->estado,
                'mensaje' => $ultima->mensaje,
            ] : null,
            'rango' => ['meses' => $meses, 'desde' => $desde, 'hasta' => $hasta],
            'resumen' => $this->resumen($desde, $hasta),
            'tendencia' => $this->tendencia($hoy),
            'diasSemana' => $this->diasSemana($desde, $hasta),
            'categorias' => $this->categorias($desde, $hasta),
            'subastas' => $this->subastas($desde, $hasta),
            'clientes' => $this->clientes($desde, $hasta),
            'tiempoVenta' => array_map(fn($f) => [
                'categoria' => $f->categoria_principal,
                'dias' => round((float) $f->dias, 1),
                'ventas' => (int) $f->ventas,
            ], $this->analisis->tiempoDeVentaPorCategoria($desde, $hasta)),
        ];
    }

    private function resumen(int $desde, int $hasta): array
    {
        $t = $this->analisis->totalesDelRango($desde, $hasta);
        $pedidos = (int) $t->pedidos;
        return [
            'ingreso' => round((float) $t->ingreso, 2),
            'ganancia' => round((float) $t->ganancia, 2),
            'unidades' => (int) $t->unidades,
            'pedidos' => $pedidos,
            'ticketPromedio' => $pedidos > 0 ? round((float) $t->ingreso / $pedidos, 2) : 0,
        ];
    }

    // Últimos 12 meses comparados con los mismos meses del año anterior
    private function tendencia(CarbonImmutable $hoy): array
    {
        $inicio = $hoy->startOfMonth()->subMonths(23);
        $porMes = [];
        foreach ($this->analisis->ventasPorMes((int) $inicio->format('Ymd')) as $fila) {
            $porMes[sprintf('%04d-%02d', $fila->anio, $fila->mes)] = $fila;
        }

        $meses = [];
        for ($i = 11; $i >= 0; $i--) {
            $mes = $hoy->startOfMonth()->subMonths($i);
            $actual = $porMes[$mes->format('Y-m')] ?? null;
            $anterior = $porMes[$mes->subYear()->format('Y-m')] ?? null;
            $meses[] = [
                'etiqueta' => self::MESES_CORTOS[$mes->month] . ' ' . $mes->format('y'),
                'ingreso' => round((float) ($actual->ingreso ?? 0), 2),
                'ganancia' => round((float) ($actual->ganancia ?? 0), 2),
                'pedidos' => (int) ($actual->pedidos ?? 0),
                'ingresoAnioAnterior' => round((float) ($anterior->ingreso ?? 0), 2),
            ];
        }
        return $meses;
    }

    private function diasSemana(int $desde, int $hasta): array
    {
        $datos = [];
        foreach ($this->analisis->ventasPorDiaSemana($desde, $hasta) as $fila) {
            $datos[(int) $fila->dia_semana] = $fila;
        }
        // Los 7 días siempre, aunque alguno no tenga ventas
        return array_map(fn($n, $nombre) => [
            'dia' => $nombre,
            'ingreso' => round((float) ($datos[$n]->ingreso ?? 0), 2),
            'unidades' => (int) ($datos[$n]->unidades ?? 0),
        ], array_keys(self::DIAS), self::DIAS);
    }

    private function categorias(int $desde, int $hasta): array
    {
        return array_map(function ($f) {
            $conCosto = (float) $f->ingreso_con_costo;
            return [
                'principal' => $f->categoria_principal,
                'categoria' => $f->categoria,
                'unidades' => (int) $f->unidades,
                'ingreso' => round((float) $f->ingreso, 2),
                // Sin costo registrado no se sabe la ganancia (no es cero)
                'ganancia' => $conCosto > 0 ? round((float) $f->ganancia, 2) : null,
                // Margen solo sobre las ventas que tienen costo registrado
                'margenPct' => $conCosto > 0 ? round((float) $f->ganancia / $conCosto * 100, 1) : null,
            ];
        }, $this->analisis->margenPorCategoria($desde, $hasta));
    }

    private function subastas(int $desde, int $hasta): array
    {
        $porResultado = [];
        foreach ($this->analisis->subastasPorResultado($desde, $hasta) as $f) {
            $porResultado[$f->resultado] = $f;
        }
        $total = array_sum(array_map(fn($f) => (int) $f->cantidad, $porResultado));
        $vendidas = $porResultado['vendida'] ?? null;

        return [
            'total' => $total,
            'porResultado' => [
                'vendida' => (int) ($vendidas->cantidad ?? 0),
                'sin_pujas' => (int) ($porResultado['sin_pujas']->cantidad ?? 0),
                'no_reclamada' => (int) ($porResultado['no_reclamada']->cantidad ?? 0),
                'cancelada' => (int) ($porResultado['cancelada']->cantidad ?? 0),
            ],
            'precioInicialPromedio' => round((float) ($vendidas->precio_inicial ?? 0), 2),
            'precioFinalPromedio' => round((float) ($vendidas->precio_final ?? 0), 2),
            'incrementoPromedioPct' => round((float) ($vendidas->incremento_pct ?? 0), 1),
            'pujasPromedio' => round((float) ($vendidas->pujas ?? 0), 1),
            'postoresPromedio' => round((float) ($vendidas->postores ?? 0), 1),
        ];
    }

    // Nuevos: su primera compra fue dentro del rango. Recurrentes: ya habían comprado antes.
    private function clientes(int $desde, int $hasta): array
    {
        $primeras = $this->analisis->primeraCompraPorCliente();
        $nuevos = 0;
        $recurrentes = 0;
        foreach ($this->analisis->clientesConCompras($desde, $hasta) as $usuarioId) {
            ($primeras[$usuarioId] ?? $desde) >= $desde ? $nuevos++ : $recurrentes++;
        }
        return ['nuevos' => $nuevos, 'recurrentes' => $recurrentes];
    }
}
