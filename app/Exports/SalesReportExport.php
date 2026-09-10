<?php

namespace App\Exports;

use App\Application\Abstractions\Dashboard\IGetRevenueReportUseCase;
use App\Application\Abstractions\Dashboard\IGetRevenueBySaleTypeUseCase;
use App\Application\Abstractions\Dashboard\IGetTopCategoriesUseCase;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class SalesReportExport implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        private string $startDate,
        private string $endDate,
        private IGetRevenueReportUseCase $revenueReport,
        private IGetRevenueBySaleTypeUseCase $revenueBySaleType,
        private IGetTopCategoriesUseCase $topCategories
    ) {}

    public function array(): array
    {
        $revenue = $this->revenueReport->execute($this->startDate, $this->endDate);
        $porTipo = $this->revenueBySaleType->execute($this->startDate, $this->endDate);
        $categorias = $this->topCategories->execute($this->startDate, $this->endDate);

        $filas = [];

        // Fila 1: título
        $filas[] = ['REPORTE DE VENTAS'];
        // Fila 2: periodo
        $filas[] = ['Periodo:', Carbon::parse($this->startDate)->format('d/m/Y') . ' al ' . Carbon::parse($this->endDate)->format('d/m/Y')];
        // Fila 3: RESUMEN GENERAL
        $filas[] = ['RESUMEN GENERAL'];

        // Fila 4: INGRESOS POR TIPO DE VENTA (sección verde)
        $filas[] = ['INGRESOS POR TIPO DE VENTA'];
        // Fila 5: Venta directa
        $filas[] = ['Venta directa', 'Q ' . number_format($porTipo->directRevenue, 2)];
        // Fila 6: Subastas
        $filas[] = ['Subastas', 'Q ' . number_format($porTipo->auctionRevenue, 2)];
        // Fila 7: Ingresos totales
        $filas[] = ['Ingresos totales', 'Q ' . number_format($revenue->totalRevenue, 2)];
        // Fila 8: Ganancia total
        $filas[] = ['Ganancia total', 'Q ' . number_format($revenue->totalProfit, 2)];

        // Fila 9: TOP CATEGORÍAS (sección verde)
        $filas[] = ['TOP CATEGORÍAS CON MÁS VENTAS'];
        // Fila 10: encabezado de la tabla
        $filas[] = ['Categoría', 'Ingresos'];
        // Fila 11+: las categorías
        if (count($categorias) === 0) {
            $filas[] = ['(Sin ventas en este periodo)', ''];
        } else {
            foreach ($categorias as $cat) {
                $filas[] = [$cat->categoryName, 'Q ' . number_format($cat->revenue, 2)];
            }
        }

        return $filas;
    }

    public function title(): string
    {
        return 'Resumen de ventas';
    }

    public function styles(Worksheet $sheet): array
    {
        // Fila 1: título grande
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '16A34A']],
        ]);

        // Filas 2 y 3: "Periodo: fecha" y "RESUMEN GENERAL" en negrita
        $sheet->getStyle('A2:B2')->applyFromArray(['font' => ['bold' => true]]);
        $sheet->getStyle('A3')->applyFromArray(['font' => ['bold' => true]]);

        // Secciones con fondo verde y texto blanco: A4 y A9
        foreach (['A4', 'A9'] as $celda) {
            $sheet->getStyle($celda)->applyFromArray([
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '16A34A'],
                ],
            ]);
        }

        // Fila 10: encabezado de la tabla de categorías (verde más claro)
        $sheet->getStyle('A10:B10')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '22C55E']],
        ]);

        // Números de Ingresos totales (B7) y Ganancia total (B8) en negrita
        $sheet->getStyle('B7:B8')->applyFromArray(['font' => ['bold' => true]]);

        return [];
    }
}