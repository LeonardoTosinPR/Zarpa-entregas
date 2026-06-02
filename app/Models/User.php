<?php

namespace App\Models;

use Lib\Validations;
use Core\Database\ActiveRecord\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static array $columns = ['name', 'email', 'encrypted_password', 'birth_date', 'cpf', 'avatar_name', 'username', 'phone', 'user_type', 'is_admin', 'code'];

    public const USER_TYPE_CLIENT = 'client';
    public const USER_TYPE_DELIVERER = 'deliverer';

    protected ?string $password = null;
    protected ?string $password_confirmation = null;

    public function __construct($params = [])
    {
        parent::__construct($params);

        if ($this->is_admin === null) {
            $this->is_admin = 0;
        }

        if ($this->user_type === null) {
            $this->user_type = self::USER_TYPE_CLIENT;
        }
    }

    public function validates(): void
    {
        Validations::notEmpty('name', $this);
        Validations::minLength('name', $this, 3);
        Validations::notEmpty('email', $this);
        Validations::email('email', $this);
        Validations::uniqueness('email', $this);
        Validations::notEmpty('username', $this);
        Validations::minLength('username', $this, 3);
        Validations::uniqueness('username', $this);
        Validations::notEmpty('phone', $this);
        Validations::phone('phone', $this);
        Validations::uniqueness('phone', $this);
        Validations::notEmpty('cpf', $this);
        Validations::cpf('cpf', $this);
        Validations::uniqueness('cpf', $this);
        Validations::notFutureDate('birth_date', $this);
        Validations::inclusion('user_type', $this, [self::USER_TYPE_CLIENT, self::USER_TYPE_DELIVERER]);

        if ($this->newRecord()) {
            Validations::notEmpty('password', $this);
            Validations::minLength('password', $this, 6);
            Validations::passwordConfirmation($this);
        }
    }

    public function authenticate(string $password): bool
    {
        if ($this->encrypted_password == null) {
            return false;
        }

        return password_verify($password, $this->encrypted_password);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isClient(): bool
    {
        return $this->user_type === self::USER_TYPE_CLIENT;
    }

    public function isDeliverer(): bool
    {
        return $this->user_type === self::USER_TYPE_DELIVERER;
    }

    public function userTypeLabel(): string
    {
        return $this->isDeliverer() ? 'Entregador' : 'Cliente';
    }

    public static function findByEmail(string $email): ?User
    {
        return User::findBy(['email' => $email]);
    }

    public static function findByCpf(string $cpf): ?User
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        return User::findBy(['cpf' => $cpf]);
    }

    public static function findByUsername(string $username): ?User
    {
        return User::findBy(['username' => $username]);
    }

    public static function findByPhone(string $phone): ?User
    {
        $phone = preg_replace('/\D/', '', $phone);
        return User::findBy(['phone' => $phone]);
    }

    public static function findByIdentifier(string $identifier): ?User
    {
        if (str_contains($identifier, '@')) return self::findByEmail($identifier);
        if (str_contains($identifier, '(')) return self::findByPhone($identifier);
        if (preg_match('/^\d/', $identifier)) return self::findByCpf($identifier);
        return self::findByUsername($identifier);
    }

    public function __set(string $property, mixed $value): void
    {
        if ($property === 'birth_date' && $value === '') {
            $value = null;
        }

        if ($property === 'cpf' && $value !== null && $value !== '') {
            $value = preg_replace('/\D/', '', $value);
        }

        if ($property === 'phone' && $value !== null && $value !== '') {
            $value = preg_replace('/\D/', '', $value);
        }

        parent::__set($property, $value);

        if ($property === 'password' && $this->newRecord() && $value !== null && $value !== '') {
            $this->encrypted_password = password_hash($value, PASSWORD_DEFAULT);
        }
    }
}
