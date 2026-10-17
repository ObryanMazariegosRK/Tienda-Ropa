<?php
namespace App\Application\Abstractions\DataWarehouse;

interface IObtenerAnalisisUseCase
{
    // $meses = 0 → todo el historial
    public function execute(int $meses): array;
}
