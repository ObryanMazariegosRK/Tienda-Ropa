<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

//Se envía a quien iba ganando una subasta cuando otra persona puja más
class PujaSuperadaNotification extends Notification
{
    public function __construct(
        private string $nombreProducto,
        private float $nuevoPrecio
    ) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        $precio = number_format($this->nuevoPrecio, 2);

        return (new WebPushMessage)
            ->title('⚠️ Superaron tu puja')
            ->body("Alguien ofreció Q{$precio} por {$this->nombreProducto}. ¡Puja de nuevo antes de que termine!")
            ->icon('/image/Notificacion.png')
            ->data(['url' => '/subastas']);
    }
}
