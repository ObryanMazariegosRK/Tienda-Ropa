<?php

namespace App\Console\Commands;

use App\Models\OrderModel;
use Illuminate\Console\Command;

class BackfillConfirmedAt extends Command
{
    protected $signature = 'orders:backfill-confirmed-at';
    protected $description = 'Rellena confirmed_at con created_at para pedidos existentes que ya estén en un estado de ingreso real (solo por única vez, para pedidos creados antes de este cambio).';

    private const REVENUE_STATUSES = ['confirmed', 'preparing', 'on_route', 'delivered'];

    public function handle(): int
    {
        $pedidos = OrderModel::whereIn('status', self::REVENUE_STATUSES)
            ->whereNull('confirmed_at')
            ->get();

        if ($pedidos->isEmpty()) {
            $this->info('No hay pedidos pendientes de rellenar. Todo está al día.');
            return self::SUCCESS;
        }

        $this->warn("Se van a actualizar {$pedidos->count()} pedido(s), usando su fecha de creación (created_at) como aproximación de confirmed_at.");

        if (!$this->confirm('¿Continuar?', true)) {
            $this->info('Cancelado, no se modificó nada.');
            return self::SUCCESS;
        }

        foreach ($pedidos as $pedido) {
            $pedido->update(['confirmed_at' => $pedido->created_at]);
            $this->line("Pedido #{$pedido->id} → confirmed_at = {$pedido->created_at}");
        }

        $this->info("Listo: {$pedidos->count()} pedido(s) actualizados.");
        return self::SUCCESS;
    }
}