<?php

namespace App\Domain\Entities;

use App\Domain\Enum\ProductSaleType;
use App\Domain\Enum\ProductStatus;
use InvalidArgumentException;

class Product
{
    private ?int $id;
    private int $categoryId;
    private string $name;
    private string $description;
    private string $slug;
    private float $price;
    private ?float $offerPrice;
    private ProductSaleType $saleType;
    private ProductStatus $status;

    public function __construct(
        ?int $id,
        int $categoryId,
        string $name,
        string $description,
        string $slug,
        float $price,
        ?float $offerPrice,
        ProductSaleType $saleType,
        ProductStatus $status = ProductStatus::AVAILABLE
    ) {
        $this->validateCategoryId($categoryId);
        $this->validateName($name);
        $this->validateDescription($description);
        $this->validateSlug($slug);
        $this->validatePrice($price);
        $this->validateOfferPrice($price, $offerPrice);

        $this->id = $id;
        $this->categoryId = $categoryId;
        $this->name = $name;
        $this->description = $description;
        $this->slug = $slug;
        $this->price = $price;
        $this->offerPrice = $offerPrice;
        $this->saleType = $saleType;
        $this->status = $status;
    }

    //VALIDACIONES

    private function validateCategoryId(int $categoryId): void
    {
        if ($categoryId <= 0) {
            throw new InvalidArgumentException('La categoría es obligatoria.');
        }
    }

    private function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new InvalidArgumentException('El nombre del producto es obligatorio.');
        }
        if (strlen($name) > 200) {
            throw new InvalidArgumentException('El nombre no puede superar los 200 caracteres.');
        }
    }

    private function validateDescription(string $description): void
    {
        if (empty(trim($description))) {
            throw new InvalidArgumentException('La descripción es obligatoria.');
        }
        if (strlen($description) > 2000) {
            throw new InvalidArgumentException('La descripción no puede superar los 2000 caracteres.');
        }
    }

    private function validateSlug(string $slug): void
    {
        if (empty(trim($slug))) {
            throw new InvalidArgumentException('El slug es obligatorio.');
        }
        if (strlen($slug) > 250) {
            throw new InvalidArgumentException('El slug no puede superar los 250 caracteres.');
        }
    }

    private function validatePrice(float $price): void
    {
        if ($price <= 0) {
            throw new InvalidArgumentException('El precio debe ser mayor a cero.');
        }
    }

    private function validateOfferPrice(float $price, ?float $offerPrice): void
    {
        if ($offerPrice === null) {
            return;
        }
        if ($offerPrice <= 0) {
            throw new InvalidArgumentException('El precio de oferta debe ser mayor a cero.');
        }
        if ($offerPrice >= $price) {
            throw new InvalidArgumentException('El precio de oferta debe ser menor al precio normal.');
        }
    }

    //REGLAS DE NEEGOCIO

    public function update(
        int $categoryId,
        string $name,
        string $description,
        string $slug,
        float $price,
        ?float $offerPrice,
        ProductSaleType $saleType
    ): void {
        // Protección agregada
        if ($this->isSold()) {
            throw new InvalidArgumentException('No se pueden modificar los detalles de un producto que ya ha sido vendido.');
        }

        $this->validateCategoryId($categoryId);
        $this->validateName($name);
        $this->validateDescription($description);
        $this->validateSlug($slug);
        $this->validatePrice($price);
        $this->validateOfferPrice($price, $offerPrice);

        $this->categoryId = $categoryId;
        $this->name = $name;
        $this->description = $description;
        $this->slug = $slug;
        $this->price = $price;
        $this->offerPrice = $offerPrice;
        $this->saleType = $saleType;
    }

    public function activateOffer(float $offerPrice): void
    {
 
        if ($this->isSold()) {
            throw new InvalidArgumentException('No se puede aplicar una oferta a un producto que ya está vendido.');
        }

        $this->validateOfferPrice($this->price, $offerPrice);
        $this->offerPrice = $offerPrice;
    }

    public function removeOffer(): void
    {
        
        if ($this->isSold()) {
            throw new InvalidArgumentException('No se puede modificar la oferta de un producto vendido.');
        }
        $this->offerPrice = null;
    }

    public function changeSaleType(ProductSaleType $saleType): void
    {
        if ($this->isSold()) {
            throw new InvalidArgumentException('No se puede modificar el tipo de venta de un producto vendido.');
        }
        $this->saleType = $saleType;
    }

    public function markAsSold(): void
    {
        $this->status = ProductStatus::SOLD;
        $this->offerPrice = null; 
    }

    public function disable(): void
    {
        if ($this->isSold()) {
            throw new InvalidArgumentException('No se puede deshabilitar un producto vendido.');
        }
        $this->status = ProductStatus::DISABLED;
    }

    public function enable(): void
    {
        if ($this->isSold()) {
            throw new InvalidArgumentException('Un producto vendido no puede volver a estar disponible.');
        }
        $this->status = ProductStatus::AVAILABLE;
    }

    //CONSULAS DE DOMINIO

    public function getCurrentPrice(): float
    {
        return $this->offerPrice ?? $this->price;
    }

    public function hasOffer(): bool
    {
        return $this->offerPrice !== null;
    }

    public function isAvailable(): bool
    {
        return $this->status === ProductStatus::AVAILABLE;
    }

    public function isSold(): bool
    {
        return $this->status === ProductStatus::SOLD;
    }

    public function isDisabled(): bool
    {
        return $this->status === ProductStatus::DISABLED;
    }

    public function isAuction(): bool
    {
        return $this->saleType === ProductSaleType::AUCTION;
    }

    //GETTERS

    public function getId(): ?int { return $this->id; }
    public function getCategoryId(): int { return $this->categoryId; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getSlug(): string { return $this->slug; }
    public function getPrice(): float { return $this->price; }
    public function getOfferPrice(): ?float { return $this->offerPrice; }
    public function getSaleType(): ProductSaleType { return $this->saleType; }
    public function getStatus(): ProductStatus { return $this->status; }
}