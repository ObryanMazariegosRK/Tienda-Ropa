<?php

namespace App\Http\Middleware;

use App\Application\Abstractions\Bitacora\IRegistrarEventoBitacoraUseCase;
use App\Application\DTOs\Bitacora\EventoBitacoraDTO;
use App\Infraestructure\Bitacora\CatalogoBitacora;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

// Registra en la bitácora (tienda_dw) las acciones de CatalogoBitacora. Si la bitácora
// falla, la operación del usuario sigue normal: nunca se interrumpe una venta por esto.
class RegistrarBitacora
{
    public function __construct(
        private CatalogoBitacora $catalogo,
        private IRegistrarEventoBitacoraUseCase $registrar
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $definicion = config('dw.habilitado') ? $this->catalogo->buscar($request) : null;
        if (!$definicion) {
            return $next($request);
        }

        // Cómo estaba el registro antes del cambio (se lee antes de ejecutar la acción)
        $antes = isset($definicion['antes'])
            ? rescue(fn() => ($r = ($definicion['antes'])($request)) ? (array) $r : null, null, false)
            : null;

        $response = $next($request);

        if ($response->isSuccessful() || $response->isRedirection() || !empty($definicion['registrarFallos'])) {
            rescue(fn() => $this->registrar($request, $response, $definicion, $antes));
        }

        return $response;
    }

    private function registrar(Request $request, Response $response, array $definicion, ?array $antes): void
    {
        $usuario = $this->usuario($request, $response);
        $idEntidad = isset($definicion['id']) ? ($definicion['id'])($request) : ($request->route('id') ?? $this->idCreado($response));

        $despues = $request->only($definicion['campos']);
        // El login con Google siempre redirige: tuvo éxito solo si la redirección trae el token
        $exito = $definicion['accion'] === 'sesion.inicio_google'
            ? str_contains((string) $response->headers->get('Location'), '#token=')
            : ($response->isSuccessful() || $response->isRedirection());

        $this->registrar->execute(new EventoBitacoraDTO(
            accion: $definicion['accion'] . (!$exito && str_starts_with($definicion['accion'], 'sesion.inicio') ? '_fallida' : ''),
            descripcion: ($definicion['describir'])($request, $response, $antes, $idEntidad),
            resultado: $exito ? 'exito' : 'rechazado',
            usuarioId: $usuario?->id,
            usuarioCorreo: $usuario->email ?? $request->input('email'),
            // El modelo User convierte el rol a un enum (RoleType)
            usuarioRol: ($rol = $usuario->role ?? null) instanceof \BackedEnum ? $rol->value : $rol,
            entidad: $definicion['entidad'],
            entidadId: $idEntidad !== null ? (string) $idEntidad : ($usuario ? (string) $usuario->id : null),
            datosAntes: $antes,
            datosDespues: $despues ?: null,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        ));
    }

    // Quién hizo la acción: el usuario autenticado o, en los inicios de sesión, la cuenta que entró
    private function usuario(Request $request, Response $response): ?object
    {
        if ($usuario = $request->user()) {
            return $usuario;
        }

        // Login con correo: la respuesta trae el token recién creado
        $token = json_decode((string) $response->getContent(), true)['token'] ?? null;
        // Login con Google: el token viaja en el fragmento de la redirección
        if (!$token && preg_match('/#token=([^&]+)/', (string) $response->headers->get('Location'), $m)) {
            $token = urldecode($m[1]);
        }

        return $token ? PersonalAccessToken::findToken($token)?->tokenable : null;
    }

    private function idCreado(Response $response): ?string
    {
        $json = json_decode((string) $response->getContent(), true);
        $id = $json['data']['id'] ?? $json['data']['order']['id'] ?? $json['id'] ?? null;
        return $id !== null ? (string) $id : null;
    }
}
