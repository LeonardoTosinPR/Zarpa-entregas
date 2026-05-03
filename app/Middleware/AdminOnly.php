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
            FlashMessage::danger('Você precisa estar logado para acessar esta página.');
            header('Location: ' . route('users.login'));
            exit;
        }

        if (!$user->isAdmin()) {
            FlashMessage::danger('Você não tem permissão para acessar a área administrativa.');
            header('Location: ' . route('home'));
            exit;
        }
    }
}
