<?php

namespace Core\Env;

use Core\Constants\Constants;

class EnvLoader
{
    public static function init(): void
    {
        $envFile  = (string) Constants::rootPath()->join('.env');
        $fileEnvs = file_exists($envFile) ? (parse_ini_file($envFile) ?: []) : [];

        foreach ($fileEnvs as $key => $value) {
            $_ENV[$key] = getenv($key) !== false ? getenv($key) : $value;
        }

        // Quando não há .env (ex: container de teste), popula $_ENV a partir das vars de ambiente do SO
        foreach ((getenv() ?: []) as $key => $value) {
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
            }
        }
    }
}
