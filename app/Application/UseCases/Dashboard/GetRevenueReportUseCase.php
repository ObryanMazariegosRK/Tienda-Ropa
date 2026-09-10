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

    public function execute(string $startDate, string $endDate): RevenueReportDTO
    {
        $start = Carbon::parse($startDate, 'America/Guatemala')->startOfDay()->utc();
        $end = Carbon::parse($endDate, 'America/Guatemala')->endOfDay()->utc();

        $diasDuracion = $start->diffInDays($end) + 1;
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($diasDuracion - 1)->startOfDay();

        $totalActual = OrderModel::whereIn('status', self::ESTADOS_VALIDOS)
            ->whereBetween('confirmed_at', [$start, $end])
            ->sum('total');

        $totalAnterior = OrderModel::whereIn('status', self::ESTADOS_VALIDOS)
            ->whereBetween('confirmed_at', [$prevStart, $prevEnd])
            ->sum('total');

        $porcentaje = $totalAnterior > 0
            ? round((($totalActual - $totalAnterior) / $totalAnterior) * 100, 1)
            : ($totalActual > 0 ? 100.0 : 0.0);

        // Ganancia = suma de (precio de venta - costo actual del producto) * cantidad,
        // por cada línea de producto vendido en el periodo.
        $gananciaActual = (float) (OrderDetailModel::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('orders.status', self::ESTADOS_VALIDOS)
            ->whereBetween('orders.confirmed_at', [$start, $end])
            ->selectRaw('SUM((order_items.unit_price - COALESCE(products.cost, 0)) * order_items.quantity) as ganancia')
            ->value('ganancia') ?? 0);

        $porDia = OrderModel::whereIn('status', self::ESTADOS_VALIDOS)
            ->whereBetween('confirmed_at', [$start, $end])
            ->selectRaw('DATE(confirmed_at) as fecha, SUM(total) as ingreso')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $breakdown = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $fechaStr = $cursor->format('Y-m-d');
            $registro = $porDia->firstWhere('fecha', $fechaStr);
            $breakdown[] = [
                'date' => $fechaStr,
                'revenue' => $registro ? (float) $registro->ingreso : 0.0,
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