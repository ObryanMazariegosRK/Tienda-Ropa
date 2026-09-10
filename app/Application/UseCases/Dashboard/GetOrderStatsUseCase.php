<?php
namespace App\Application\UseCases\Dashboard;

use App\Application\Abstractions\Dashboard\IGetOrderStatsUseCase;
use App\Application\DTOs\Dashboard\OrderStatsDTO;
use App\Application\DTOs\Dashboard\StuckOrderDTO;
use App\Models\OrderModel;
use Carbon\Carbon;

class GetOrderStatsUseCase implements IGetOrderStatsUseCase
{
    private const TODOS_LOS_ESTADOS = ['pending_payment', 'confirmed', 'preparing', 'on_route', 'delivered', 'cancelled'];
    private const DIAS_PARA_ATASCADO = 3;

    public function execute(): OrderStatsDTO
    {
        $conteosRaw = OrderModel::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Nos aseguramos de que TODOS los estados aparezcan, aunque tengan 0
        $statusCounts = [];
        foreach (self::TODOS_LOS_ESTADOS as $estado) {
            $statusCounts[$estado] = (int) ($conteosRaw[$estado] ?? 0);
        }

        $nuevosHoy = OrderModel::whereDate('created_at', Carbon::today())->count();
        $nuevosSemana = OrderModel::where('created_at', '>=', Carbon::now()->startOfWeek())->count();

        $totalOrdenes = array_sum($statusCounts);
        $tasaCancelacion = $totalOrdenes > 0
            ? round(($statusCounts['cancelled'] / $totalOrdenes) * 100, 1)
            : 0.0;

        $atascadas = OrderModel::whereNotIn('status', ['delivered', 'cancelled'])
            ->where('updated_at', '<=', Carbon::now()->subDays(self::DIAS_PARA_ATASCADO))
            ->get();

        $stuckOrders = $atascadas->map(function ($order) {
            return new StuckOrderDTO(
                id: $order->id,
                status: $order->status,
                daysSinceUpdate: Carbon::parse($order->updated_at)->diffInDays(Carbon::now())
            );
        })->toArray();

        return new OrderStatsDTO(
            statusCounts: $statusCounts,
            newToday: $nuevosHoy,
            newThisWeek: $nuevosSemana,
            cancellationRate: $tasaCancelacion,
            stuckOrders: $stuckOrders
        );
    }
}