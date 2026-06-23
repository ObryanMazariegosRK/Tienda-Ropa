<?php

namespace App\Domain\Entities;

use InvalidArgumentException;

class Category
{
    private ?int $id;
    private string $name;
    private string $description;
    private ?int $parentCategoryId;
    private string $slug;
    private bool $isActive;

    public function __construct(
        ?int $id,
        string $name,
        string $description,
        ?int $parentCategoryId,
        string $slug,
        bool $isActive = true
    ) {
        $this->validateName($name);
        $this->validateDescription($description);
        $this->validateSlug($slug);
        //Validacion de que no sea su propio padre xd
        $this->validateParent($id, $parentCategoryId); 

        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->parentCategoryId = $parentCategoryId;
        $this->slug = $slug;
        $this->isActive = $isActive;
    }

    //VALIDACIONES

    private function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new InvalidArgumentException('El nombre de la categoría es obligatorio.');
        }

        if (strlen($name) > 100) {
            throw new InvalidArgumentException('El nombre no puede superar los 100 caracteres.');
        }
    }

    private function validateDescription(string $description): void 
    {
        if (empty(trim($description))) {
            throw new InvalidArgumentException('La descripción es obligatoria.');
        }
        
        if (strlen($description) > 500) {
            throw new InvalidArgumentException('La descripción no puede ser más de 500 caracteres.');
        }
    }

    private function validateSlug(string $slug): void
    {
        if (empty(trim($slug))) {
            throw new InvalidArgumentException('El slug (URL) no puede estar vacío.');
        }

        if (str_contains($slug, ' ')) {
            throw new InvalidArgumentException('El slug no puede contener espacios vacíos.');
        }
    }

    private function validateParent(?int $id, ?int $parentCategoryId): void
    {
        if ($id !== null && $parentCategoryId !== null && $id === $parentCategoryId) {
            throw new InvalidArgumentException('Una categoría no puede ser subcategoría de sí misma.');
        }
    }

    //Compotamientos 

    public function update(
        string $name, 
        string $description, 
        string $slug, 
        ?int $parentCategoryId, 
        bool $isActive
    ): void {
        $this->validateName($name);
        $this->validateDescription($description);
        $this->validateSlug($slug);
        $this->validateParent($this->id, $parentCategoryId);

        $this->name = $name;
        $this->description = $description;
        $this->slug = $slug;
        $this->parentCategoryId = $parentCategoryId;
        $this->isActive = $isActive;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    //CONSULTA

    public function isSubCategory(): bool
    {
        return $this->parentCategoryId !== null;
    }

    //GETTERS
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getParentCategoryId(): ?int
    {
        return $this->parentCategoryId;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}