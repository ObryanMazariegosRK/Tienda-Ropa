<?php

namespace App\Domain\Entities;

use DateTimeImmutable; 
use InvalidArgumentException; 
use App\Domain\Enum\OrderStatus;

class Order {
    private ?int $id;
    private int $userId;
    private int $addressId;
    private string $shippingAddress;
    private float $total;
    private OrderStatus $status;
    private DateTimeImmutable $createdAt; 

    public function __construct(
        ?int $id,
        int $userId,
        int $addressId,
        string $shippingAddress,
        float $total,
        ?OrderStatus $status = null, 
        ?DateTimeImmutable $createdAt = null 
    ) {
        $this->validateUserId($userId);
        $this->validateAddressId($addressId);
        $this->validateShippingAddress($shippingAddress);
        $this->validateTotal($total);

        $this->id = $id;
        $this->userId = $userId;
        $this->addressId = $addressId;
        $this->shippingAddress = $shippingAddress;
        $this->total = $total;
        //Asignación por defecto
        $this->status = $status ?? OrderStatus::RECEIVED; 
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    //VALIDACIONES

    private function validateUserId(int $userId): void {
        if ($userId <= 0) {
            throw new InvalidArgumentException('El identificador de usuario es inválido');
        }
    }

    private function validateAddressId(int $addressId): void {
        if ($addressId <= 0) {
            throw new InvalidArgumentException('El identificador de dirección es inválido');
        }
    }

    private function validateShippingAddress(string $shippingAddress): void {
        if (empty(trim($shippingAddress))) {
            throw new InvalidArgumentException('La dirección de envío es obligatoria');
        }
        if (strlen($shippingAddress) > 500) {
            throw new InvalidArgumentException('La dirección de envío no puede superar los 500 caracteres');
        }
    }

    private function validateTotal(float $total): void {
        if ($total <= 0) {
            throw new InvalidArgumentException('El total debe ser mayor a cero');
        }
    }

    //ESTADOS

    public function confirm(): void {
        if ($this->status === OrderStatus::CANCELLED) {
            throw new InvalidArgumentException('No puedes confirmar un pedido que ha sido cancelado.');
        }
        $this->status = OrderStatus::CONFIRMED;
    }

    public function prepare(): void { // Agregué el estado PREPARING que tenías en tu Enum
        if ($this->status === OrderStatus::CANCELLED) {
            throw new InvalidArgumentException('No puedes preparar un pedido cancelado.');
        }
        $this->status = OrderStatus::PREPARING;
    }

    public function markOnRoute(): void { // Corregido el nombre a OnRoute
        if ($this->status === OrderStatus::CANCELLED) {
            throw new InvalidArgumentException('No puedes enviar a ruta un pedido cancelado.');
        }
        $this->status = OrderStatus::ON_ROUTE; // Corregido: usando ON_ROUTE según tu Enum
    }

    public function deliver(): void {
        if ($this->status === OrderStatus::CANCELLED) {
            throw new InvalidArgumentException('No puedes entregar un pedido cancelado.');
        }
        $this->status = OrderStatus::DELIVERED;
    }

    public function cancel(): void {
        if ($this->status === OrderStatus::DELIVERED) {
            throw new InvalidArgumentException('No puedes cancelar un pedido que ya fue entregado al cliente.');
        }
        $this->status = OrderStatus::CANCELLED;
    }

    //GETTERS

    public function getId(): ?int {
        return $this->id;
    }

    public function getUserId(): int {
        return $this->userId;
    }

    public function getAddressId(): int {
        return $this->addressId;
    }

    public function getShippingAddress(): string {
        return $this->shippingAddress;
    }

    public function getTotal(): float {
        return $this->total;
    }

    public function getStatus(): OrderStatus {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable { 
        return $this->createdAt;
    }
}