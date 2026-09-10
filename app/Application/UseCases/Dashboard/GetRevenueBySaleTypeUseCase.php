<?php
namespace App\Application\UseCases\Dashboard;

use App\Application\Abstractions\Dashboard\IGetRevenueBySaleTypeUseCase;
use App\Application\DTOs\Dashboard\RevenueBySaleTypeDTO;
use App\Models\OrderDetailModel;
use Carbon\Carbon;

class GetRevenueBySaleTypeUseCase implements IGetRevenueBySaleTypeUseCase
{
    private const ESTADOS_VALIDOS = ['confirmed', 'preparing', 'on_route', 'delivered'];

    public function execute(string $startDate, string $endDate): RevenueBySaleTypeDTO
    {
        $start = Carbon::parse($startDate, 'America/Guatemala')->startOfDay()->utc();
        $end = Carbon::parse($endDate, 'America/Guatemala')->endOfDay()->utc();

        // Agrupamos los ingresos según el sale_type del producto vendido.
        $filas = OrderDetailModel::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('orders.status', self::ESTADOS_VALIDOS)
            ->whereBetween('orders.confirmed_at', [$start, $end])
            ->selectRaw('products.sale_type, SUM(order_items.unit_price * order_items.quantity) as ingresos')
            ->groupBy('products.sale_type')
            ->pluck('ingresos', 'sale_type');

        return new RevenueBySaleTypeDTO(
            directRevenue: (float) ($filas['direct'] ?? 0),
            auctionRevenue: (float) ($filas['auction'] ?? 0)
        );
    }
}