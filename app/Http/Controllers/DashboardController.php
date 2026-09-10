<?php
namespace App\Http\Controllers;

use App\Application\Abstractions\Dashboard\IGetDashboardSummaryUseCase;
use App\Application\Abstractions\Dashboard\IGetRevenueReportUseCase;
use App\Application\Abstractions\Dashboard\IGetOrderStatsUseCase;
use App\Application\Abstractions\Dashboard\IGetRevenueBySaleTypeUseCase;
use App\Application\Abstractions\Dashboard\IGetTopCategoriesUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(private IGetDashboardSummaryUseCase $getSummary,
    private IGetRevenueReportUseCase $getRevenueReport,
    private IGetOrderStatsUseCase $getOrderStats,
    private IGetTopCategoriesUseCase $getTopCategories,
    private IGetRevenueBySaleTypeUseCase $getRevenueBySaleType) {}

    public function summary(): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->getSummary->execute()], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo cargar el resumen del dashboard.'], 500);
        }
    }

    public function revenue(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start' => ['required', 'date'],
                'end' => ['required', 'date'],
            ]);

            $report = $this->getRevenueReport->execute($request->query('start'), $request->query('end'));
            return response()->json(['success' => true, 'data' => $report], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo generar el reporte de ingresos.'], 500);
        }
    }

    public function orderStats(): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->getOrderStats->execute()], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudieron cargar las estadísticas de pedidos.'], 500);
        }
    }

    public function topCategories(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start' => ['required', 'date'],
                'end' => ['required', 'date'],
            ]);

            $categorias = $this->getTopCategories->execute($request->query('start'), $request->query('end'));
            return response()->json(['success' => true, 'data' => $categorias], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo generar el top de categorías.'], 500);
        }
    }

    public function revenueBySaleType(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start' => ['required', 'date'],
                'end' => ['required', 'date'],
            ]);

            $data = $this->getRevenueBySaleType->execute($request->query('start'), $request->query('end'));
            return response()->json(['success' => true, 'data' => $data], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo generar el reporte por tipo de venta.'], 500);
        }
    }

    public function exportOrdersExcel(Request $request)
    {
        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
        ]);

        $start = $request->query('start');
        $end = $request->query('end');
        $nombreArchivo = "pedidos_{$start}_a_{$end}.xlsx";

        return Excel::download(new OrdersExport($start, $end), $nombreArchivo);
    }

    public function exportSalesExcel(Request $request)
    {
        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
        ]);

        $start = $request->query('start');
        $end = $request->query('end');
        $nombreArchivo = "ventas_{$start}_a_{$end}.xlsx";

        $export = new SalesReportExport(
            $start,
            $end,
            $this->getRevenueReport,
            $this->getRevenueBySaleType,
            $this->getTopCategories
        );

        return Excel::download($export, $nombreArchivo);
    }

    public function exportSalesPdf(Request $request)
    {
        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
        ]);

        $start = $request->query('start');
        $end = $request->query('end');

        // Reutilizamos los mismos cálculos del dashboard
        $revenue = $this->getRevenueReport->execute($start, $end);
        $porTipo = $this->getRevenueBySaleType->execute($start, $end);
        $categorias = $this->getTopCategories->execute($start, $end);

        $periodoLegible = Carbon::parse($start)->format('d/m/Y') . ' al ' . Carbon::parse($end)->format('d/m/Y');
        $generadoEl = Carbon::now('America/Guatemala')->format('d/m/Y H:i');

        $logoPath = public_path('Auth/images/Prenda.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;
        
        $pdf = Pdf::loadView('admin.reports.sales-pdf', compact(
            'revenue', 'porTipo', 'categorias', 'periodoLegible', 'generadoEl', 'logoBase64'
        ));

        return $pdf->download("ventas_{$start}_a_{$end}.pdf");
    }

    public function exportOrdersPdf(Request $request)
    {
        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
        ]);

        $start = $request->query('start');
        $end = $request->query('end');

        $inicio = Carbon::parse($start, 'America/Guatemala')->startOfDay()->utc();
        $fin = Carbon::parse($end, 'America/Guatemala')->endOfDay()->utc();

        $estadosLegibles = [
            'confirmed' => 'Confirmado', 'preparing' => 'En preparación',
            'on_route' => 'En camino', 'delivered' => 'Entregado',
        ];

        $orders = \App\Models\OrderModel::with(['details.product', 'user'])
            ->whereNotNull('confirmed_at')
            ->whereIn('status', ['confirmed', 'preparing', 'on_route', 'delivered'])
            ->whereBetween('confirmed_at', [$inicio, $fin])
            ->orderBy('confirmed_at')
            ->get();

        $filas = [];
        $totalGeneral = 0;

        foreach ($orders as $order) {
            $cliente = $order->user ? trim("{$order->user->name} {$order->user->last_name}") : 'Cliente #' . $order->user_id;
            foreach ($order->details as $detail) {
                $subtotal = $detail->unit_price * $detail->quantity;
                $totalGeneral += $subtotal;
                $filas[] = [
                    'id' => $order->id,
                    'fecha' => Carbon::parse($order->confirmed_at)->timezone('America/Guatemala')->format('d/m/y H:i'),
                    'cliente' => $cliente,
                    'estado' => $estadosLegibles[$order->status] ?? $order->status,
                    'producto' => $detail->product->name ?? 'Producto eliminado',
                    'cantidad' => $detail->quantity,
                    'precioUnitario' => $detail->unit_price,
                    'subtotal' => $subtotal,
                ];
            }
        }

        $periodoLegible = Carbon::parse($start)->format('d/m/Y') . ' al ' . Carbon::parse($end)->format('d/m/Y');
        $generadoEl = Carbon::now('America/Guatemala')->format('d/m/Y H:i');

        $logoPath = public_path('Auth/images/Prenda.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $pdf = Pdf::loadView('admin.reports.orders-pdf', compact(
            'filas', 'totalGeneral', 'periodoLegible', 'generadoEl', 'logoBase64'
        ))->setPaper('a4', 'landscape');

        return $pdf->download("pedidos_{$start}_a_{$end}.pdf");
    }


}