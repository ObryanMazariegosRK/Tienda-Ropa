<?php

namespace Tests\Unit;

use App\Domain\Entities\User;
use App\Domain\Enum\RoleType;
use PHPUnit\Framework\TestCase;

class UserClaimWithProviderTest extends TestCase
{
    private function usuario(?\DateTimeImmutable $verificadoEn): User
    {
        return new User(
            id: 1, name: 'Ana', lastName: 'Lopez', email: 'ana@test.com',
            password: 'hash-de-alguien-mas', phone: null, role: RoleType::CUSTOMER,
            emailVerifiedAt: $verificadoEn
        );
    }

    public function test_cuenta_sin_verificar_pierde_la_contrasena_y_queda_verificada(): void
    {
        $user = $this->usuario(null);

        $user->claimWithVerifiedProvider(new \DateTimeImmutable());

        $this->assertNull($user->getPassword());
        $this->assertTrue($user->isEmailVerified());
    }

    public function test_cuenta_ya_verificada_conserva_su_contrasena(): void
    {
        $user = $this->usuario(new \DateTimeImmutable('-1 day'));

        $user->claimWithVerifiedProvider(new \DateTimeImmutable());

        $this->assertSame('hash-de-alguien-mas', $user->getPassword());
    }
}
