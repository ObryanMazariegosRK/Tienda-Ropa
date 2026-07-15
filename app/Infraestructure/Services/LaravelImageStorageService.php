<?php

namespace App\Infraestructure\Services;

use App\Application\Abstractions\Product\IImageStorageService;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class LaravelImageStorageService implements IImageStorageService
{
    /**
     * Recibe un arreglo de archivos, los guarda en la ruta especificada
     * y retorna un arreglo con los paths generados.
     */
    public function storeMultiple(array $images, string $directory): array
    {
        $paths = [];
        //
        //foreach ($images as $image) {
            //Por seguridad, verificamos que el elemento sea realmente un archivo subido por Laravel
            //if ($image instanceof UploadedFile) {
                
                //El método store() guarda físicamente el archivo en storage/app/public/$directory
                //Genera un nombre único automáticamente (hash) para evitar que se sobreescriban
                //Retorna la ruta relativa, ej: "products/hj87123gasdf.jpg"
                //$paths[] = $image->store($directory, 'public');
                
            //} else {
                //throw new InvalidArgumentException("Uno de los elementos proporcionados no es un archivo válido.");
            //}
        //}

        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                try {
                    // Forzamos el guardado
                    $path = $image->store($directory, 'public');
                    
                    if ($path === false) {
                        // Si retorna false, registramos en el log
                        \Illuminate\Support\Facades\Log::error("Error al guardar archivo en disco: El método store devolvió false.");
                    }
                    
                    $paths[] = $path;
                    
                } catch (\Exception $e) {
                    // Capturamos cualquier excepción física (ej: Driver no encontrado, disco lleno, etc)
                    \Illuminate\Support\Facades\Log::error("Excepción al guardar archivo: " . $e->getMessage());
                    $paths[] = false;
                }
            } else {
                throw new InvalidArgumentException("Uno de los elementos no es válido.");
            }
        }


        return $paths;
    }

    public function delete(string $path): bool
    {
        try {
            // Verificamos si el archivo realmente existe antes de intentar borrarlo
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }
            return false;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error al eliminar archivo en disco: " . $e->getMessage());
            return false;
        }
    }



}