<?php
namespace App\Application\UseCases\Dashboard;

use App\Application\Abstractions\Dashboard\IGetTopCategoriesUseCase;
use App\Application\DTOs\Dashboard\TopCategoryDTO;
use App\Models\OrderDetailModel;
use Carbon\Carbon;

class GetTopCategoriesUseCase implements IGetTopCategoriesUseCase
{
    private const ESTADOS_VALIDOS = ['confirmed', 'preparing', 'on_route', 'delivered'];
    private const LIMITE = 3;

    public function execute(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate, 'America/Guatemala')->startOfDay()->utc();
        $end = Carbon::parse($endDate, 'America/Guatemala')->endOfDay()->utc();

        // Si el producto está en una subcategoría (cat.parent_category_id no es null),
        // el ingreso se suma a la categoría PADRE (parent_cat). Si es una categoría
        // principal (sin padre), se suma a sí misma.
        $filas = OrderDetailModel::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories as cat', 'cat.id', '=', 'products.category_id')
            ->leftJoin('categories as parent_cat', 'parent_cat.id', '=', 'cat.parent_category_id')
            ->whereIn('orders.status', self::ESTADOS_VALIDOS)
            ->whereBetween('orders.confirmed_at', [$start, $end])
            ->selectRaw('
                COALESCE(parent_cat.id, cat.id) as categoria_id,
                COALESCE(parent_cat.name, cat.name) as categoria_nombre,
                SUM(order_items.unit_price * order_items.quantity) as ingresos
            ')
            ->groupBy('categoria_id', 'categoria_nombre')
            ->orderByDesc('ingresos')
            ->limit(self::LIMITE)
            ->get();

        return $filas->map(fn($fila) => new TopCategoryDTO(
            categoryId: (int) $fila->categoria_id,
            categoryName: $fila->categoria_nombre,
            revenue: (float) $fila->ingresos
        ))->toArray();
    }
}