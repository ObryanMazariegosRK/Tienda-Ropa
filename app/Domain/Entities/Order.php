<?php

namespace App\Domain\Entities;

use DateTimeImmutable; 
use InvalidArgumentException; 
use App\Domain\Enum\OrderStatus;

class Order {
    private ?int $id;
    private int $userId;
    private ?int $addressId;
    private string $shippingAddress;
    private float $total;
    private OrderStatus $status;
    private DateTimeImmutable $createdAt; 
    private ?DateTimeImmutable $confirmedAt;
    private ?string $contactPhone;
    private ?string $publicCode;

    public function __construct(
        ?int $id,
        int $userId,
        ?int $addressId,
        string $shippingAddress,
        float $total,
        ?OrderStatus $status = null, 
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $confirmedAt = null,
        ?string $contactPhone = null,
        ?string $publicCode = null  
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
        $this->status = $status ?? OrderStatus::PENDING_PAYMENT; 
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->confirmedAt = $confirmedAt;
        $this->contactPhone = $contactPhone;
        $this->publicCode = $publicCode;
    }

    //VALIDACIONES

    private function validateUserId(int $userId): void {
        if ($userId <= 0) {
            throw new InvalidArgumentException('El identificador de usuario es inválido');
        }
    }

    private function validateAddressId(?int $addressId): void {
        if ($addressId !== null && $addressId <= 0) {
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

    //Regla de cambios de estado que usa el panel del admin:
    // - Entregado y Cancelado son finales.
    // - Una vez confirmado (ya se recibió el pago) no se puede regresar a "Pendiente de pago":
    //   así la fecha de confirmación de los reportes siempre corresponde a un pago real.
    //   Si el pago resultó inválido, lo correcto es cancelar el pedido.
    // - Entre Confirmado, En preparación y En camino se puede ir y venir para corregir errores.
    public function ensureCanChangeTo(OrderStatus $nuevoEstado): void {
        if ($this->status === OrderStatus::CANCELLED) {
            throw new InvalidArgumentException('Este pedido ya está cancelado y no se puede modificar.');
        }
        if ($this->status === OrderStatus::DELIVERED) {
            throw new InvalidArgumentException('Este pedido ya está entregado y no se puede modificar.');
        }
        if ($nuevoEstado === OrderStatus::PENDING_PAYMENT && $this->status !== OrderStatus::PENDING_PAYMENT) {
            throw new InvalidArgumentException(
                'Un pedido confirmado no puede regresar a "Pendiente de pago". Si el pago no fue válido, cancélalo.'
            );
        }
    }

    //GETTERS

    public function getId(): ?int {
        return $this->id;
    }

    public function getUserId(): int {
        return $this->userId;
    }

    public function getAddressId(): ?int {
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
    public function getConfirmedAt(): ?DateTimeImmutable {
        return $this->confirmedAt;
    }
    public function getContactPhone(): ?string {
        return $this->contactPhone;
    }
    public function getPublicCode(): ?string {
        return $this->publicCode;
    }
}