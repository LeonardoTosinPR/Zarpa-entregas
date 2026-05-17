<?php

namespace App\Controllers;

use App\Models\Order;
use App\Models\User;
use Core\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function dashboard(): void
    {
        $title = 'Admin';
        $users = User::all();
        $orders = Order::orderedByCreatedAt();

        $this->render('admin/dashboard', compact('title', 'users', 'orders'));
    }
}
