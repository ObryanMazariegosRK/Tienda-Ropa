<?php

namespace App\Domain\Entities;

use InvalidArgumentException;

class ProductImage
{
    private ?int $id;
    private int $productId;
    private string $imageUrl;

    public function __construct(
        ?int $id,
        int $productId,
        string $imageUrl
    ) {
        if ($id !== null) {
            $this->validateId($id);
        }

        $this->validateProductId($productId);
        $this->validateUrl($imageUrl);

        $this->id = $id;
        $this->productId = $productId;
        $this->imageUrl = $imageUrl;
    }

    //VALIDACIONES

    private function validateId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('El ID de la imagen no es válido.');
        }
    }

    private function validateProductId(int $productId): void
    {
        if ($productId <= 0) {
            throw new InvalidArgumentException('El ID del producto asociado no es válido.');
        }
    }

    private function validateUrl(string $imageUrl): void 
    {
        if (empty(trim($imageUrl))) {
            throw new InvalidArgumentException('La URL de la imagen es obligatoria.');
        }

        if (strlen($imageUrl) > 2048) { 
            throw new InvalidArgumentException('La URL de la imagen es demasiado larga (máximo 2048 caracteres).');
        }
    }

    //comportamiento

    public function updateUrl(string $imageUrl): void 
    {
        $this->validateUrl($imageUrl);
        $this->imageUrl = $imageUrl;
    }

    //GETTERS

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }
}