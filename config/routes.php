<?php

use App\Controllers\AuthenticationsController;
use App\Controllers\AdminController;
use App\Controllers\HomeController;
use App\Controllers\OrdersController;
use App\Controllers\TagsController;
use Core\Router\Route;

Route::get('/login', [AuthenticationsController::class, 'new'])->name('users.login');
Route::post('/login', [AuthenticationsController::class, 'authenticate'])->name('users.authenticate');

Route::get('/register', [AuthenticationsController::class, 'register'])->name('users.register');
Route::post('/register', [AuthenticationsController::class, 'create'])->name('users.create');

Route::middleware('auth')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('root');
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/logout', [AuthenticationsController::class, 'destroy'])->name('users.logout');

    Route::get('/orders', [OrdersController::class, 'index'])->name('orders.index');
    Route::get('/orders/new', [OrdersController::class, 'new'])->name('orders.new');
    Route::post('/orders', [OrdersController::class, 'create'])->name('orders.create');
    Route::get('/orders/{id}', [OrdersController::class, 'show'])->name('orders.show');
    Route::get('/orders/{id}/edit', [OrdersController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{id}', [OrdersController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{id}', [OrdersController::class, 'destroy'])->name('orders.destroy');
    Route::post('/orders/{id}/accept', [OrdersController::class, 'accept'])->name('orders.accept');
    Route::post('/orders/{id}/refuse', [OrdersController::class, 'refuse'])->name('orders.refuse');
    Route::post('/orders/{id}/tags', [OrdersController::class, 'attachTag'])->name('order_tags.create');
    Route::delete('/orders/{id}/tags/{tag_id}', [OrdersController::class, 'detachTag'])->name('order_tags.destroy');

    Route::get('/tags', [TagsController::class, 'index'])->name('tags.index');
    Route::post('/tags', [TagsController::class, 'create'])->name('tags.create');
    Route::delete('/tags/{id}', [TagsController::class, 'destroy'])->name('tags.destroy');

    // Rotas Ajax para Tags
    Route::get('/api/tags', [TagsController::class, 'listAjax'])->name('api.tags.list');
    Route::post('/api/tags', [TagsController::class, 'createAjax'])->name('api.tags.create');
    Route::delete('/api/tags/{id}', [TagsController::class, 'destroyAjax'])->name('api.tags.destroy');
});

Route::middleware('admin')->group(function () {
    Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');
});
