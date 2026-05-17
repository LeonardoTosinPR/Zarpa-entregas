<?php

namespace Tests\Acceptance\Authentication;

use App\Models\User;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class LoginCest extends BaseAcceptanceCest
{
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

    // 1.1 - Tentativa de acesso à área restrita sem autenticação
    public function redirectsUnauthenticatedUserToLogin(AcceptanceTester $page): void
    {
        $page->amOnPage('/home');

        $page->seeInCurrentUrl('/login');
        $page->see('Você precisa estar logado para acessar esta página.');
    }

    // 1.2 - Tentativa de autenticação com dados incorretos
    public function showsErrorOnWrongCredentials(AcceptanceTester $page): void
    {
        $page->amOnPage('/login');
        $page->fillField('user[identifier]', 'naoexiste@example.com');
        $page->fillField('user[password]', 'senhaerrada');
        $page->click('Entrar');

        $page->seeInCurrentUrl('/login');
        $page->see('E-mail e/ou senha inválidos.');
    }

    // 1.3 - Autenticação bem-sucedida
    public function logsInSuccessfully(AcceptanceTester $page): void
    {
        $user = $this->makeUser();

        $page->login($user->email, '123456');

        $page->seeInCurrentUrl('/home');
        $page->see('Bem-vindo(a), Fulano de Tal!');
    }

    // 1.4 - Logout
    public function logsOutSuccessfully(AcceptanceTester $page): void
    {
        $user = $this->makeUser();

        $page->login($user->email, '123456');
        $page->seeInCurrentUrl('/home');

        $page->logout();

        $page->seeInCurrentUrl('/login');
        $page->see('Até logo!');
    }
}
