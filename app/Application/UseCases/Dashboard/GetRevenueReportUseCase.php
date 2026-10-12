<?php
namespace App\Application\UseCases\Dashboard;

use App\Application\Abstractions\Dashboard\IGetRevenueReportUseCase;
use App\Application\DTOs\Dashboard\RevenueReportDTO;
use App\Models\OrderModel;
use App\Models\OrderDetailModel;
use Carbon\Carbon;

class GetRevenueReportUseCase implements IGetRevenueReportUseCase
{
    private const ESTADOS_VALIDOS = ['confirmed', 'preparing', 'on_route', 'delivered'];
    private const ZONA = 'America/Guatemala';

    public function execute(string $startDate, string $endDate): RevenueReportDTO
    {
        // Las fechas se guardan en UTC; los días se cuentan en hora de Guatemala (UTC-6).
        // Calculamos todo en hora de Guatemala y convertimos a UTC solo para consultar.
        $inicioGt = Carbon::parse($startDate, self::ZONA)->startOfDay();
        $finGt = Carbon::parse($endDate, self::ZONA)->endOfDay();
        $start = $inicioGt->copy()->utc();
        $end = $finGt->copy()->utc();

        // Periodo anterior de la misma duración, también en días de Guatemala
        // (antes se calculaba sobre UTC y quedaba corrido 6 horas)
        $diasDuracion = (int) $inicioGt->diffInDays($finGt->copy()->startOfDay()) + 1;
        $prevEnd = $inicioGt->copy()->subDay()->endOfDay()->utc();
        $prevStart = $inicioGt->copy()->subDays($diasDuracion)->startOfDay()->utc();

        $totalActual = OrderModel::whereIn('status', self::ESTADOS_VALIDOS)
            ->whereBetween('confirmed_at', [$start, $end])
            ->sum('total');

        $totalAnterior = OrderModel::whereIn('status', self::ESTADOS_VALIDOS)
            ->whereBetween('confirmed_at', [$prevStart, $prevEnd])
            ->sum('total');

        $porcentaje = $totalAnterior > 0
            ? round((($totalActual - $totalAnterior) / $totalAnterior) * 100, 1)
            : ($totalActual > 0 ? 100.0 : 0.0);

        // Ganancia = suma de (precio de venta - costo al momento de la venta) * cantidad,
        // por cada línea de producto vendido en el periodo. El costo se guarda en order_items
        // al vender; si una línea no lo tiene, se usa el costo actual del producto.
        $gananciaActual = (float) (OrderDetailModel::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('orders.status', self::ESTADOS_VALIDOS)
            ->whereBetween('orders.confirmed_at', [$start, $end])
            ->selectRaw('SUM((order_items.unit_price - COALESCE(order_items.unit_cost, products.cost, 0)) * order_items.quantity) as ganancia')
            ->value('ganancia') ?? 0);

        // Ingresos por día de Guatemala. Agrupamos en PHP: DATE(confirmed_at) en SQL usaba
        // el día UTC, y las ventas después de las 6:00 PM aparecían en el día siguiente.
        $porDia = OrderModel::whereIn('status', self::ESTADOS_VALIDOS)
            ->whereBetween('confirmed_at', [$start, $end])
            ->get(['confirmed_at', 'total'])
            ->groupBy(fn ($order) => Carbon::parse($order->confirmed_at, 'UTC')->timezone(self::ZONA)->format('Y-m-d'))
            ->map(fn ($orders) => (float) $orders->sum('total'));

        $breakdown = [];
        $cursor = $inicioGt->copy();
        while ($cursor->lte($finGt)) {
            $fechaStr = $cursor->format('Y-m-d');
            $breakdown[] = [
                'date' => $fechaStr,
                'revenue' => $porDia->get($fechaStr, 0.0),
            ];
            $cursor->addDay();
        }

        return new RevenueReportDTO(
            totalRevenue: (float) $totalActual,
            totalProfit: $gananciaActual,
            previousPeriodRevenue: (float) $totalAnterior,
            percentChange: $porcentaje,
            dailyBreakdown: $breakdown
        );
    }
}