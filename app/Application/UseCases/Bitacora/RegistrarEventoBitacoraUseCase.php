<?php
namespace App\Application\UseCases\Bitacora;

use App\Application\Abstractions\Bitacora\IRegistrarEventoBitacoraUseCase;
use App\Application\DTOs\Bitacora\EventoBitacoraDTO;
use App\Domain\Abstractions\IBitacoraRepository;

class RegistrarEventoBitacoraUseCase implements IRegistrarEventoBitacoraUseCase
{
    public function __construct(private IBitacoraRepository $repository) {}

    public function execute(EventoBitacoraDTO $evento): void
    {
        if (!config('dw.habilitado')) {
            return;
        }

        $this->repository->registrar([
            'ocurrido_en' => now()->format('Y-m-d H:i:s'),
            'usuario_id' => $evento->usuarioId,
            'usuario_correo' => $evento->usuarioCorreo ? mb_substr($evento->usuarioCorreo, 0, 150) : null,
            'usuario_rol' => $evento->usuarioRol,
            'accion' => $evento->accion,
            'entidad' => $evento->entidad,
            'entidad_id' => $evento->entidadId,
            'descripcion' => mb_substr($evento->descripcion, 0, 500),
            'resultado' => $evento->resultado,
            'datos_antes' => $evento->datosAntes,
            'datos_despues' => $evento->datosDespues,
            'ip' => $evento->ip,
            'user_agent' => $evento->userAgent ? mb_substr($evento->userAgent, 0, 300) : null,
        ]);
    }
}
