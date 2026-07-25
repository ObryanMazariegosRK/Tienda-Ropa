<?php
// AddMediaToGroupUseCase.php
namespace App\Application\UseCases\Banner;

use App\Application\Abstractions\Banner\IAddMediaToGroupUseCase;
use App\Application\DTOs\Banner\BannerGroupDTO;
use App\Domain\Abstractions\IBannerGroupRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class AddMediaToGroupUseCase implements IAddMediaToGroupUseCase
{
    private const MAX_MEDIA_PER_GROUP = 5;

    public function __construct(private IBannerGroupRepository $repo) {}

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

        return DB::transaction(function () use ($groupId, $files, $actuales) {
            foreach ($files as $index => $file) {
                $path = $file->store('banners', 'public');
                $this->repo->addMedia($groupId, $path, $actuales + $index);
            }

            return BannerGroupDTO::fromEntity($this->repo->findById($groupId));
        });
    }
}