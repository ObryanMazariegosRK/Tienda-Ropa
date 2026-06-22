<?php

namespace App\Domain\Entitis;

use App\Domain\Enum\ProductStatus;
use InvalidArgumentException;

class Product
{
    private ?int $id;
    private int $categoryId;
    private string $name;
    private string $description;
    private float $price;
    private ?float $offerPrice;
    private ProductStatus $status;
    private bool $isFeatured;

    public function __construct(
        ?int $id,
        int $categoryId,
        string $name,
        string $description,
        float $price,
        ?float $offerPrice,
        ProductStatus $status = ProductStatus::AVAILABLE,
        bool $isFeatured = false
    ) {
        $this->validateCategoryId($categoryId);
        $this->validateName($name);
        $this->validateDescription($description);
        $this->validatePrice($price);
        $this->validateOfferPrice($price, $offerPrice);

        $this->id = $id;
        $this->categoryId = $categoryId;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->offerPrice = $offerPrice;
        $this->status = $status;
        $this->isFeatured = $isFeatured;
    }

    // VALIDACIONES

    private function validateCategoryId(int $categoryId): void
    {
        if ($categoryId <= 0) {
            throw new InvalidArgumentException(
                'La categoría es obligatoria.'
            );
        }
    }

    private function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new InvalidArgumentException(
                'El nombre del producto es obligatorio.'
            );
        }

        if (strlen($name) > 150) {
            throw new InvalidArgumentException(
                'El nombre no puede superar los 150 caracteres.'
            );
        }
    }

    private function validateDescription(
        string $description
    ): void {
        if (empty(trim($description))) {
            throw new InvalidArgumentException(
                'La descripción es obligatoria.'
            );
        }

        if (strlen($description) > 2000) {
            throw new InvalidArgumentException(
                'La descripción no puede superar los 2000 caracteres.'
            );
        }
    }

    private function validatePrice(float $price): void
    {
        if ($price <= 0) {
            throw new InvalidArgumentException(
                'El precio debe ser mayor a cero.'
            );
        }
    }

    private function validateOfferPrice(
        float $price,
        ?float $offerPrice
    ): void {
        if ($offerPrice === null) {
            return;
        }

        if ($offerPrice <= 0) {
            throw new InvalidArgumentException(
                'El precio de oferta debe ser mayor a cero.'
            );
        }

        if ($offerPrice >= $price) {
            throw new InvalidArgumentException(
                'El precio de oferta debe ser menor al precio normal.'
            );
        }
    }

    // REGLAS DE NEGOCIO

    public function update(
        int $categoryId,
        string $name,
        string $description,
        float $price,
        ?float $offerPrice,
        bool $isFeatured
    ): void {
        $this->validateCategoryId($categoryId);
        $this->validateName($name);
        $this->validateDescription($description);
        $this->validatePrice($price);
        $this->validateOfferPrice($price, $offerPrice);

        $this->categoryId = $categoryId;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->offerPrice = $offerPrice;
        $this->isFeatured = $isFeatured;
    }

    public function activateOffer(
        float $offerPrice
    ): void {
        $this->validateOfferPrice(
            $this->price,
            $offerPrice
        );

        $this->offerPrice = $offerPrice;
    }

    public function removeOffer(): void
    {
        $this->offerPrice = null;
    }

    public function moveToAuction(): void
    {
        if ($this->status === ProductStatus::SOLD) {
            throw new InvalidArgumentException(
                'Un producto vendido no puede entrar en subasta.'
            );
        }

        $this->status = ProductStatus::AUCTION;
    }

    public function markAsSold(): void
    {
        $this->status = ProductStatus::SOLD;
    }

    public function disable(): void
    {
        if ($this->status === ProductStatus::SOLD) {
            throw new InvalidArgumentException(
                'No se puede deshabilitar un producto vendido.'
            );
        }

        $this->status = ProductStatus::DISABLED;
    }

    public function enable(): void
    {
        if ($this->status === ProductStatus::SOLD) {
            throw new InvalidArgumentException(
                'Un producto vendido no puede volver a estar disponible.'
            );
        }

        $this->status = ProductStatus::AVAILABLE;
    }

    public function markAsFeatured(): void
    {
        $this->isFeatured = true;
    }

    public function removeFeatured(): void
    {
        $this->isFeatured = false;
    }

    // CONSULTAS DE DOMINIO

    public function getCurrentPrice(): float
    {
        return $this->offerPrice ?? $this->price;
    }

    public function isAvailable(): bool
    {
        return $this->status === ProductStatus::AVAILABLE;
    }

    public function isInAuction(): bool
    {
        return $this->status === ProductStatus::AUCTION;
    }

    public function isSold(): bool
    {
        return $this->status === ProductStatus::SOLD;
    }

    public function isDisabled(): bool
    {
        return $this->status === ProductStatus::DISABLED;
    }

    public function hasOffer(): bool
    {
        return $this->offerPrice !== null;
    }

    // GETTERS

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getOfferPrice(): ?float
    {
        return $this->offerPrice;
    }

    public function getStatus(): ProductStatus
    {
        return $this->status;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }
}