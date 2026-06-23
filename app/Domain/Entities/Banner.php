<?php

namespace App\Domain\Entities;

use App\Domain\Enum\BannerType;
use InvalidArgumentException;

class Banner
{
    private ?int $id;
    private string $title;
    private string $mediaUrl;
    private BannerType $type;
    private int $displayOrder; 
    private bool $isActive;

    public function __construct(
        ?int $id,
        string $title,
        string $mediaUrl,
        BannerType $type,
        int $displayOrder = 0,
        bool $isActive = true
    ) {
        $this->validateTitle($title);
        $this->validateMediaUrl($mediaUrl);
        $this->validateDisplayOrder($displayOrder);

        $this->id = $id;
        $this->title = $title;
        $this->mediaUrl = $mediaUrl;
        $this->type = $type;
        $this->displayOrder = $displayOrder;
        $this->isActive = $isActive;
    }

    //VALIDACIONES

    private function validateTitle(string $title): void
    {
        if (empty(trim($title))) {
            throw new InvalidArgumentException('El título es obligatorio.');
        }

        if (strlen($title) > 150) {
            throw new InvalidArgumentException('El título no puede superar los 150 caracteres.');
        }
    }

    private function validateMediaUrl(string $mediaUrl): void 
    {
        if (empty(trim($mediaUrl))) {
            throw new InvalidArgumentException('La ruta del archivo es obligatoria.');
        }

        if (strlen($mediaUrl) > 2048) {
            throw new InvalidArgumentException('La ruta del archivo es demasiado larga (máximo 2048 caracteres).');
        }
    }

    private function validateDisplayOrder(int $displayOrder): void
    {
        if ($displayOrder < 0) {
            throw new InvalidArgumentException('El orden de visualización no puede ser negativo.');
        }
    }

    //REGLAS DE NEGOCIO

    public function update(
        string $title,
        string $mediaUrl,
        BannerType $type
    ): void {
        $this->validateTitle($title);
        $this->validateMediaUrl($mediaUrl);

        $this->title = $title;
        $this->mediaUrl = $mediaUrl;
        $this->type = $type;
    }

    //Metodo para reordenar el carrusel por asi decirlo
    public function changeOrder(int $newOrder): void
    {
        $this->validateDisplayOrder($newOrder);
        $this->displayOrder = $newOrder;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    //Consutlas de dominio1

    public function isImage(): bool
    {
        return $this->type === BannerType::IMAGE;
    }

    public function isVideo(): bool
    {
        return $this->type === BannerType::VIDEO;
    }

    //Getters

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getMediaUrl(): string { return $this->mediaUrl; }
    public function getType(): BannerType { return $this->type; }
    public function getDisplayOrder(): int { return $this->displayOrder; }
    public function isActive(): bool { return $this->isActive; }
}