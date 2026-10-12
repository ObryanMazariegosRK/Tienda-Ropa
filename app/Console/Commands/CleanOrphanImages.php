<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Archivos en storage/app/public/{products,banners} que ningún producto ni banner usa
class CleanOrphanImages extends Command
{
    private const CARPETAS = ['products', 'banners'];

    protected $signature = 'images:clean-orphans
                            {--delete : Borra los archivos encontrados (sin esta opción solo los lista)}
                            {--force : No pide confirmación antes de borrar}';
    protected $description = 'Lista (o borra con --delete) las imágenes que no pertenecen a ningún producto ni banner.';

    public function handle(): int
    {
        $disco = Storage::disk('public');

        $usadas = DB::table('product_images')->pluck('image_url')
            ->merge(DB::table('banners')->pluck('media_url'))
            ->flip();

        // Se ignoran los archivos de la última hora: pueden ser de una subida que aún no termina
        $limite = now()->subHour()->getTimestamp();

        $huerfanas = collect(self::CARPETAS)
            ->flatMap(fn($carpeta) => $disco->files($carpeta))
            ->reject(fn($path) => $usadas->has($path) || $disco->lastModified($path) > $limite)
            ->values();

        if ($huerfanas->isEmpty()) {
            $this->info('No hay imágenes huérfanas.');
            return self::SUCCESS;
        }

        $peso = $huerfanas->sum(fn($path) => $disco->size($path));
        $this->info(sprintf('%d archivos sin usar (%.1f MB):', $huerfanas->count(), $peso / 1048576));
        $huerfanas->take(20)->each(fn($path) => $this->line("  $path"));
        if ($huerfanas->count() > 20) {
            $this->line('  ... y ' . ($huerfanas->count() - 20) . ' más');
        }

        if (!$this->option('delete')) {
            $this->newLine();
            $this->line('No se borró nada. Para borrarlos: php artisan images:clean-orphans --delete');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('¿Borrar estos archivos? No se puede deshacer.')) {
            $this->line('Cancelado.');
            return self::SUCCESS;
        }

        $disco->delete($huerfanas->all());
        $this->info(sprintf('Se borraron %d archivos (%.1f MB).', $huerfanas->count(), $peso / 1048576));

        return self::SUCCESS;
    }
}
