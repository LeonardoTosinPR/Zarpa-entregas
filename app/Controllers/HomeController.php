<?php

namespace App\Controllers;

use App\Models\Order;
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

        $title = 'Inicio';
        $orders = Order::visibleFor($this->currentUser());

        $this->render('home/index', compact('title', 'orders'));
    }
}
