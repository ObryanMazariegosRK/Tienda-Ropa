<?php
namespace App\Application\UseCases\Bitacora;

use App\Application\Abstractions\Bitacora\IListarBitacoraUseCase;
use App\Domain\Abstractions\IBitacoraRepository;
use Carbon\CarbonImmutable;

class ListarBitacoraUseCase implements IListarBitacoraUseCase
{
    private const POR_PAGINA = 20;

    public function __construct(private IBitacoraRepository $repository) {}

    public function execute(array $filtros, int $pagina): array
    {
        $zona = config('dw.zona_horaria');

        // Los días del filtro son de Guatemala; la bitácora guarda en UTC
        $desde = !empty($filtros['desde'])
            ? CarbonImmutable::parse($filtros['desde'], $zona)->startOfDay()->utc()->format('Y-m-d H:i:s') : null;
        $hasta = !empty($filtros['hasta'])
            ? CarbonImmutable::parse($filtros['hasta'], $zona)->addDay()->startOfDay()->utc()->format('Y-m-d H:i:s') : null;

        $resultado = $this->repository->listar([
            'desde' => $desde,
            'hasta' => $hasta,
            'accion' => $filtros['accion'] ?? null,
            'usuario' => $filtros['usuario'] ?? null,
        ], max(1, $pagina), self::POR_PAGINA);

        $resultado['acciones'] = $this->repository->acciones();
        return $resultado;
    }
}
