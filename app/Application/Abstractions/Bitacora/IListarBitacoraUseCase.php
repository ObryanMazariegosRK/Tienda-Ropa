<?php
namespace App\Application\Abstractions\Bitacora;

interface IListarBitacoraUseCase
{
    /** $filtros: desde, hasta (Y-m-d, hora de Guatemala), accion, usuario */
    public function execute(array $filtros, int $pagina): array;
}
