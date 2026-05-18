<?php

namespace Tests\Unit\Lib;

use Lib\Validations;
use PHPUnit\Framework\TestCase;

class ValidationsTest extends TestCase
{
    private function stub(array $props = []): object
    {
        $obj = new #[\AllowDynamicProperties] class {
            public array $errors = [];

            public function addError(string $field, string $message): void
            {
                $this->errors[$field] = $message;
            }
        };

        foreach ($props as $key => $value) {
            $obj->$key = $value;
        }

        return $obj;
    }

    public function testNotEmptyReturnsFalseForNullValue(): void
    {
        $obj = $this->stub(['name' => null]);

        $this->assertFalse(Validations::notEmpty('name', $obj));
        $this->assertArrayHasKey('name', $obj->errors);
    }

    public function testNotEmptyReturnsTrueForNonEmptyValue(): void
    {
        $obj = $this->stub(['name' => 'Fulano']);

        $this->assertTrue(Validations::notEmpty('name', $obj));
        $this->assertEmpty($obj->errors);
    }

    public function testEmailReturnsFalseForInvalidEmail(): void
    {
        $obj = $this->stub(['email' => 'nao-e-email']);

        $this->assertFalse(Validations::email('email', $obj));
        $this->assertArrayHasKey('email', $obj->errors);
    }

    public function testEmailReturnsTrueForValidEmail(): void
    {
        $obj = $this->stub(['email' => 'fulano@example.com']);

        $this->assertTrue(Validations::email('email', $obj));
        $this->assertEmpty($obj->errors);
    }

    public function testMinLengthReturnsFalseWhenTooShort(): void
    {
        $obj = $this->stub(['name' => 'AB']);

        $this->assertFalse(Validations::minLength('name', $obj, 3));
        $this->assertArrayHasKey('name', $obj->errors);
    }

    public function testMinLengthReturnsTrueWhenLongEnough(): void
    {
        $obj = $this->stub(['name' => 'Fulano']);

        $this->assertTrue(Validations::minLength('name', $obj, 3));
        $this->assertEmpty($obj->errors);
    }

    public function testNotFutureDateReturnsFalseForFutureDate(): void
    {
        $obj = $this->stub(['birth_date' => '2099-12-31']);

        $this->assertFalse(Validations::notFutureDate('birth_date', $obj));
        $this->assertArrayHasKey('birth_date', $obj->errors);
    }

    public function testNotFutureDateReturnsTrueForPastDate(): void
    {
        $obj = $this->stub(['birth_date' => '1990-06-15']);

        $this->assertTrue(Validations::notFutureDate('birth_date', $obj));
        $this->assertEmpty($obj->errors);
    }

    public function testNotFutureDateReturnsTrueForNullDate(): void
    {
        $obj = $this->stub(['birth_date' => null]);

        $this->assertTrue(Validations::notFutureDate('birth_date', $obj));
        $this->assertEmpty($obj->errors);
    }

    public function testInclusionReturnsFalseWhenValueNotInList(): void
    {
        $obj = $this->stub(['user_type' => 'superadmin']);

        $this->assertFalse(Validations::inclusion('user_type', $obj, ['client', 'deliverer']));
        $this->assertArrayHasKey('user_type', $obj->errors);
    }

    public function testInclusionReturnsTrueWhenValueInList(): void
    {
        $obj = $this->stub(['user_type' => 'client']);

        $this->assertTrue(Validations::inclusion('user_type', $obj, ['client', 'deliverer']));
        $this->assertEmpty($obj->errors);
    }

    public function testPasswordConfirmationReturnsFalseWhenMismatch(): void
    {
        $obj = $this->stub(['password' => '123456', 'password_confirmation' => 'diferente']);

        $this->assertFalse(Validations::passwordConfirmation($obj));
        $this->assertArrayHasKey('password', $obj->errors);
    }

    public function testPasswordConfirmationReturnsTrueWhenMatch(): void
    {
        $obj = $this->stub(['password' => '123456', 'password_confirmation' => '123456']);

        $this->assertTrue(Validations::passwordConfirmation($obj));
        $this->assertEmpty($obj->errors);
    }

    public function testCpfReturnsTrueForKnownValidCpf(): void
    {
        $obj = $this->stub(['cpf' => '52998224725']);

        $this->assertTrue(Validations::cpf('cpf', $obj));
        $this->assertEmpty($obj->errors);
    }

    public function testCpfReturnsFalseForAllRepeatedDigits(): void
    {
        $obj = $this->stub(['cpf' => '11111111111']);

        $this->assertFalse(Validations::cpf('cpf', $obj));
        $this->assertArrayHasKey('cpf', $obj->errors);
    }

    public function testCpfReturnsFalseForWrongCheckDigits(): void
    {
        $obj = $this->stub(['cpf' => '52998224700']);

        $this->assertFalse(Validations::cpf('cpf', $obj));
        $this->assertArrayHasKey('cpf', $obj->errors);
    }
}
