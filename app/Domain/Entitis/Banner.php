<?php

namespace App\Domain\Entitis;

use App\Domain\Enum\BannerType;
use InvalidArgumentException;

class Banner
{
    private ?int $id;
    private string $title;
    private string $mediaUrl;
    private BannerType $type;
    private bool $isActive;

    public function __construct(
        ?int $id,
        string $title,
        string $mediaUrl,
        BannerType $type,
        bool $isActive = true
    ) {
        $this->validateTitle($title);
        $this->validateMediaUrl($mediaUrl);

        $this->id = $id;
        $this->title = $title;
        $this->mediaUrl = $mediaUrl;
        $this->type = $type;
        $this->isActive = $isActive;
    }

    // VALIDACIONES

    private function validateTitle(string $title): void
    {
        if (empty(trim($title))) {
            throw new InvalidArgumentException(
                'El título es obligatorio.'
            );
        }

        if (strlen($title) > 150) {
            throw new InvalidArgumentException(
                'El título no puede superar los 150 caracteres.'
            );
        }
    }

    private function validateMediaUrl(
        string $mediaUrl
    ): void {
        if (empty(trim($mediaUrl))) {
            throw new InvalidArgumentException(
                'La ruta del archivo es obligatoria.'
            );
        }
    }

    // REGLAS DE NEGOCIO

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

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    // CONSULTAS DE DOMINIO

    public function isImage(): bool
    {
        return $this->type === BannerType::IMAGE;
    }

    public function isVideo(): bool
    {
        return $this->type === BannerType::VIDEO;
    }

    // GETTERS

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMediaUrl(): string
    {
        return $this->mediaUrl;
    }

    public function getType(): BannerType
    {
        return $this->type;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}