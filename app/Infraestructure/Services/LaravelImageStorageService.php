<?php

namespace App\Infraestructure\Services;

use App\Application\Abstractions\Product\IImageStorageService;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class LaravelImageStorageService implements IImageStorageService
{
    private const DISCO = 'public';
    private const CALIDAD_WEBP = 80;
    private const CALIDAD_JPEG = 82;

    public function storeMultiple(array $images, string $directory, int $maxSide = 1600): array
    {
        $paths = [];

        try {
            foreach ($images as $image) {
                if (!$image instanceof UploadedFile) {
                    throw new InvalidArgumentException('Uno de los elementos no es válido.');
                }

                $optimizada = str_starts_with((string) $image->getMimeType(), 'image/')
                    ? $this->optimizar($image->getRealPath(), $maxSide, $image->getSize())
                    : null;

                if ($optimizada) {
                    [$contenido, $extension] = $optimizada;
                    $path = $directory . '/' . Str::random(40) . '.' . $extension;
                    $guardado = Storage::disk(self::DISCO)->put($path, $contenido);
                } else {
                    // Videos, o una imagen que no se pudo procesar: se guarda el original
                    $path = $guardado = $image->store($directory, self::DISCO);
                }

                if (!$guardado) {
                    throw new RuntimeException('No se pudo guardar una de las imágenes en el servidor. Inténtalo de nuevo.');
                }
                $paths[] = $path;
            }
        } catch (\Throwable $e) {
            // Todo o nada: que no queden archivos sueltos en el disco
            $this->deleteMany($paths);
            if (!$e instanceof InvalidArgumentException && !$e instanceof RuntimeException) {
                Log::error('Error al guardar imágenes: ' . $e->getMessage());
                throw new RuntimeException('No se pudo guardar una de las imágenes en el servidor. Inténtalo de nuevo.');
            }
            throw $e;
        }

        return $paths;
    }

    public function optimizeExisting(string $path, int $maxSide = 1600): ?string
    {
        $disco = Storage::disk(self::DISCO);
        if (!$disco->exists($path)) {
            return null;
        }

        $optimizada = $this->optimizar($disco->path($path), $maxSide, $disco->size($path));
        if (!$optimizada) {
            return null;
        }

        [$contenido, $extension] = $optimizada;
        $nuevo = dirname($path) . '/' . Str::random(40) . '.' . $extension;
        return $disco->put($nuevo, $contenido) ? $nuevo : null;
    }

    public function delete(string $path): bool
    {
        try {
            if (Storage::disk(self::DISCO)->exists($path)) {
                return Storage::disk(self::DISCO)->delete($path);
            }
            return false;
        } catch (\Exception $e) {
            Log::error("Error al eliminar archivo en disco: " . $e->getMessage());
            return false;
        }
    }

    public function deleteMany(array $paths): void
    {
        foreach ($paths as $path) {
            if ($path) {
                $this->delete($path);
            }
        }
    }

    /**
     * Reduce la imagen a $maxSide px por su lado mayor y la comprime (WebP, o JPEG si el
     * servidor no tiene WebP). Retorna [contenido, extensión] o null si no conviene:
     * no se pudo leer, o el resultado no pesa menos que el original.
     */
    private function optimizar(string $rutaAbsoluta, int $maxSide, int $pesoOriginal): ?array
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        // Una foto de celular de 12 MP ocupa ~50 MB al descomprimirse en memoria
        $limiteAnterior = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $info = @getimagesize($rutaAbsoluta);
            if (!$info || $info[0] * $info[1] > 40_000_000) {
                return null; // no es imagen, o es demasiado grande para procesarla con seguridad
            }

            $imagen = @imagecreatefromstring(file_get_contents($rutaAbsoluta));
            if (!$imagen) {
                return null;
            }

            if ($info[2] === IMAGETYPE_JPEG) {
                $imagen = $this->corregirOrientacion($imagen, $rutaAbsoluta);
            }

            $ancho = imagesx($imagen);
            $alto = imagesy($imagen);
            $escala = min(1, $maxSide / max($ancho, $alto));
            $nuevoAncho = max(1, (int) round($ancho * $escala));
            $nuevoAlto = max(1, (int) round($alto * $escala));

            $usarWebp = function_exists('imagewebp');
            $lienzo = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
            if ($usarWebp) {
                // WebP conserva la transparencia de los PNG
                imagealphablending($lienzo, false);
                imagesavealpha($lienzo, true);
                imagefill($lienzo, 0, 0, imagecolorallocatealpha($lienzo, 0, 0, 0, 127));
            } else {
                // JPEG no tiene transparencia: fondo blanco
                imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
            }
            imagecopyresampled($lienzo, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
            imagedestroy($imagen);

            ob_start();
            $usarWebp ? imagewebp($lienzo, null, self::CALIDAD_WEBP) : imagejpeg($lienzo, null, self::CALIDAD_JPEG);
            $contenido = ob_get_clean();
            imagedestroy($lienzo);

            if (!$contenido || strlen($contenido) >= $pesoOriginal) {
                return null;
            }

            return [$contenido, $usarWebp ? 'webp' : 'jpg'];
        } catch (\Throwable $e) {
            Log::warning('No se pudo optimizar la imagen, se guarda la original: ' . $e->getMessage());
            return null;
        } finally {
            ini_set('memory_limit', $limiteAnterior);
        }
    }

    // Las fotos de celular guardan la rotación en los datos EXIF; al recomprimirlas se
    // pierden, así que la aplicamos a los píxeles para que no salgan de lado
    private function corregirOrientacion(GdImage $imagen, string $rutaAbsoluta): GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $imagen;
        }

        $orientacion = (int) (@exif_read_data($rutaAbsoluta)['Orientation'] ?? 1);

        $rotada = match ($orientacion) {
            3, 4 => imagerotate($imagen, 180, 0),
            5, 6 => imagerotate($imagen, -90, 0),
            7, 8 => imagerotate($imagen, 90, 0),
            default => $imagen,
        };
        if (in_array($orientacion, [2, 4, 5, 7], true)) {
            imageflip($rotada, IMG_FLIP_HORIZONTAL);
        }

        return $rotada;
    }
}
