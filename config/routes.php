<?php

use App\Controllers\AuthenticationsController;
use App\Controllers\HomeController;
use Core\Router\Route;

Route::get('/login', [AuthenticationsController::class, 'new'])->name('users.login');
Route::post('/login', [AuthenticationsController::class, 'authenticate'])->name('users.authenticate');

Route::get('/register', [AuthenticationsController::class, 'register'])->name('users.register');
Route::post('/register', [AuthenticationsController::class, 'create'])->name('users.create');

Route::middleware('auth')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('root');
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/logout', [AuthenticationsController::class, 'destroy'])->name('users.logout');
});
