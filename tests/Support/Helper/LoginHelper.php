<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use Codeception\Module;

class LoginHelper extends Module
{
    public function login(string $identifier, string $password): void
    {
        /** @var \Codeception\Module\WebDriver $page */
        $page = $this->getModule('WebDriver');
        $page->amOnPage('/login');
        $page->fillField('user[identifier]', $identifier);
        $page->fillField('user[password]', $password);
        $page->click('Entrar');
    }

    public function logout(): void
    {
        /** @var \Codeception\Module\WebDriver $page */
        $page = $this->getModule('WebDriver');
        $page->amOnPage('/logout');
    }
}
