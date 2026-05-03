<?php

namespace App\Middleware;

use Core\Http\Middleware\Middleware;
use Core\Http\Request;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class AdminOnly implements Middleware
{
    public function handle(Request $request): void
    {
        $user = Auth::user();

        if ($user === null) {
            FlashMessage::danger('VocÃª precisa estar logado para acessar esta pÃ¡gina.');
            header('Location: ' . route('users.login'));
            exit;
        }

        if (!$user->isAdmin()) {
            FlashMessage::danger('VocÃª nÃ£o tem permissÃ£o para acessar a Ã¡rea administrativa.');
            header('Location: ' . route('home'));
            exit;
        }
    }
}
