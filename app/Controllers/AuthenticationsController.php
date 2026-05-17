<?php

namespace App\Controllers;

use App\Models\User;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class AuthenticationsController extends Controller
{
    protected string $layout = 'login';

    public function new(): void
    {
        if (Auth::check()) {
            $this->redirectTo(route('home'));
            return;
        }

        $this->render('authentications/new');
    }

    public function authenticate(Request $request): void
    {
        $params = $request->getParam('user');
        $user = User::findByIdentifier($params['identifier']);

        if ($user && $user->authenticate($params['password'])) {
            Auth::login($user);
            FlashMessage::success('Bem-vindo(a), ' . $user->name . '!');
            $this->redirectTo($user->isAdmin() ? route('admin.dashboard') : route('home'));
        } else {
            FlashMessage::danger('E-mail e/ou senha inválidos.');
            $this->redirectTo(route('users.login'));
        }
    }

    public function register(): void
    {
        if (Auth::check()) {
            $this->redirectTo(route('home'));
            return;
        }

        $user = new User();
        $this->render('authentications/register', compact('user'));
    }

    public function create(Request $request): void
    {
        $params = $request->getParam('user');
        unset($params['is_admin']);

        $user = new User($params);

        if ($user->save()) {
            Auth::login($user);
            FlashMessage::success('Cadastro realizado! Bem-vindo(a), ' . $user->name . '!');
            $this->redirectTo(route('home'));
        } else {
            FlashMessage::danger('Verifique os dados informados.');
            $this->render('authentications/register', compact('user'));
        }
    }

    public function destroy(): void
    {
        Auth::logout();
        FlashMessage::success('Até logo!');
        $this->redirectTo(route('users.login'));
    }
}
