<?php

namespace Tests\Unit\Lib;

use App\Models\User;
use Lib\Authentication\Auth;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    private function makeUser(): User
    {
        $user = new User([
            'name'                  => 'Fulano de Tal',
            'email'                 => 'fulano@example.com',
            'cpf'                   => '52998224725',
            'password'              => '123456',
            'password_confirmation' => '123456',
            'user_type'             => User::USER_TYPE_CLIENT,
        ]);
        $user->save();
        return $user;
    }

    public function testCheckReturnsFalseWhenSessionIsEmpty(): void
    {
        $this->assertFalse(Auth::check());
    }

    public function testCheckReturnsTrueAfterLogin(): void
    {
        $user = $this->makeUser();
        Auth::login($user);

        $this->assertTrue(Auth::check());
    }

    public function testLoginStoresUserIdInSession(): void
    {
        $user = $this->makeUser();
        Auth::login($user);

        $this->assertEquals($user->id, $_SESSION['user']['id']);
    }

    public function testUserReturnsNullWhenNotLoggedIn(): void
    {
        $this->assertNull(Auth::user());
    }

    public function testUserReturnsCorrectUserAfterLogin(): void
    {
        $user = $this->makeUser();
        Auth::login($user);

        $loggedUser = Auth::user();

        $this->assertNotNull($loggedUser);
        $this->assertEquals($user->id, $loggedUser->id);
        $this->assertEquals($user->email, $loggedUser->email);
    }

    public function testLogoutMakesCheckReturnFalse(): void
    {
        $user = $this->makeUser();
        Auth::login($user);
        $this->assertTrue(Auth::check());

        Auth::logout();

        $this->assertFalse(Auth::check());
    }

    public function testLogoutClearsSessionUserId(): void
    {
        $user = $this->makeUser();
        Auth::login($user);

        Auth::logout();

        $this->assertArrayNotHasKey('id', $_SESSION['user'] ?? []);
    }
}
