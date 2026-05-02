<?php

namespace Core\Errors;

use Core\Exceptions\HTTPException;

class ErrorsHandler
{
    public static function init(): void
    {
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
            ob_start();
            ob_end_clean();
            echo "<h1>{$errstr}</h1>";
            echo "File: {$errfile} <br>";
            echo "Line: {$errline} <br>";
            echo "<br>";
            echo "Stack Trace: <br><pre>";
            debug_print_backtrace();
            echo "</pre>";
            exit;
        });

        set_exception_handler(function (\Throwable $e) {
            ob_start();
            ob_end_clean();

            if ($e instanceof HTTPException) {
                http_response_code($e->getCode());
            }

            echo "<h1>{$e->getMessage()}</h1>";
            echo "File: {$e->getFile()} <br>";
            echo "Line: {$e->getLine()} <br>";
            echo "<br>";
            echo "Stack Trace: <br><pre>{$e->getTraceAsString()}</pre>";
        });
    }
}
