<?php
namespace App\Application\UseCases\DataWarehouse;

use App\Application\Abstractions\DataWarehouse\ICargarDataWarehouseUseCase;
use App\Domain\Abstractions\IDataWarehouseRepository;
use App\Domain\Abstractions\IOrigenDatosRepository;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

// ETL incremental: Extrae de la base de la tienda, Transforma al esquema estrella y Carga
// en tienda_dw. Se puede correr las veces que sea: solo agrega lo que falta.
class CargarDataWarehouseUseCase implements ICargarDataWarehouseUseCase
{
    private const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    private const DIAS = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    private int $versionesNuevas = 0;
    private string $ahora;

    public function __construct(
        private IOrigenDatosRepository $origen,
        private IDataWarehouseRepository $dw
    ) {}

    public function execute(): array
    {
        $inicio = now();
        $this->ahora = $inicio->format('Y-m-d H:i:s');
        $this->versionesNuevas = 0;

        try {
            [$ventas, $anulaciones] = $this->cargarVentas();
            $subastas = $this->cargarSubastas();

            $resumen = ['ventas' => $ventas, 'anulaciones' => $anulaciones, 'subastas' => $subastas, 'versiones_dimension' => $this->versionesNuevas];
            $this->dw->registrarCarga($resumen + ['iniciado_en' => $this->ahora, 'terminado_en' => now()->format('Y-m-d H:i:s'), 'estado' => 'ok']);
            return $resumen;
        } catch (\Throwable $e) {
            rescue(fn() => $this->dw->registrarCarga([
                'iniciado_en' => $this->ahora, 'terminado_en' => now()->format('Y-m-d H:i:s'), 'estado' => 'error',
                'ventas' => 0, 'anulaciones' => 0, 'subastas' => 0, 'versiones_dimension' => $this->versionesNuevas,
                'mensaje' => mb_substr($e->getMessage(), 0, 500),
            ]), null, false);
            throw $e;
        }
    }

    // ---------- Ventas y anulaciones ----------
    private function cargarVentas(): array
    {
        $cargados = $this->dw->movimientosCargados();
        $nuevas = [];
        $anulaciones = [];

        foreach ($this->origen->lineasDeVenta() as $linea) {
            $tieneVenta = isset($cargados["{$linea->item_id}|venta"]);
            if (!$tieneVenta) {
                $nuevas[] = $linea;
            }
            // Pedido cancelado después de confirmarse: se agrega su anulación (nunca se borra la venta)
            if ($linea->status === 'cancelled' && !isset($cargados["{$linea->item_id}|anulacion"])) {
                $anulaciones[] = $linea;
            }
        }

        $todas = array_merge($nuevas, $anulaciones);
        if (empty($todas)) {
            return [0, 0];
        }

        $productos = $this->resolverProductos(array_unique(array_map(fn($l) => (int) $l->product_id, $todas)));
        $clientes = $this->resolverClientes(array_unique(array_map(fn($l) => (int) $l->user_id, $todas)));

        $filas = [];
        foreach ($nuevas as $linea) {
            $filas[] = $this->filaVenta($linea, 'venta', $linea->confirmed_at, $productos, $clientes);
        }
        foreach ($anulaciones as $linea) {
            // La fecha de la anulación es cuando el pedido pasó a cancelado (su última actualización)
            $filas[] = $this->filaVenta($linea, 'anulacion', $linea->updated_at ?? $linea->confirmed_at, $productos, $clientes);
        }

        $this->asegurarFechas(array_map(fn($f) => $f['fecha_id'], $filas));
        $this->dw->insertarVentas($filas);

        return [count($nuevas), count($anulaciones)];
    }

    private function filaVenta(object $linea, string $tipo, string $ocurrido, array $productos, array $clientes): array
    {
        $signo = $tipo === 'anulacion' ? -1 : 1;
        $cantidad = (int) $linea->quantity;
        $ingreso = round((float) $linea->unit_price * $cantidad, 2);
        $costo = $linea->unit_cost !== null ? round((float) $linea->unit_cost * $cantidad, 2) : null;
        $publicado = $productos[$linea->product_id]['publicado_en'];

        $dias = null;
        if ($tipo === 'venta' && $publicado) {
            $dias = max(0, (int) floor((Carbon::parse($linea->confirmed_at)->getTimestamp() - Carbon::parse($publicado)->getTimestamp()) / 86400));
        }

        return [
            'tipo_movimiento' => $tipo,
            'pedido_item_id' => $linea->item_id,
            'pedido_id' => $linea->pedido_id,
            'pedido_codigo' => $linea->public_code,
            'fecha_id' => $this->fechaId($ocurrido),
            'producto_key' => $productos[$linea->product_id]['key'],
            'cliente_key' => $clientes[$linea->user_id],
            'tipo_venta' => $linea->es_subasta ? 'auction' : 'direct',
            'cantidad' => $signo * $cantidad,
            'ingreso' => $signo * $ingreso,
            'costo' => $costo !== null ? $signo * $costo : null,
            'ganancia' => $costo !== null ? $signo * round($ingreso - $costo, 2) : null,
            'dias_para_vender' => $dias,
            'ocurrido_en' => Carbon::parse($ocurrido)->format('Y-m-d H:i:s'),
            'cargado_en' => $this->ahora,
        ];
    }

    // ---------- Subastas ----------
    private function cargarSubastas(): int
    {
        $cargadas = array_flip($this->dw->subastasCargadas());
        $pendientes = array_values(array_filter($this->origen->subastasConResultado(), fn($s) => !isset($cargadas[$s->id])));
        if (empty($pendientes)) {
            return 0;
        }

        $productos = $this->resolverProductos(array_unique(array_map(fn($s) => (int) $s->product_id, $pendientes)));

        $filas = [];
        foreach ($pendientes as $s) {
            $resultado = match (true) {
                $s->status === 'cancelled' => 'cancelada',
                $s->order_id !== null => 'vendida',
                (int) $s->total_pujas === 0 => 'sin_pujas',
                default => 'no_reclamada',
            };
            $inicial = (float) $s->starting_price;
            $final = $resultado === 'vendida' ? (float) $s->current_price : null;

            $filas[] = [
                'subasta_id' => $s->id,
                'producto_key' => $productos[$s->product_id]['key'],
                'fecha_id' => $this->fechaId($s->end_date),
                'resultado' => $resultado,
                'precio_inicial' => $inicial,
                'precio_final' => $final,
                'incremento_pct' => $final !== null && $inicial > 0 ? round(($final - $inicial) / $inicial * 100, 2) : null,
                'total_pujas' => (int) $s->total_pujas,
                'postores' => (int) $s->postores,
                'cargado_en' => $this->ahora,
            ];
        }

        $this->asegurarFechas(array_map(fn($f) => $f['fecha_id'], $filas));
        $this->dw->insertarSubastas($filas);

        return count($filas);
    }

    // ---------- Dimensiones (versionadas, sin updates) ----------

    /** @return array<int, array{key: int, publicado_en: ?string}> */
    private function resolverProductos(array $ids): array
    {
        $actuales = $this->origen->productos($ids);
        $versiones = $this->dw->versionesActualesProductos($ids);
        $resultado = [];

        foreach ($ids as $id) {
            $origen = $actuales[$id] ?? null;
            $datos = [
                'producto_id' => $id,
                'nombre' => $origen->name ?? "Producto #{$id} (eliminado)",
                'categoria' => $origen->categoria ?? 'Sin categoría',
                'categoria_principal' => $origen->categoria_principal ?? 'Sin categoría',
                'publicado_en' => $origen?->created_at,
            ];
            $vigente = $versiones[$id] ?? null;

            // Si el nombre o la categoría cambiaron, se agrega una versión nueva
            if (!$vigente || $vigente->nombre !== $datos['nombre'] || $vigente->categoria !== $datos['categoria']
                || $vigente->categoria_principal !== $datos['categoria_principal']) {
                $key = $this->dw->insertarVersionProducto($datos + ['valido_desde' => $this->ahora]);
                $this->versionesNuevas++;
            } else {
                $key = (int) $vigente->producto_key;
            }

            $resultado[$id] = ['key' => $key, 'publicado_en' => $datos['publicado_en'] ?? $vigente?->publicado_en];
        }

        return $resultado;
    }

    /** @return array<int, int> usuario_id => cliente_key */
    private function resolverClientes(array $ids): array
    {
        $actuales = $this->origen->usuarios($ids);
        $versiones = $this->dw->versionesActualesClientes($ids);
        $resultado = [];

        foreach ($ids as $id) {
            $metodo = ($actuales[$id]->provider ?? null) ? 'google' : 'correo';
            $vigente = $versiones[$id] ?? null;

            if (!$vigente || $vigente->metodo_registro !== $metodo) {
                $resultado[$id] = $this->dw->insertarVersionCliente([
                    'usuario_id' => $id,
                    'registrado_en' => $actuales[$id]->created_at ?? null,
                    'metodo_registro' => $metodo,
                    'valido_desde' => $this->ahora,
                ]);
                $this->versionesNuevas++;
            } else {
                $resultado[$id] = (int) $vigente->cliente_key;
            }
        }

        return $resultado;
    }

    // ---------- Dimensión fecha ----------

    // Día en hora de Guatemala (la base guarda en UTC): una venta de las 8 p. m. es de ese día
    private function fechaId(string $fechaUtc): int
    {
        return (int) CarbonImmutable::parse($fechaUtc, 'UTC')->setTimezone(config('dw.zona_horaria'))->format('Ymd');
    }

    private function asegurarFechas(array $fechaIds): void
    {
        $fechaIds = array_values(array_unique($fechaIds));
        $faltantes = array_diff($fechaIds, $this->dw->fechasExistentes($fechaIds));

        $this->dw->insertarFechas(array_map(function ($fechaId) {
            $f = CarbonImmutable::createFromFormat('Ymd', (string) $fechaId)->startOfDay();
            return [
                'fecha_id' => $fechaId,
                'fecha' => $f->format('Y-m-d'),
                'anio' => $f->year,
                'trimestre' => $f->quarter,
                'mes' => $f->month,
                'nombre_mes' => self::MESES[$f->month],
                'dia' => $f->day,
                'dia_semana' => $f->dayOfWeekIso,
                'nombre_dia' => self::DIAS[$f->dayOfWeekIso],
                'es_fin_de_semana' => $f->dayOfWeekIso >= 6,
            ];
        }, array_values($faltantes)));
    }
}
