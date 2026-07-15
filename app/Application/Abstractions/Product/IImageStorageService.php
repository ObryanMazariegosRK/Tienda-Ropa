<?php

namespace App\Application\Abstractions\Product;

interface IImageStorageService
{
    /**
     * Recibe un arreglo de archivos, los guarda en la ruta especificada
     * y retorna un arreglo con los paths generados.
     */
    public function storeMultiple(array $images, string $directory): array;
    public function delete(string $path): bool;
}