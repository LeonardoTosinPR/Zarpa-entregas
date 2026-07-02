<?php

namespace Tests\Unit\Controllers;

use App\Controllers\OrdersController;
use App\Models\Order;
use App\Models\Tag;
use App\Models\User;
use Core\Http\Request;
use Lib\Authentication\Auth;
use Tests\TestCase;

class OrdersControllerTest extends TestCase
{
    private function makeClient(): User
    {
        $user = new User([
            'name' => 'Cliente Teste',
            'email' => 'cliente2@example.com',
            'cpf' => '52998224726',
            'password' => '123456',
            'password_confirmation' => '123456',
            'user_type' => User::USER_TYPE_CLIENT,
        ]);
        $user->save();
        return $user;
    }

    private function makeOrder(User $client): Order
    {
        $order = new Order([
            'client_id' => $client->id,
            'pickup_address' => 'Rua A, 1',
            'delivery_address' => 'Rua B, 2',
            'distance_km' => '1.00',
        ]);
        $order->save();
        return $order;
    }

    private function makeTag(array $overrides = []): Tag
    {
        $tag = new Tag(array_merge([
            'name' => 'Etiqueta ' . random_int(1000, 9999),
            'color' => Tag::COLOR_PRIMARY,
        ], $overrides));
        $tag->save();
        return $tag;
    }

}
