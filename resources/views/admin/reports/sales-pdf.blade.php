<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { color: #1f2937; font-size: 12px; }
        h1 { font-size: 20px; color: #16A34A; margin-bottom: 2px; }
        .periodo { color: #6b7280; font-size: 11px; margin-bottom: 20px; }
        h2 { font-size: 13px; background: #f3f4f6; padding: 6px 10px; margin: 18px 0 8px 0; border-left: 4px solid #16A34A; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 6px 10px; text-align: left; }
        .resumen td:last-child { text-align: right; font-weight: bold; }
        .top-table th { background: #16A34A; color: #fff; font-size: 11px; }
        .top-table td, .top-table th { border-bottom: 1px solid #e5e7eb; }
        .top-table td:last-child,
        .top-table th:last-child { text-align: right; }
        .destacado { font-size: 16px; color: #16A34A; font-weight: bold; }
        .footer { margin-top: 30px; font-size: 10px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <table style="width:100%; border:none; margin-bottom:20px;">
        <tr>
            <td style="border:none; vertical-align:top;">
                <h1>Reporte de Ventas — Amerishop</h1>
                <div class="periodo">Periodo: {{ $periodoLegible }}</div>
            </td>
            <td style="border:none; text-align:right; vertical-align:top; width:150px;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" style="max-width:140px; max-height:60px;">
                @endif
            </td>
        </tr>
    </table>

    <h2>Resumen general</h2>
    <table class="resumen">
        <tr><td>Ingresos totales</td><td class="destacado">Q {{ number_format($revenue->totalRevenue, 2) }}</td></tr>
        <tr><td>Ganancia total</td><td>Q {{ number_format($revenue->totalProfit, 2) }}</td></tr>
    </table>

    <h2>Ingresos por tipo de venta</h2>
    <table class="resumen">
        <tr><td>Venta directa</td><td>Q {{ number_format($porTipo->directRevenue, 2) }}</td></tr>
        <tr><td>Subastas</td><td>Q {{ number_format($porTipo->auctionRevenue, 2) }}</td></tr>
    </table>

    <h2>Top categorías con más ventas</h2>
    <table class="top-table">
        <thead>
            <tr><th>Categoría</th><th>Ingresos</th></tr>
        </thead>
        <tbody>
            @forelse ($categorias as $cat)
                <tr>
                    <td>{{ $cat->categoryName }}</td>
                    <td>Q {{ number_format($cat->revenue, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Sin ventas en este periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ $generadoEl }} — Amerishop
    </div>
</body>
</html>