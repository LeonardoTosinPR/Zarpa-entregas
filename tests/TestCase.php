<?php

namespace Tests;

use Core\Database\Database;
use Core\Env\EnvLoader;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        EnvLoader::init();
        Database::drop();
        Database::create();
        Database::migrate();
    }

    protected function tearDown(): void
    {
        Database::drop();
        parent::tearDown();
    }
}
