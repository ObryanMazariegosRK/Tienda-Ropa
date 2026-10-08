<?php 

namespace App\Domain\Abstractions;

interface IPushNotifier
{
    public function notificarNuevoPedido(string $publicCode, float $total): void;
    public function notificarCambioEstado(int $userId, string $publicCode, string $estado): void;
    public function notificarSubastaGanada(int $userId, ?string $nombreProducto = null): void;
}