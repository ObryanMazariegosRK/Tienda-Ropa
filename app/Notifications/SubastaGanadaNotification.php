<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class SubastaGanadaNotification extends Notification
{
    public function __construct(
        private ?string $nombreProducto = null
    ) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('🏆 ¡Ganaste la subasta!')
            ->body($this->nombreProducto
                ? "Felicidades, ganaste: {$this->nombreProducto}"
                : "Felicidades, ganaste una subasta. Revísala en la sección de subastas.")
            ->icon('/image/Notificacion.png')
            ->data(['url' => '/subastas']);
    }
}