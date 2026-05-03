<?php

namespace App\Models;

use Lib\Validations;
use Core\Database\ActiveRecord\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static array $columns = ['name', 'email', 'encrypted_password', 'birth_date', 'cpf', 'avatar_name', 'user_type', 'is_admin'];

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

    public static function findByIdentifier(string $identifier): ?User
    {
        return str_contains($identifier, '@')
            ? self::findByEmail($identifier)
            : self::findByCpf($identifier);
    }

    public function __set(string $property, mixed $value): void
    {
        if ($property === 'birth_date' && $value === '') {
            $value = null;
        }

        parent::__set($property, $value);

        if ($property === 'password' && $this->newRecord() && $value !== null && $value !== '') {
            $this->encrypted_password = password_hash($value, PASSWORD_DEFAULT);
        }
    }
}
