<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Lib\Authentication\Auth;

class HomeController extends Controller
{
    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirectTo(route('users.login'));
            return;
        }

        $title = 'Início';
        $this->render('home/index', compact('title'));
    }
}
