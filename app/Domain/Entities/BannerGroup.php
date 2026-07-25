<?php

namespace App\Domain\Entities;

use App\Domain\Enum\BannerType;
use InvalidArgumentException;

class BannerGroup
{
    /** @var Banner[] */
    private array $media = [];

    public function __construct(
        private ?int $id,
        private string $name,
        private BannerType $type,
        private bool $isActive = false
    ) {
        $this->validateName($name);
    }

    private function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new InvalidArgumentException('El nombre del grupo es obligatorio.');
        }
        if (strlen($name) > 150) {
            throw new InvalidArgumentException('El nombre no puede superar los 150 caracteres.');
        }
    }

    public function rename(string $name): void
    {
        $this->validateName($name);
        $this->name = $name;
    }

    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }

    public function setMedia(array $media): void { $this->media = $media; }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getType(): BannerType { return $this->type; }
    public function isActive(): bool { return $this->isActive; }
    public function getMedia(): array { return $this->media; }
}