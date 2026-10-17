<?php

// Data warehouse y bitácora (rama app-movil). Ver docs/DATA-WAREHOUSE.md
return [
    // Si es false no se escribe en la bitácora ni se carga el warehouse (los tests lo apagan)
    'habilitado' => env('DW_HABILITADO', true),

    // Zona horaria para agrupar las ventas por día (la base guarda todo en UTC)
    'zona_horaria' => 'America/Guatemala',
];
