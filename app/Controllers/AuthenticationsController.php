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

    public function verifyCode(): void
    {
        $this->render('authentications/verify_code');
    }

    public function sendCode(Request $request): void
    {
        $email = $request->getParam('email');
        $user = User::findByEmail($email);

        if (!$user) {
            FlashMessage::danger('E-mail não encontrado.');
            $this->redirectTo(route('password.verify'));
            return;
        }

        $code = random_int(1000, 9999);
        $user->update(['code' => $code]);

        $_SESSION['password_reset_email'] = $email;
        $_SESSION['reset_attempts'] = 0;

        FlashMessage::success('Código enviado para o seu e-mail.');
        $this->render('authentications/verify_code');
    }

    public function handleCode(Request $request): void
    {
        $email = $_SESSION['password_reset_email'] ?? null;
        $submitted = $request->getParam('code');

        if (!$email) {
            $this->redirectTo(route('password.verify'));
            return;
        }

        $user = User::findByEmail($email);

        if ($user && (string) $user->code === (string) $submitted) {
            $user->update(['code' => null]);
            unset($_SESSION['password_reset_email'], $_SESSION['reset_attempts']);
            FlashMessage::success('Código verificado! Faça login para continuar.');
            $this->redirectTo(route('users.login'));
        } else {
            $_SESSION['reset_attempts'] = ($_SESSION['reset_attempts'] ?? 0) + 1;
            FlashMessage::danger('Código inválido. Tente novamente.');
            $this->render('authentications/verify_code');
        }
    }
}
