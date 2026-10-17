<?php
namespace App\Application\DTOs\Bitacora;

class EventoBitacoraDTO
{
    public function __construct(
        public readonly string $accion,
        public readonly string $descripcion,
        public readonly string $resultado,
        public readonly ?int $usuarioId = null,
        public readonly ?string $usuarioCorreo = null,
        public readonly ?string $usuarioRol = null,
        public readonly ?string $entidad = null,
        public readonly ?string $entidadId = null,
        public readonly ?array $datosAntes = null,
        public readonly ?array $datosDespues = null,
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null
    ) {}
}
