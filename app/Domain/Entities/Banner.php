<?php

namespace App\Domain\Entities;

use InvalidArgumentException;

class Banner
{
    public function __construct(
        private ?int $id,
        private int $bannerGroupId,
        private string $mediaUrl,
        private int $displayOrder = 0
    ) {
        if ($bannerGroupId <= 0) {
            throw new InvalidArgumentException('El grupo de banner es obligatorio.');
        }
        if (empty(trim($mediaUrl))) {
            throw new InvalidArgumentException('La ruta del archivo es obligatoria.');
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getBannerGroupId(): int { return $this->bannerGroupId; }
    public function getMediaUrl(): string { return $this->mediaUrl; }
    public function getDisplayOrder(): int { return $this->displayOrder; }
}