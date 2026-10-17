<?php

use App\Console\Commands\CloseExpiredAuctions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


//withoutOverlapping evita que dos ejecuciones procesen la misma subasta
//si una tarda más de lo normal (por ejemplo, enviando notificaciones)
Schedule::command(CloseExpiredAuctions::class)->everyMinute()->withoutOverlapping();

Schedule::command(\App\Console\Commands\ExpireUnclaimedAuctionWins::class)->hourly()->withoutOverlapping();

//Data warehouse (rama app-movil): carga incremental de ventas y subastas cada hora
Schedule::command('dw:cargar')->hourly()->withoutOverlapping();

//Borra de la db los tokens de Sanctum que ya vencieron
Schedule::command('sanctum:prune-expired --hours=24')->daily();