<?php
namespace App\Console\Commands;

use App\Application\Abstractions\Product\IImageStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Optimiza las fotos que se subieron antes de que la tienda las redujera al subirlas.
// Guarda una copia de cada original en storage/app/private/respaldo-imagenes/<fecha>/
class OptimizeStoredImages extends Command
{
    // Lado mayor máximo por carpeta (igual que al subir)
    private const TAMANOS = ['products' => 1600, 'banners' => 1920];

    protected $signature = 'images:optimize {--dry-run : Solo muestra qué fotos se optimizarían}';
    protected $description = 'Reduce y comprime las fotos de productos y banners que ya están guardadas.';

    public function handle(IImageStorageService $imageStorage): int
    {
        $publico = Storage::disk('public');
        $respaldo = 'respaldo-imagenes/' . now()->format('Y-m-d_His');

        // Cada foto con la tabla y columna que la usa
        $usos = [];
        foreach (DB::table('product_images')->pluck('image_url') as $path) {
            $usos[$path] = ['product_images', 'image_url'];
        }
        foreach (DB::table('banners')->pluck('media_url') as $path) {
            $usos[$path] = ['banners', 'media_url'];
        }

        $candidatas = [];
        foreach ($usos as $path => $uso) {
            $carpeta = explode('/', $path)[0];
            if (!isset(self::TAMANOS[$carpeta]) || !$publico->exists($path)) {
                continue;
            }
            $info = @getimagesize($publico->path($path));
            if (!$info) {
                continue; // videos u otros archivos
            }
            // Ya optimizada: WebP y dentro del tamaño (recomprimirla solo le quitaría calidad)
            if (str_ends_with($path, '.webp') && max($info[0], $info[1]) <= self::TAMANOS[$carpeta]) {
                continue;
            }
            $candidatas[$path] = $uso;
        }

        $pesoTotal = array_sum(array_map(fn($p) => $publico->size($p), array_keys($candidatas)));
        $this->info(sprintf('%d fotos por optimizar (%.1f MB).', count($candidatas), $pesoTotal / 1048576));

        if ($this->option('dry-run') || empty($candidatas)) {
            return self::SUCCESS;
        }

        $antes = 0;
        $despues = 0;
        $barra = $this->output->createProgressBar(count($candidatas));

        foreach ($candidatas as $path => [$tabla, $columna]) {
            $pesoOriginal = $publico->size($path);
            $nuevo = $imageStorage->optimizeExisting($path, self::TAMANOS[explode('/', $path)[0]]);

            // Solo vale la pena si ahorra al menos un 10 %
            if (!$nuevo || $publico->size($nuevo) > $pesoOriginal * 0.9) {
                if ($nuevo) {
                    $publico->delete($nuevo);
                }
                $barra->advance();
                continue;
            }

            Storage::disk('local')->put("$respaldo/$path", $publico->get($path));
            DB::table($tabla)->where($columna, $path)->update([$columna => $nuevo]);
            $publico->delete($path);

            $antes += $pesoOriginal;
            $despues += $publico->size($nuevo);
            $barra->advance();
        }

        $barra->finish();
        $this->newLine(2);
        $this->info(sprintf('Listo: %.1f MB → %.1f MB.', $antes / 1048576, $despues / 1048576));
        if ($antes > 0) {
            $this->line('Originales respaldados en storage/app/private/' . $respaldo . ' (bórralos cuando confirmes que todo se ve bien).');
        }

        return self::SUCCESS;
    }
}
