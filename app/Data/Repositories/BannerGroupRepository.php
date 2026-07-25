<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\IBannerGroupRepository;
use App\Domain\Entities\Banner;
use App\Domain\Entities\BannerGroup;
use App\Domain\Enum\BannerType;
use App\Models\BannerGroupModel;
use App\Models\BannerModel;

class BannerGroupRepository implements IBannerGroupRepository
{
    public function create(BannerGroup $group): BannerGroup
    {
        $model = BannerGroupModel::create([
            'name' => $group->getName(),
            'type' => $group->getType()->value,
            'is_active' => $group->isActive(),
        ]);
        return $this->mapToDomain($model);
    }

    public function findById(int $id): ?BannerGroup
    {
        $model = BannerGroupModel::with('media')->find($id);
        return $model ? $this->mapToDomain($model) : null;
    }

    public function findAll(): array
    {
        $models = BannerGroupModel::with('media')->orderBy('id')->get();
        return $models->map(fn($m) => $this->mapToDomain($m))->toArray();
    }

    public function findActive(): ?BannerGroup
    {
        $model = BannerGroupModel::with('media')->where('is_active', true)->first();
        return $model ? $this->mapToDomain($model) : null;
    }

    public function delete(int $id): bool
    {
        return (bool) BannerGroupModel::destroy($id);
    }

    public function countGroups(): int
    {
        return BannerGroupModel::count();
    }

    public function deactivateAll(): void
    {
        BannerGroupModel::query()->update(['is_active' => false]);
    }

    public function activateOne(int $id): void
    {
        BannerGroupModel::where('id', $id)->update(['is_active' => true]);
    }

    public function renameGroup(int $id, string $name): void
    {
        BannerGroupModel::where('id', $id)->update(['name' => $name]);
    }

    public function addMedia(int $groupId, string $mediaUrl, int $order): void
    {
        if (empty(trim($mediaUrl))) {
            throw new \Exception('No se pudo guardar el archivo en el disco. Verifica los permisos de almacenamiento.');
        }

        BannerModel::create([
            'banner_group_id' => $groupId,
            'media_url' => $mediaUrl,
            'display_order' => $order,
        ]);
    }

    public function removeMedia(int $mediaId): ?string
    {
        $media = BannerModel::find($mediaId);
        if (!$media) return null;

        $url = $media->media_url;
        $media->delete();
        return $url;
    }

    public function countMedia(int $groupId): int
    {
        return BannerModel::where('banner_group_id', $groupId)->count();
    }

    private function mapToDomain(BannerGroupModel $model): BannerGroup
    {
        $group = new BannerGroup(
            id: $model->id,
            name: $model->name,
            type: BannerType::from($model->type),
            isActive: $model->is_active
        );

        $mediaEntities = $model->media->map(fn($m) => new Banner(
            id: $m->id,
            bannerGroupId: $m->banner_group_id,
            mediaUrl: $m->media_url,
            displayOrder: $m->display_order
        ))->toArray();

        $group->setMedia($mediaEntities);
        return $group;
    }
}