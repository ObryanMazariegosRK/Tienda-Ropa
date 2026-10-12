<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\IMailSenderRepository;
use App\Domain\Entities\MailSender;
use App\Models\MailSenderModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class MailSenderRepository implements IMailSenderRepository
{
    public function findAll(): array
    {
        return MailSenderModel::orderBy('id')->get()->map(fn($m) => $this->mapToDomain($m))->all();
    }

    public function findById(int $id): ?MailSender
    {
        $model = MailSenderModel::find($id);
        return $model ? $this->mapToDomain($model) : null;
    }

    public function findByEmail(string $email): ?MailSender
    {
        $model = MailSenderModel::where('email', strtolower(trim($email)))->first();
        return $model ? $this->mapToDomain($model) : null;
    }

    public function findActive(): ?MailSender
    {
        $model = MailSenderModel::where('is_active', true)->first();
        return $model ? $this->mapToDomain($model) : null;
    }

    public function create(MailSender $sender): MailSender
    {
        $model = MailSenderModel::create([
            'from_name' => $sender->getFromName(),
            'email' => $sender->getEmail(),
            'password' => $sender->getPassword(),
            'host' => $sender->getHost(),
            'port' => $sender->getPort(),
            'is_active' => false,
        ]);
        return $this->mapToDomain($model);
    }

    public function update(MailSender $sender, bool $resetTested): MailSender
    {
        $model = MailSenderModel::findOrFail($sender->getId());
        $model->fill([
            'from_name' => $sender->getFromName(),
            'email' => $sender->getEmail(),
            'host' => $sender->getHost(),
            'port' => $sender->getPort(),
        ]);
        if ($sender->getPassword() !== null) {
            $model->password = $sender->getPassword();
        }
        if ($resetTested) {
            $model->last_tested_at = null;
        }
        $model->save();
        return $this->mapToDomain($model);
    }

    public function delete(int $id): void
    {
        MailSenderModel::destroy($id);
    }

    public function setActive(?int $id): void
    {
        DB::transaction(function () use ($id) {
            MailSenderModel::where('is_active', true)->update(['is_active' => false]);
            if ($id !== null) {
                MailSenderModel::where('id', $id)->update(['is_active' => true]);
            }
        });
    }

    public function markTested(int $id): void
    {
        MailSenderModel::where('id', $id)->update(['last_tested_at' => now()]);
    }

    private function mapToDomain(MailSenderModel $model): MailSender
    {
        return new MailSender(
            id: $model->id,
            fromName: $model->from_name,
            email: $model->email,
            password: $model->password,
            host: $model->host,
            port: $model->port,
            isActive: $model->is_active,
            lastTestedAt: $model->last_tested_at ? DateTimeImmutable::createFromInterface($model->last_tested_at) : null
        );
    }
}
