<?php

namespace App\Domain\Entities;

use App\Domain\Enum\AuctionStatus;
use DateTimeImmutable;
use InvalidArgumentException;

class Auction
{
    private ?int $id;
    private int $productId;
    private float $startingPrice;
    private float $currentPrice;
    private float $minIncrement; 
    private DateTimeImmutable $startDate;
    private DateTimeImmutable $endDate;
    private AuctionStatus $status;
    private ?int $currentWinnerUserId;
    private ?int $winnerUserId;
    private ?int $orderId;
    private ?DateTimeImmutable $wonAt;
    private const MAX_BID_AMOUNT = 10000.00;// Límite máximo de oferta

    public function __construct(
        ?int $id,
        int $productId,
        float $startingPrice,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        AuctionStatus $status = AuctionStatus::ACTIVE,
        ?int $currentWinnerUserId = null,
        ?int $winnerUserId = null,
        ?float $currentPrice = null,
        float $minIncrement = 10.00,
        ?int $orderId = null,
        ?DateTimeImmutable $wonAt = null
        

    ) {
        $this->validateProductId($productId);
        $this->validatePrice($startingPrice);
        $this->validateDates($startDate, $endDate);
        $this->validateMinIncrement($minIncrement);

        $this->id = $id;
        $this->productId = $productId;
        $this->startingPrice = $startingPrice;
        $this->currentPrice = $currentPrice ?? $startingPrice;
        $this->minIncrement = $minIncrement;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->status = $status;
        $this->currentWinnerUserId = $currentWinnerUserId;
        $this->winnerUserId = $winnerUserId;
         $this->orderId = $orderId;
         $this->wonAt = $wonAt;
    }

    private function validateProductId(int $productId): void
    {
        if ($productId <= 0) {
            throw new InvalidArgumentException('El producto es obligatorio.');
        }
    }

    private function validatePrice(float $price): void
    {
        if ($price <= 0) {
            throw new InvalidArgumentException('El precio debe ser mayor a cero.');
        }
    }

    private function validateMinIncrement(float $minIncrement): void
    {
        if ($minIncrement <= 0) {
            throw new InvalidArgumentException('El incremento mínimo debe ser mayor a cero.');
        }
    }

    private function validateDates(DateTimeImmutable $startDate, DateTimeImmutable $endDate): void
    {
        if ($endDate <= $startDate) {
            throw new InvalidArgumentException('La fecha de finalización debe ser posterior a la de inicio.');
        }
    }

    public function registerBid(int $userId, float $amount): void
    {
        if (!$this->isActive()) {
            throw new InvalidArgumentException('La subasta no está activa.');
        }

        if ($userId <= 0) {
            throw new InvalidArgumentException('El usuario es inválido.');
        }

        if ($userId === $this->currentWinnerUserId) {
            throw new InvalidArgumentException('Ya eres el ganador actual de esta subasta.');
        }

        $minimoRequerido = $this->currentPrice + $this->minIncrement;
        if ($amount < $minimoRequerido) {
            throw new InvalidArgumentException(
                "La oferta debe ser de al menos Q" . number_format($minimoRequerido, 2) .
                " (precio actual + incremento mínimo de Q" . number_format($this->minIncrement, 2) . ")."
            );
        }

        if ($amount > self::MAX_BID_AMOUNT) {
            throw new InvalidArgumentException(
                "El monto ingresado es demasiado alto. La oferta máxima permitida es Q" . number_format(self::MAX_BID_AMOUNT, 2) . "."
            );
        }

        $this->currentPrice = $amount;
        $this->currentWinnerUserId = $userId;
    }

    public function finish(): void
    {
        if (!$this->isActive()) {
            throw new InvalidArgumentException('Solo se puede finalizar una subasta que está activa.');
        }
        $this->status = AuctionStatus::FINISHED;
        $this->winnerUserId = $this->currentWinnerUserId;

        if ($this->winnerUserId !== null) {
            $this->wonAt = new DateTimeImmutable(); 
        }
    }

    public function cancel(): void
    {
        if (!$this->isActive()) {
            throw new InvalidArgumentException('Solo se puede cancelar una subasta que está activa.');
        }
        $this->status = AuctionStatus::CANCELLED;
    }

    public function markAsOrdered(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function hasBeenOrdered(): bool
    {
        return $this->orderId !== null;
    }

    public function extendEndDate(DateTimeImmutable $newEndDate): void
    {
        if (!$this->isActive()) {
            throw new InvalidArgumentException('Solo se puede modificar la duración de una subasta activa.');
        }
        if ($newEndDate <= new DateTimeImmutable()) {
            throw new InvalidArgumentException('La nueva fecha de finalización debe ser en el futuro.');
        }
        $this->endDate = $newEndDate;
    }

    public function reassignWinner(int $userId, float $amount): void
    {
        if (!$this->isFinished()) {
            throw new InvalidArgumentException('Solo se puede reasignar una subasta finalizada.');
        }
        $this->winnerUserId = $userId;
        $this->currentWinnerUserId = $userId;
        $this->currentPrice = $amount;
    }

    public function clearWinner(): void
    {
        $this->winnerUserId = null;
        $this->currentWinnerUserId = null;
    }

    public function hasWinConfirmationExpired(int $daysAllowed): bool
    {
        if (!$this->wonAt) return false;
        $limite = $this->wonAt->modify("+{$daysAllowed} days");
        return new DateTimeImmutable() >= $limite;
    }

    public function getWonAt(): ?DateTimeImmutable { return $this->wonAt; }    


    public function getOrderId(): ?int { return $this->orderId; }
    

    public function isActive(): bool { return $this->status === AuctionStatus::ACTIVE; }
    public function isFinished(): bool { return $this->status === AuctionStatus::FINISHED; }
    public function isCancelled(): bool { return $this->status === AuctionStatus::CANCELLED; }
    public function hasWinner(): bool { return $this->winnerUserId !== null; }
    public function hasExpired(): bool { return new DateTimeImmutable() >= $this->endDate; } // 👈 nuevo, útil para el cierre automático

    public function getId(): ?int { return $this->id; }
    public function getProductId(): int { return $this->productId; }
    public function getStartingPrice(): float { return $this->startingPrice; }
    public function getCurrentPrice(): float { return $this->currentPrice; }
    public function getMinIncrement(): float { return $this->minIncrement; }
    public function getStartDate(): DateTimeImmutable { return $this->startDate; }
    public function getEndDate(): DateTimeImmutable { return $this->endDate; }
    public function getStatus(): AuctionStatus { return $this->status; }
    public function getCurrentWinnerUserId(): ?int { return $this->currentWinnerUserId; }
    public function getWinnerUserId(): ?int { return $this->winnerUserId; }
}