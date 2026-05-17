<?php

namespace Tests\Acceptance\Access;

use App\Models\User;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class AccessCest extends BaseAcceptanceCest
{
    private function makeClient(): User
    {
        $user = new User([
            'name'                  => 'Cliente Teste',
            'email'                 => 'cliente@example.com',
            'cpf'                   => '52998224725',
            'password'              => '123456',
            'password_confirmation' => '123456',
            'user_type'             => User::USER_TYPE_CLIENT,
        ]);
        $user->save();
        return $user;
    }

    private function makeAdmin(): User
    {
        $user = new User([
            'name'                  => 'Admin Teste',
            'email'                 => 'admin@example.com',
            'cpf'                   => '11144477735',
            'password'              => '123456',
            'password_confirmation' => '123456',
            'user_type'             => User::USER_TYPE_CLIENT,
            'is_admin'              => 1,
        ]);
        $user->save();
        return $user;
    }

    // 2.1 - Rotas autenticadas: acessadas somente por usuários autenticados
    public function authenticatedRoutesRedirectGuestToLogin(AcceptanceTester $page): void
    {
        $authenticatedRoutes = ['/home', '/orders', '/orders/new'];

        foreach ($authenticatedRoutes as $route) {
            $page->amOnPage($route);
            $page->seeInCurrentUrl('/login');
        }
    }

    // 2.1 - Rota admin: apenas admins podem acessar
    public function adminRouteBlocksNonAdminUser(AcceptanceTester $page): void
    {
        $client = $this->makeClient();
        $page->login($client->email, '123456');

        $page->amOnPage('/admin');

        $page->seeInCurrentUrl('/home');
        $page->see('Você não tem permissão para acessar a área administrativa.');
    }

    // 2.2 - Rotas públicas: acessadas por qualquer usuário (sem login)
    public function publicRoutesAreAccessibleWithoutLogin(AcceptanceTester $page): void
    {
        $page->amOnPage('/login');
        $page->seeInCurrentUrl('/login');

        $page->amOnPage('/register');
        $page->seeInCurrentUrl('/register');
    }

    // 2.3 - Rotas públicas que não devem permitir usuários autenticados
    public function loginPageRedirectsAlreadyAuthenticatedUser(AcceptanceTester $page): void
    {
        $client = $this->makeClient();
        $page->login($client->email, '123456');
        $page->seeInCurrentUrl('/home');

        $page->amOnPage('/login');

        $page->seeInCurrentUrl('/home');
    }
}
