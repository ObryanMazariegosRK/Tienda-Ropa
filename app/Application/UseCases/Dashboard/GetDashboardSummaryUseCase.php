<?php
namespace App\Application\UseCases\Dashboard;

use App\Application\Abstractions\Dashboard\IGetDashboardSummaryUseCase;
use App\Application\DTOs\Dashboard\DashboardSummaryDTO;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\AuctionModel;
use Carbon\Carbon;

class GetDashboardSummaryUseCase implements IGetDashboardSummaryUseCase
{
    public function execute(): DashboardSummaryDTO
    {
        $inicioMes = Carbon::now('America/Guatemala')->startOfMonth()->utc();
        $finMes = Carbon::now('America/Guatemala')->endOfMonth()->utc();

        // Mismos estados que el reporte de ingresos, para que la tarjeta y el reporte coincidan
        // (antes solo excluía cancelados y contaba pedidos regresados a "Pendiente de pago")
        $ingresosDelMes = OrderModel::whereBetween('confirmed_at', [$inicioMes, $finMes])
            ->whereIn('status', ['confirmed', 'preparing', 'on_route', 'delivered'])
            ->sum('total');

        $pedidosPendientes = OrderModel::where('status', 'pending_payment')->count();

        $productosActivos = ProductModel::where('status', 'available')->count();

        $subastasActivas = AuctionModel::where('status', 'active')->count();

        return new DashboardSummaryDTO(
            monthlyRevenue: (float) $ingresosDelMes,
            pendingOrders: $pedidosPendientes,
            activeProducts: $productosActivos,
            activeAuctions: $subastasActivas
        );
    }
}