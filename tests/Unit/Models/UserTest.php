<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    private function makeUser(array $overrides = []): User
    {
        $user = new User(array_merge([
            'name'                  => 'Fulano de Tal',
            'email'                 => 'fulano@example.com',
            'cpf'                   => '52998224725',
            'password'              => '123456',
            'password_confirmation' => '123456',
            'user_type'             => User::USER_TYPE_CLIENT,
        ], $overrides));
        $user->save();
        return $user;
    }

    // 3.1 - authenticate()
    public function testAuthenticateReturnsTrueWithCorrectPassword(): void
    {
        $user = $this->makeUser();

        $this->assertTrue($user->authenticate('123456'));
    }

    public function testAuthenticateReturnsFalseWithWrongPassword(): void
    {
        $user = $this->makeUser();

        $this->assertFalse($user->authenticate('senhaerrada'));
    }

    // 3.1 - isAdmin() / isClient() / isDeliverer()
    public function testIsAdminReturnsFalseForRegularUser(): void
    {
        $user = $this->makeUser();

        $this->assertFalse($user->isAdmin());
    }

    public function testIsAdminReturnsTrueForAdminUser(): void
    {
        $user = $this->makeUser([
            'email'    => 'admin@example.com',
            'cpf'      => '11144477735',
            'is_admin' => 1,
        ]);

        $this->assertTrue($user->isAdmin());
    }

    public function testIsClientReturnsTrueForClientUserType(): void
    {
        $user = $this->makeUser(['user_type' => User::USER_TYPE_CLIENT]);

        $this->assertTrue($user->isClient());
        $this->assertFalse($user->isDeliverer());
    }

    public function testIsDelivererReturnsTrueForDelivererUserType(): void
    {
        $user = $this->makeUser([
            'email'     => 'entregador@example.com',
            'cpf'       => '11144477735',
            'user_type' => User::USER_TYPE_DELIVERER,
        ]);

        $this->assertTrue($user->isDeliverer());
        $this->assertFalse($user->isClient());
    }

    // 3.1 - findByEmail() / findByCpf() / findByIdentifier()
    public function testFindByEmailReturnsCorrectUser(): void
    {
        $user = $this->makeUser();

        $found = User::findByEmail('fulano@example.com');

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $this->assertNull(User::findByEmail('naoexiste@example.com'));
    }

    public function testFindByCpfReturnsCorrectUser(): void
    {
        $user = $this->makeUser();

        $found = User::findByCpf('529.982.247-25');

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function testFindByIdentifierAcceptsEmailOrCpf(): void
    {
        $user = $this->makeUser();

        $byEmail = User::findByIdentifier('fulano@example.com');
        $byCpf   = User::findByIdentifier('529.982.247-25');

        $this->assertEquals($user->id, $byEmail->id);
        $this->assertEquals($user->id, $byCpf->id);
    }

    // 3.1 - validates()
    public function testSaveFailsWithoutRequiredFields(): void
    {
        $user = new User();

        $this->assertFalse($user->save());
        $this->assertNotEmpty($user->errors('name'));
        $this->assertNotEmpty($user->errors('email'));
        $this->assertNotEmpty($user->errors('cpf'));
    }

    public function testSaveFailsWithDuplicateEmail(): void
    {
        $this->makeUser();

        $duplicate = new User([
            'name'                  => 'Outro Nome',
            'email'                 => 'fulano@example.com',
            'cpf'                   => '11144477735',
            'password'              => '123456',
            'password_confirmation' => '123456',
            'user_type'             => User::USER_TYPE_CLIENT,
        ]);

        $this->assertFalse($duplicate->save());
        $this->assertNotEmpty($duplicate->errors('email'));
    }
}
