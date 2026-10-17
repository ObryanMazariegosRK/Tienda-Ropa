<?php

namespace App\Domain\Abstractions;

// Consultas de análisis sobre el data warehouse (esquema estrella)
interface IAnalisisRepository
{
    public function ventasPorMes(int $desdeFechaId): array;
    public function ventasPorDiaSemana(int $desde, int $hasta): array;
    public function margenPorCategoria(int $desde, int $hasta): array;
    public function subastasPorResultado(int $desde, int $hasta): array;
    /** @return array<int, int> primera fecha_id de compra de cada usuario */
    public function primeraCompraPorCliente(): array;
    /** @return int[] usuarios que compraron en el rango */
    public function clientesConCompras(int $desde, int $hasta): array;
    public function totalesDelRango(int $desde, int $hasta): object;
    public function tiempoDeVentaPorCategoria(int $desde, int $hasta): array;
}
