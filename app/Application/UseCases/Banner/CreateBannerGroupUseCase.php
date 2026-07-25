<?php
// CreateBannerGroupUseCase.php
namespace App\Application\UseCases\Banner;

use App\Application\Abstractions\Banner\ICreateBannerGroupUseCase;
use App\Application\DTOs\Banner\BannerGroupDTO;
use App\Domain\Abstractions\IBannerGroupRepository;
use App\Domain\Entities\BannerGroup;
use App\Domain\Enum\BannerType;
use Illuminate\Support\Facades\DB;
use Exception;

class CreateBannerGroupUseCase implements ICreateBannerGroupUseCase
{
    private const MAX_GROUPS = 5;
    private const MAX_MEDIA_PER_GROUP = 5;

    public function __construct(private IBannerGroupRepository $repo) {}

    public function execute(string $name, array $files): BannerGroupDTO
    {
        if ($this->repo->countGroups() >= self::MAX_GROUPS) {
            throw new Exception('Ya existe el máximo de ' . self::MAX_GROUPS . ' grupos de banner permitidos.');
        }

        if (empty($files)) {
            throw new Exception('Debes subir al menos un archivo.');
        }

        if (count($files) > self::MAX_MEDIA_PER_GROUP) {
            throw new Exception('Un grupo admite máximo ' . self::MAX_MEDIA_PER_GROUP . ' archivos.');
        }

        $firstIsVideo = str_starts_with($files[0]->getMimeType(), 'video/');
        foreach ($files as $file) {
            if (str_starts_with($file->getMimeType(), 'video/') !== $firstIsVideo) {
                throw new Exception('No puedes mezclar imágenes y videos en el mismo grupo.');
            }
        }

        $type = $firstIsVideo ? BannerType::VIDEO : BannerType::IMAGE;

        return DB::transaction(function () use ($name, $type, $files) {
            $group = new BannerGroup(id: null, name: $name, type: $type, isActive: false);
            $created = $this->repo->create($group);

            foreach ($files as $index => $file) {
                $path = $file->store('banners', 'public');
                $this->repo->addMedia($created->getId(), $path, $index);
            }

            return BannerGroupDTO::fromEntity($this->repo->findById($created->getId()));
        });
    }
}