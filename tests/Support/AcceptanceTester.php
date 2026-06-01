<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * @method void amOnPage(string $url)
 * @method void fillField(string $field, string $value)
 * @method void attachFile(string $field, string $filename)
 * @method void click(string $button)
 * @method void see(string $text, string $selector = null)
 * @method void dontSee(string $text, string $selector = null)
 * @method void seeInCurrentUrl(string $url)
 * @method void dontSeeInCurrentUrl(string $url)
 * @method void acceptPopup()
 * @method void login(string $identifier, string $password)
 * @method void logout()
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;
}
