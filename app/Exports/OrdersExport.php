<?php

namespace App\Exports;

use App\Models\OrderModel;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OrdersExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    private const ESTADOS_LEGIBLES = [
        'pending_payment' => 'Pendiente de pago',
        'confirmed'       => 'Confirmado',
        'preparing'       => 'En preparación',
        'on_route'        => 'En camino',
        'delivered'       => 'Entregado',
        'cancelled'       => 'Cancelado',
    ];

    public function __construct(
        private string $startDate,
        private string $endDate
    ) {}

    public function array(): array
    {
        $start = Carbon::parse($this->startDate, 'America/Guatemala')->startOfDay()->utc();
        $end = Carbon::parse($this->endDate, 'America/Guatemala')->endOfDay()->utc();

        // Filtramos por confirmed_at (cuándo se cobró), excluyendo cancelados —
        // igual que el reporte de ventas. Los pedidos aún sin confirmar (confirmed_at
        // null) no aparecen, porque todavía no representan una venta real.
        $orders = OrderModel::with(['details.product', 'user'])
            ->whereNotNull('confirmed_at')
            ->whereIn('status', ['confirmed', 'preparing', 'on_route', 'delivered'])
            ->whereBetween('confirmed_at', [$start, $end])
            ->orderBy('confirmed_at')
            ->get();

        $filas = [];

        foreach ($orders as $order) {
            $cliente = $order->user
                ? trim("{$order->user->name} {$order->user->last_name}")
                : 'Cliente #' . $order->user_id;

            $telefono = $order->user->phone ?? '';

            foreach ($order->details as $detail) {
                $filas[] = [
                    $order->id,
                    Carbon::parse($order->confirmed_at)->timezone('America/Guatemala')->format('d/m/Y H:i'),
                    $cliente,
                    $telefono,
                    $order->shipping_address,
                    self::ESTADOS_LEGIBLES[$order->status] ?? $order->status,
                    $detail->product->name ?? 'Producto eliminado',
                    $detail->quantity,
                    number_format($detail->unit_price, 2),
                    number_format($detail->unit_price * $detail->quantity, 2),
                ];
            }
        }

        return $filas;
    }

    public function headings(): array
    {
        return [
            'Pedido #',
            'Fecha',
            'Cliente',
            'Teléfono',
            'Dirección de envío',
            'Estado',
            'Producto',
            'Cantidad',
            'Precio unitario (Q)',
            'Subtotal (Q)',
        ];
    }

    public function title(): string
    {
        return 'Pedidos';
    }

    public function styles(Worksheet $sheet): array
    {
        // Fila 1 = encabezados (Pedido #, Fecha, Cliente, etc.)
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '16A34A'], // verde Amerishop
            ],
            'alignment' => ['vertical' => 'center'],
        ]);

        // Bordes suaves en toda la tabla con datos
        $ultimaFila = $sheet->getHighestRow();
        $sheet->getStyle("A1:J{$ultimaFila}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ]);

        // Un poco más de alto en la fila de encabezados
        $sheet->getRowDimension(1)->setRowHeight(22);

        return [];
    }
}