<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\IDataWarehouseRepository;
use Illuminate\Support\Facades\DB;

// Carga del data warehouse por la conexión "dw" (solo SELECT e INSERT)
class DataWarehouseRepository implements IDataWarehouseRepository
{
    private function dw()
    {
        return DB::connection('dw');
    }

    public function movimientosCargados(): array
    {
        $claves = [];
        foreach ($this->dw()->table('hechos_ventas')->get(['pedido_item_id', 'tipo_movimiento']) as $fila) {
            $claves["{$fila->pedido_item_id}|{$fila->tipo_movimiento}"] = true;
        }
        return $claves;
    }

    public function versionesActualesProductos(array $productoIds): array
    {
        return $this->versionesActuales('dim_producto', 'producto_key', 'producto_id', $productoIds);
    }

    public function insertarVersionProducto(array $fila): int
    {
        return (int) $this->dw()->table('dim_producto')->insertGetId($fila, 'producto_key');
    }

    public function versionesActualesClientes(array $usuarioIds): array
    {
        return $this->versionesActuales('dim_cliente', 'cliente_key', 'usuario_id', $usuarioIds);
    }

    public function insertarVersionCliente(array $fila): int
    {
        return (int) $this->dw()->table('dim_cliente')->insertGetId($fila, 'cliente_key');
    }

    // La versión vigente es la última agregada (la de mayor clave)
    private function versionesActuales(string $tabla, string $clave, string $idOrigen, array $ids): array
    {
        if (empty($ids)) return [];

        $ultimas = $this->dw()->table($tabla)->whereIn($idOrigen, $ids)
            ->select($idOrigen, DB::raw("MAX({$clave}) as ultima"))->groupBy($idOrigen);

        return $this->dw()->table("{$tabla} as d")
            ->joinSub($ultimas, 'u', 'u.ultima', '=', "d.{$clave}")
            ->get(['d.*'])->keyBy($idOrigen)->all();
    }

    public function fechasExistentes(array $fechaIds): array
    {
        if (empty($fechaIds)) return [];
        return $this->dw()->table('dim_fecha')->whereIn('fecha_id', $fechaIds)->pluck('fecha_id')->map(fn($v) => (int) $v)->all();
    }

    public function insertarFechas(array $filas): void
    {
        foreach (array_chunk($filas, 500) as $lote) {
            $this->dw()->table('dim_fecha')->insertOrIgnore($lote);
        }
    }

    public function insertarVentas(array $filas): void
    {
        // insertOrIgnore: si una carga se repite, el índice único evita duplicados sin modificar nada
        foreach (array_chunk($filas, 500) as $lote) {
            $this->dw()->table('hechos_ventas')->insertOrIgnore($lote);
        }
    }

    public function subastasCargadas(): array
    {
        return $this->dw()->table('hechos_subastas')->pluck('subasta_id')->map(fn($v) => (int) $v)->all();
    }

    public function insertarSubastas(array $filas): void
    {
        foreach (array_chunk($filas, 500) as $lote) {
            $this->dw()->table('hechos_subastas')->insertOrIgnore($lote);
        }
    }

    public function registrarCarga(array $fila): void
    {
        $this->dw()->table('etl_cargas')->insert($fila);
    }

    public function ultimaCarga(): ?object
    {
        return $this->dw()->table('etl_cargas')->orderByDesc('id')->first();
    }
}
