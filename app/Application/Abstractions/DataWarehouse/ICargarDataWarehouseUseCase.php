<?php
namespace App\Application\Abstractions\DataWarehouse;

interface ICargarDataWarehouseUseCase
{
    /** @return array{ventas: int, anulaciones: int, subastas: int, versiones_dimension: int} */
    public function execute(): array;
}
