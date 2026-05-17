<?php

namespace Tests\Acceptance\Orders;

use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class OrdersAccessCest extends BaseAcceptanceCest
{
    // 2.1 - Rotas autenticadas não devem ser acessíveis por usuários não autenticados
    public function ordersRoutesRedirectGuestToLogin(AcceptanceTester $page): void
    {
        $authenticatedRoutes = [
            '/orders',
            '/orders/new',
            '/orders/999',
            '/orders/999/edit',
        ];

        foreach ($authenticatedRoutes as $route) {
            $page->amOnPage($route);
            $page->seeInCurrentUrl('/login');
        }
    }
}
