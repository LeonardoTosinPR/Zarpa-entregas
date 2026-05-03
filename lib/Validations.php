<?php

namespace Lib;

use Core\Database\Database;

class Validations
{
    public static function notEmpty($attribute, $obj)
    {
        if ($obj->$attribute === null || $obj->$attribute === '') {
            $obj->addError($attribute, 'nao pode ser vazio');
            return false;
        }

        return true;
    }

    public static function email($attribute, $obj)
    {
        if ($obj->$attribute !== null && $obj->$attribute !== '' && !filter_var($obj->$attribute, FILTER_VALIDATE_EMAIL)) {
            $obj->addError($attribute, 'deve ser um e-mail valido');
            return false;
        }

        return true;
    }

    public static function minLength($attribute, $obj, int $length)
    {
        if ($obj->$attribute !== null && $obj->$attribute !== '' && strlen($obj->$attribute) < $length) {
            $obj->addError($attribute, "deve ter pelo menos {$length} caracteres");
            return false;
        }

        return true;
    }

    public static function notFutureDate($attribute, $obj)
    {
        if ($obj->$attribute === null || $obj->$attribute === '') {
            return true;
        }

        $date = strtotime($obj->$attribute);

        if ($date === false || $date > strtotime('today')) {
            $obj->addError($attribute, 'nao pode ser uma data futura');
            return false;
        }

        return true;
    }

    public static function inclusion($attribute, $obj, array $allowedValues)
    {
        if (!in_array($obj->$attribute, $allowedValues, true)) {
            $obj->addError($attribute, 'possui uma opcao invalida');
            return false;
        }

        return true;
    }

    public static function passwordConfirmation($obj)
    {
        if ($obj->password !== $obj->password_confirmation) {
            $obj->addError('password', 'as senhas devem ser iguais');
            return false;
        }

        return true;
    }

    public static function uniqueness($fields, $object)
    {
        $dbFieldsValues = [];
        $objFieldValues = [];

        if (!is_array($fields)) {
            $fields = [$fields];
        }

        if (!$object->newRecord()) {
            $dbObject = $object::findById($object->id);

            foreach ($fields as $field) {
                $dbFieldsValues[] = $dbObject->$field;
                $objFieldValues[] = $object->$field;
            }

            if (
                !empty($dbFieldsValues) &&
                !empty($objFieldValues) &&
                $dbFieldsValues === $objFieldValues
            ) {
                return true;
            }
        }

        $table = $object::table();
        $conditions = implode(' AND ', array_map(fn($field) => "{$field} = :{$field}", $fields));

        $sql = <<<SQL
            SELECT id FROM {$table} WHERE {$conditions};
        SQL;

        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare($sql);

        foreach ($fields as $field) {
            $stmt->bindValue($field, $object->$field);
        }

        $stmt->execute();

        if ($stmt->rowCount() !== 0) {
            foreach ($fields as $field) {
                $object->addError($field, 'ja existe um registro com esse dado');
            }
            return false;
        }

        return true;
    }
}
