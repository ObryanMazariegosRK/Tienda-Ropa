<?php

namespace App\Domain\Abstractions;

// Lectura de la base de la TIENDA para el ETL del data warehouse (E de Extraer)
interface IOrigenDatosRepository
{
    /** Líneas de los pedidos que alguna vez se confirmaron (incluye los cancelados después) */
    public function lineasDeVenta(): array;

    /** @return array<int, object> productos indexados por id, con su categoría y categoría principal */
    public function productos(array $ids): array;

    /** @return array<int, object> usuarios indexados por id */
    public function usuarios(array $ids): array;

    /** Subastas terminadas o canceladas cuyo resultado ya es definitivo */
    public function subastasConResultado(): array;
}
