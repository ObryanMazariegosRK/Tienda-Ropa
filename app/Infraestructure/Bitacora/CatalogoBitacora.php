<?php

namespace App\Infraestructure\Bitacora;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

// Qué acciones van a la bitácora. Cada entrada se identifica por "MÉTODO uri-de-la-ruta".
//   accion, entidad   → cómo se clasifica
//   campos            → LISTA BLANCA de datos de la petición que se guardan (nunca contraseñas)
//   antes             → (opcional) cómo estaba el registro antes del cambio
//   describir         → texto legible para la pantalla de bitácora
//   registrarFallos   → si también se guarda cuando la operación es rechazada (ej. login fallido)
class CatalogoBitacora
{
    private const ESTADOS_PEDIDO = [
        'pending_payment' => 'Pendiente de pago', 'confirmed' => 'Confirmado', 'preparing' => 'En preparación',
        'on_route' => 'En camino', 'delivered' => 'Entregado', 'cancelled' => 'Cancelado',
    ];

    public function buscar(Request $request): ?array
    {
        $ruta = $request->route();
        if (!$ruta) {
            return null;
        }
        return $this->definiciones()[$request->method() . ' ' . $ruta->uri()] ?? null;
    }

    private function definiciones(): array
    {
        $producto = fn(Request $r) => DB::table('products')->where('id', $r->route('id'))
            ->first(['name', 'price', 'offer_price', 'cost', 'status', 'sale_type', 'category_id']);
        $categoria = fn(Request $r) => DB::table('categories')->where('id', $r->route('id'))
            ->first(['name', 'parent_category_id', 'is_active']);
        $grupoBanner = fn(Request $r) => DB::table('banner_groups')->where('id', $r->route('id') ?? $r->route('groupId'))
            ->first(['name', 'is_active']);
        $cuentaCorreo = fn(Request $r) => DB::table('mail_senders')->where('id', $r->route('id'))
            ->first(['from_name', 'email', 'host', 'port', 'is_active']);
        $nombreAntes = fn($antes, string $campo = 'name') => $antes ? "\"" . ($antes[$campo] ?? '') . "\"" : '';

        return [
            // ---------- Sesión y cuenta ----------
            'POST api/login' => [
                'accion' => 'sesion.inicio', 'entidad' => 'usuario', 'campos' => ['email'], 'registrarFallos' => true,
                'describir' => fn(Request $r, Response $resp) => match ($resp->getStatusCode()) {
                    200 => 'Inició sesión con correo y contraseña',
                    403 => "Intentó iniciar sesión con una cuenta sin verificar ({$r->input('email')})",
                    429 => "Demasiados intentos de inicio de sesión ({$r->input('email')})",
                    default => "Intento fallido de inicio de sesión ({$r->input('email')})",
                },
            ],
            'GET auth/{provider}/callback' => [
                'accion' => 'sesion.inicio_google', 'entidad' => 'usuario', 'campos' => [], 'registrarFallos' => true,
                'describir' => fn(Request $r, Response $resp) => str_contains((string) $resp->headers->get('Location'), '#token=')
                    ? 'Inició sesión con Google' : 'Intento fallido de inicio de sesión con Google',
            ],
            'POST api/logout' => ['accion' => 'sesion.cierre', 'entidad' => 'usuario', 'campos' => [],
                'describir' => fn() => 'Cerró sesión'],
            'POST api/register' => ['accion' => 'cuenta.registro', 'entidad' => 'usuario', 'campos' => ['email', 'name', 'last_name'],
                'describir' => fn(Request $r) => "Creó una cuenta con el correo {$r->input('email')}"],
            'POST api/reset-password' => ['accion' => 'cuenta.contrasena_restablecida', 'entidad' => 'usuario', 'campos' => ['email'],
                'registrarFallos' => true,
                'describir' => fn(Request $r, Response $resp) => $resp->isSuccessful()
                    ? "Restableció la contraseña de {$r->input('email')}"
                    : "Intento fallido de restablecer la contraseña de {$r->input('email')}"],

            // ---------- Pedidos y subastas (clientes) ----------
            'POST api/orders/checkout' => ['accion' => 'pedido.creado', 'entidad' => 'pedido', 'campos' => ['addressId'],
                'describir' => fn(Request $r, Response $resp, $antes, $id) => "Realizó el pedido #{$id}"],
            'POST api/auctions/{auctionId}/checkout' => ['accion' => 'subasta.pedido_confirmado', 'entidad' => 'subasta',
                'campos' => ['addressId'], 'id' => fn(Request $r) => $r->route('auctionId'),
                'describir' => fn(Request $r) => "Confirmó el pedido de la subasta ganada #{$r->route('auctionId')}"],
            'POST api/auctions/{auctionId}/decline' => ['accion' => 'subasta.rechazada_por_ganador', 'entidad' => 'subasta',
                'campos' => [], 'id' => fn(Request $r) => $r->route('auctionId'),
                'describir' => fn(Request $r) => "Rechazó el producto de la subasta ganada #{$r->route('auctionId')}"],

            // ---------- Administración: pedidos ----------
            'PATCH api/orders/{id}/status' => [
                'accion' => 'pedido.estado_cambiado', 'entidad' => 'pedido', 'campos' => ['status'],
                'antes' => fn(Request $r) => DB::table('orders')->where('id', $r->route('id'))->first(['status', 'public_code']),
                'describir' => function (Request $r, Response $resp, $antes) {
                    $codigo = $antes['public_code'] ?? "#{$r->route('id')}";
                    $de = self::ESTADOS_PEDIDO[$antes['status'] ?? ''] ?? ($antes['status'] ?? '?');
                    $a = self::ESTADOS_PEDIDO[$r->input('status')] ?? $r->input('status');
                    return "Cambió el pedido {$codigo} de \"{$de}\" a \"{$a}\"";
                },
            ],

            // ---------- Administración: productos ----------
            'POST api/products' => ['accion' => 'producto.creado', 'entidad' => 'producto',
                'campos' => ['name', 'price', 'offerPrice', 'cost', 'status', 'saleType', 'categoryId'],
                'describir' => fn(Request $r) => "Creó el producto \"{$r->input('name')}\""],
            'PUT api/products/{id}' => ['accion' => 'producto.editado', 'entidad' => 'producto',
                'campos' => ['name', 'price', 'offerPrice', 'cost', 'status', 'saleType', 'categoryId'], 'antes' => $producto,
                'describir' => fn(Request $r) => "Editó el producto \"{$r->input('name')}\""],
            'DELETE api/products/{id}' => ['accion' => 'producto.eliminado', 'entidad' => 'producto', 'campos' => [], 'antes' => $producto,
                'describir' => fn(Request $r, Response $resp, $antes) => "Eliminó el producto {$nombreAntes($antes)}"],
            'PATCH api/auctions/{auctionId}/extend-duration' => ['accion' => 'subasta.duracion_extendida', 'entidad' => 'subasta',
                'campos' => ['newEndDate'], 'id' => fn(Request $r) => $r->route('auctionId'),
                'antes' => fn(Request $r) => DB::table('auctions')->where('id', $r->route('auctionId'))->first(['end_date']),
                'describir' => fn(Request $r) => "Extendió la subasta #{$r->route('auctionId')} hasta {$r->input('newEndDate')}"],

            // ---------- Administración: categorías ----------
            'POST api/categories' => ['accion' => 'categoria.creada', 'entidad' => 'categoria',
                'campos' => ['name', 'parentCategoryId', 'isActive'],
                'describir' => fn(Request $r) => "Creó la categoría \"{$r->input('name')}\""],
            'PUT api/categories/{id}' => ['accion' => 'categoria.editada', 'entidad' => 'categoria',
                'campos' => ['name', 'parentCategoryId', 'isActive'], 'antes' => $categoria,
                'describir' => fn(Request $r, Response $resp, $antes) => "Editó la categoría {$nombreAntes($antes)}"],
            'DELETE api/categories/{id}' => ['accion' => 'categoria.eliminada', 'entidad' => 'categoria', 'campos' => [], 'antes' => $categoria,
                'describir' => fn(Request $r, Response $resp, $antes) => "Eliminó la categoría {$nombreAntes($antes)}"],

            // ---------- Administración: banners ----------
            'POST api/banner-groups' => ['accion' => 'banner.creado', 'entidad' => 'banner', 'campos' => ['name'],
                'describir' => fn(Request $r) => "Creó el grupo de banners \"{$r->input('name')}\""],
            'POST api/banner-groups/{id}/media' => ['accion' => 'banner.archivos_agregados', 'entidad' => 'banner', 'campos' => [],
                'antes' => $grupoBanner,
                'describir' => fn(Request $r, Response $resp, $antes) => "Agregó archivos al grupo de banners {$nombreAntes($antes)}"],
            'DELETE api/banner-groups/{groupId}/media/{mediaId}' => ['accion' => 'banner.archivo_quitado', 'entidad' => 'banner',
                'campos' => [], 'id' => fn(Request $r) => $r->route('groupId'), 'antes' => $grupoBanner,
                'describir' => fn(Request $r, Response $resp, $antes) => "Quitó un archivo del grupo de banners {$nombreAntes($antes)}"],
            'PATCH api/banner-groups/{id}/rename' => ['accion' => 'banner.renombrado', 'entidad' => 'banner', 'campos' => ['name'],
                'antes' => $grupoBanner,
                'describir' => fn(Request $r, Response $resp, $antes) => "Renombró el grupo de banners {$nombreAntes($antes)} a \"{$r->input('name')}\""],
            'DELETE api/banner-groups/{id}' => ['accion' => 'banner.eliminado', 'entidad' => 'banner', 'campos' => [], 'antes' => $grupoBanner,
                'describir' => fn(Request $r, Response $resp, $antes) => "Eliminó el grupo de banners {$nombreAntes($antes)}"],
            'PATCH api/banner-groups/{id}/toggle-active' => ['accion' => 'banner.activacion', 'entidad' => 'banner', 'campos' => [],
                'antes' => $grupoBanner,
                'describir' => fn(Request $r, Response $resp, $antes) => ($antes['is_active'] ?? false)
                    ? "Desactivó el grupo de banners {$nombreAntes($antes)}" : "Activó el grupo de banners {$nombreAntes($antes)}"],

            // ---------- Administración: correo de envío (sin la contraseña) ----------
            'POST api/admin/mail-senders' => ['accion' => 'correo.cuenta_agregada', 'entidad' => 'correo', 'campos' => ['fromName', 'email', 'host', 'port'],
                'describir' => fn(Request $r) => "Agregó la cuenta de correo {$r->input('email')}"],
            'PUT api/admin/mail-senders/{id}' => ['accion' => 'correo.cuenta_editada', 'entidad' => 'correo', 'campos' => ['fromName', 'email', 'host', 'port'],
                'antes' => $cuentaCorreo, 'describir' => fn(Request $r) => "Editó la cuenta de correo {$r->input('email')}"],
            'DELETE api/admin/mail-senders/{id}' => ['accion' => 'correo.cuenta_eliminada', 'entidad' => 'correo', 'campos' => [],
                'antes' => $cuentaCorreo,
                'describir' => fn(Request $r, Response $resp, $antes) => 'Eliminó la cuenta de correo ' . ($antes['email'] ?? '')],
            'PUT api/admin/mail-senders/active' => ['accion' => 'correo.cuenta_en_uso', 'entidad' => 'correo', 'campos' => ['id'],
                'id' => fn(Request $r) => $r->input('id'),
                'describir' => fn(Request $r) => $r->input('id') ? 'Cambió la cuenta de correo en uso' : 'Volvió al correo del servidor'],
        ];
    }
}
