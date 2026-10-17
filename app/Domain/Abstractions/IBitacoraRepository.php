<?php

namespace App\Domain\Abstractions;

// Bitácora inmutable (base tienda_dw): solo se agregan y se consultan registros
interface IBitacoraRepository
{
    public function registrar(array $evento): void;

    /** @return array{data: array, meta: array} */
    public function listar(array $filtros, int $pagina, int $porPagina): array;

    /** @return string[] acciones distintas registradas (para el filtro) */
    public function acciones(): array;
}
