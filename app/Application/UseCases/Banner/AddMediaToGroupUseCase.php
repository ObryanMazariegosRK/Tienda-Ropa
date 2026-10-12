<?php
// AddMediaToGroupUseCase.php
namespace App\Application\UseCases\Banner;

use App\Application\Abstractions\Banner\IAddMediaToGroupUseCase;
use App\Application\DTOs\Banner\BannerGroupDTO;
use App\Application\Abstractions\Product\IImageStorageService;
use App\Domain\Abstractions\IBannerGroupRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class AddMediaToGroupUseCase implements IAddMediaToGroupUseCase
{
    private const MAX_MEDIA_PER_GROUP = 5;

    public function __construct(
        private IBannerGroupRepository $repo,
        private IImageStorageService $imageStorage
    ) {}

    public function execute(int $groupId, array $files): BannerGroupDTO
    {
        $group = $this->repo->findById($groupId);
        if (!$group) throw new Exception('Grupo no encontrado.');

        $actuales = $this->repo->countMedia($groupId);
        if ($actuales + count($files) > self::MAX_MEDIA_PER_GROUP) {
            throw new Exception('Este grupo admite máximo ' . self::MAX_MEDIA_PER_GROUP . ' archivos en total.');
        }

        $grupoEsVideo = $group->getType()->value === 'video';
        foreach ($files as $file) {
            if (str_starts_with($file->getMimeType(), 'video/') !== $grupoEsVideo) {
                throw new Exception('Este grupo es de tipo "' . $group->getType()->value . '" — no puedes agregar el tipo opuesto.');
            }
        }

        // Las imágenes se guardan optimizadas (los videos tal cual); si la base falla, se borran
        $paths = $this->imageStorage->storeMultiple($files, 'banners', 1920);

        try {
            return DB::transaction(function () use ($groupId, $paths, $actuales) {
                foreach ($paths as $index => $path) {
                    $this->repo->addMedia($groupId, $path, $actuales + $index);
                }

                return BannerGroupDTO::fromEntity($this->repo->findById($groupId));
            });
        } catch (\Throwable $e) {
            $this->imageStorage->deleteMany($paths);
            throw $e;
        }
    }
}