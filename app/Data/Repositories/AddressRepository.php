<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\IAddressRepository;
use App\Domain\Entities\Address;
use App\Models\AddressModel;

class AddressRepository implements IAddressRepository
{
    public function create(Address $address): Address
    {
        $model = AddressModel::create([
            'user_id' => $address->getUserId(),
            'label' => $address->getLabel(),
            'address_line' => $address->getAddressLine(),
            'is_default' => $address->isDefault(),
        ]);

        return $this->mapToDomain($model);
    }

    public function update(Address $address): Address
    {
        $model = AddressModel::findOrFail($address->getId());
        $model->update([
            'label' => $address->getLabel(),
            'address_line' => $address->getAddressLine(),
            'is_default' => $address->isDefault(),
        ]);

        return $this->mapToDomain($model->fresh());
    }

    public function findById(int $id): ?Address
    {
        $model = AddressModel::find($id);
        return $model ? $this->mapToDomain($model) : null;
    }

    public function findByIdAndUser(int $id, int $userId): ?Address
    {
        $model = AddressModel::where('id', $id)->where('user_id', $userId)->first();
        return $model ? $this->mapToDomain($model) : null;
    }

    public function findByUserId(int $userId): array
    {
        $models = AddressModel::where('user_id', $userId)->orderByDesc('is_default')->get();
        return $models->map(fn($m) => $this->mapToDomain($m))->toArray();
    }

    public function delete(int $id): bool
    {
        return (bool) AddressModel::destroy($id);
    }

    public function clearDefaultForUser(int $userId): void
    {
        AddressModel::where('user_id', $userId)->update(['is_default' => false]);
    }

    private function mapToDomain(AddressModel $model): Address
    {
        return new Address(
            id: $model->id,
            userId: $model->user_id,
            label: $model->label,
            addressLine: $model->address_line,
            isDefault: $model->is_default
        );
    }
}