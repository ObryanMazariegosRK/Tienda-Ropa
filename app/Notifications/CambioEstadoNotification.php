<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class CambioEstadoNotification extends Notification
{
    public function __construct(
        private string $publicCode,
        private string $estado
    ) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('📦 Tu pedido cambió de estado')
            ->body("Pedido {$this->publicCode}: {$this->estado}")
            ->icon('/image/Notificacion.png')
            ->data(['url' => '/pedido/' . $this->publicCode]);
    }
}