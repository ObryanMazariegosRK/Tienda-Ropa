<?php

namespace App\Infraestructure\Notifications; 

use App\Domain\Abstractions\IPushNotifier;
use App\Models\User as UserModel;
use App\Notifications\NuevoPedidoNotification;
use App\Notifications\CambioEstadoNotification;
use App\Notifications\SubastaGanadaNotification;
use App\Notifications\PujaSuperadaNotification;
use App\Domain\Enum\RoleType; 

class PushNotifier implements IPushNotifier
{
    public function notificarNuevoPedido(string $publicCode, float $total): void
    {
        $admins = UserModel::where('role', RoleType::ADMIN->value)->get();
        foreach ($admins as $admin) {
            $admin->notify(new NuevoPedidoNotification($publicCode, $total));
        }
    }

    public function notificarCambioEstado(int $userId, string $publicCode, string $estado): void
    {
        $user = UserModel::find($userId);
        $user?->notify(new CambioEstadoNotification($publicCode, $estado));
    }

    public function notificarSubastaGanada(int $userId, ?string $nombreProducto = null): void
    {
        $user = UserModel::find($userId);
        $user?->notify(new SubastaGanadaNotification($nombreProducto));
    }

    public function notificarPujaSuperada(int $userId, string $nombreProducto, float $nuevoPrecio): void
    {
        $user = UserModel::find($userId);
        $user?->notify(new PujaSuperadaNotification($nombreProducto, $nuevoPrecio));
    }
}