<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NuevoPedidoNotification extends Notification
{
    public function __construct(
        private string $publicCode,
        private float $total
    ) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('🛒 Nuevo pedido')
            ->body("Pedido {$this->publicCode} · Q" . number_format($this->total, 2))
            ->icon('/image/Notificacion.png')
            ->data(['url' => '/pedido/' . $this->publicCode]);
    }
}