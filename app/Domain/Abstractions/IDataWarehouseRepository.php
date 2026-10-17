<?php

namespace App\Domain\Abstractions;

// Escritura del data warehouse (L de Cargar). Solo INSERT: nada se modifica ni se borra.
interface IDataWarehouseRepository
{
    /** @return array<string, true> claves "pedidoItemId|tipoMovimiento" ya cargadas */
    public function movimientosCargados(): array;

    /** @return array<int, object> versión vigente de cada producto, indexada por producto_id */
    public function versionesActualesProductos(array $productoIds): array;
    public function insertarVersionProducto(array $fila): int;

    /** @return array<int, object> versión vigente de cada cliente, indexada por usuario_id */
    public function versionesActualesClientes(array $usuarioIds): array;
    public function insertarVersionCliente(array $fila): int;

    /** @return int[] fecha_id que ya existen */
    public function fechasExistentes(array $fechaIds): array;
    public function insertarFechas(array $filas): void;

    public function insertarVentas(array $filas): void;

    /** @return int[] ids de subasta ya cargados */
    public function subastasCargadas(): array;
    public function insertarSubastas(array $filas): void;

    public function registrarCarga(array $fila): void;
    public function ultimaCarga(): ?object;
}
