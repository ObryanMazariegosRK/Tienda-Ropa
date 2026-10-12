<?php

namespace App\Application\Abstractions\Product;

interface IImageStorageService
{
    /**
     * Guarda los archivos en el directorio y retorna sus paths. Las imágenes se reducen a
     * $maxSide px por su lado mayor y se comprimen; los videos se guardan tal cual.
     * Si alguno falla, borra los que ya guardó y lanza RuntimeException (todo o nada).
     */
    public function storeMultiple(array $images, string $directory, int $maxSide = 1600): array;

    /**
     * Vuelve a guardar optimizada una imagen que ya está en el disco. Retorna el path
     * nuevo, o null si no hace falta (ya era pequeña) o no se pudo leer.
     * No borra el archivo original.
     */
    public function optimizeExisting(string $path, int $maxSide = 1600): ?string;

    public function delete(string $path): bool;

    public function deleteMany(array $paths): void;
}
