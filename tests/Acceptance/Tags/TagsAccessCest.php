<?php

namespace Tests\Acceptance\Tags;

use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class TagsAccessCest extends BaseAcceptanceCest
{
    // 3.1 - Rotas autenticadas não devem ser acessíveis por usuários não autenticados
    public function tagsRouteRedirectsGuestToLogin(AcceptanceTester $page): void
    {
        $page->amOnPage('/tags');
        $page->seeInCurrentUrl('/login');
    }
}
