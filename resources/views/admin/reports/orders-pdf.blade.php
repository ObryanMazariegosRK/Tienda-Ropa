<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { color: #1f2937; font-size: 10px; }
        h1 { font-size: 18px; color: #16A34A; margin-bottom: 2px; }
        .periodo { color: #6b7280; font-size: 10px; margin-bottom: 15px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th { background: #16A34A; color: #fff; font-size: 9px; padding: 5px 6px; text-align: left; }
        table.data td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }
        table.data td.num, table.data th.num { text-align: right; }
        .total-row td { font-weight: bold; font-size: 11px; border-top: 2px solid #16A34A; padding-top: 8px; }
        .footer { margin-top: 20px; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <table style="width:100%; border:none; margin-bottom:15px;">
        <tr>
            <td style="border:none; vertical-align:top;">
                <h1>Reporte de Pedidos — Amerishop</h1>
                <div class="periodo">Periodo: {{ $periodoLegible }}</div>
            </td>
            <td style="border:none; text-align:right; vertical-align:top; width:150px;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" style="max-width:140px; max-height:60px;">
                @endif
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Pedido</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Producto</th>
                <th class="num">Cant.</th>
                <th class="num">P. Unit.</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $fila)
                <tr>
                    <td>#{{ $fila['id'] }}</td>
                    <td>{{ $fila['fecha'] }}</td>
                    <td>{{ $fila['cliente'] }}</td>
                    <td>{{ $fila['estado'] }}</td>
                    <td>{{ $fila['producto'] }}</td>
                    <td class="num">{{ $fila['cantidad'] }}</td>
                    <td class="num">Q {{ number_format($fila['precioUnitario'], 2) }}</td>
                    <td class="num">Q {{ number_format($fila['subtotal'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No hay pedidos en este periodo.</td></tr>
            @endforelse
            @if(count($filas) > 0)
                <tr class="total-row">
                    <td colspan="7" class="num">TOTAL DEL PERIODO</td>
                    <td class="num">Q {{ number_format($totalGeneral, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">Generado el {{ $generadoEl }} — Amerishop</div>
</body>
</html>