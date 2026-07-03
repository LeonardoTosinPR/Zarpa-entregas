<?php
require __DIR__ . '/vendor/autoload.php';

use Core\Env\EnvLoader;
use Core\Database\Database;
use App\Models\User;
use App\Models\Order;
use ReflectionObject;

EnvLoader::init();

$user = new User([
    'name' => 'Teste',
    'email' => 'test' . rand(1, 9999) . '@example.com',
    'cpf' => '52998224726',
    'password' => '123456',
    'password_confirmation' => '123456',
    'user_type' => User::USER_TYPE_CLIENT,
]);
var_dump('user_save', $user->save(), 'user_id', $user->id);
var_dump('user_errors', $user->errors());
$refUser = new ReflectionObject($user);
$errorsProp = $refUser->getProperty('errors');
$errorsProp->setAccessible(true);
var_dump('user_internal_errors', $errorsProp->getValue($user));

$order = new Order([
    'client_id' => $user->id,
    'pickup_address' => 'Rua A',
    'delivery_address' => 'Rua B',
    'distance_km' => '1.00',
]);
var_dump('order_before_save', $order->id);
var_dump('order_save', $order->save(), 'order_id', $order->id);
var_dump('order_errors', $order->errors());
$found = Order::findById($order->id);
var_dump('found', $found !== null, 'found_id', $found?->id, 'found_client_id', $found?->client_id, 'found_status', $found?->status);
