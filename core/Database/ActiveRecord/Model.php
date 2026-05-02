<?php

namespace Core\Database\ActiveRecord;

use Core\Database\Database;
use Lib\Paginator;
use Lib\StringUtils;
use PDO;
use ReflectionMethod;

abstract class Model
{
    protected array $errors = [];
    protected ?int $id = null;
    private array $attributes = [];
    protected static string $table = '';
    protected static array $columns = [];

    public function __construct($params = [])
    {
        foreach (static::$columns as $column) {
            $this->attributes[$column] = null;
        }

        foreach ($params as $property => $value) {
            $this->__set($property, $value);
        }
    }

    public function __get(string $property): mixed
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        if (array_key_exists($property, $this->attributes)) {
            return $this->attributes[$property];
        }

        $method = StringUtils::lowerSnakeToCamelCase($property);
        if (method_exists($this, $method)) {
            $reflectionMethod = new ReflectionMethod($this, $method);
            $returnType = $reflectionMethod->getReturnType();

            $allowedTypes = [
                'Core\Database\ActiveRecord\BelongsTo',
                'Core\Database\ActiveRecord\HasMany',
                'Core\Database\ActiveRecord\BelongsToMany'
            ];

            if ($returnType !== null && in_array($returnType->getName(), $allowedTypes)) {
                return $this->$method()->get();
            }
        }

        throw new \Exception("Property {$property} not found in " . static::class);
    }

    public function __set(string $property, mixed $value): void
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
            return;
        }

        if (array_key_exists($property, $this->attributes)) {
            $this->attributes[$property] = $value;
            return;
        }

        throw new \Exception("Property {$property} not found in " . static::class);
    }

    public static function table(): string
    {
        return static::$table;
    }

    public static function columns(): array
    {
        return static::$columns;
    }

    public function isValid(): bool
    {
        $this->errors = [];
        $this->validates();
        return empty($this->errors);
    }

    public function newRecord(): bool
    {
        return $this->id === null;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function errors(string $index = null): string | null
    {
        return $this->errors[$index] ?? null;
    }

    public function addError(string $index, string $value): void
    {
        $this->errors[$index] = $value;
    }

    public function validates(): void {}

    public function save(): bool
    {
        if ($this->isValid()) {
            $pdo = Database::getDatabaseConn();
            if ($this->newRecord()) {
                $table = static::$table;
                $attributes = implode(', ', static::$columns);
                $values = ':' . implode(', :', static::$columns);

                $sql = "INSERT INTO {$table} ({$attributes}) VALUES ({$values});";
                $stmt = $pdo->prepare($sql);
                foreach (static::$columns as $column) {
                    $stmt->bindValue($column, $this->$column);
                }
                $stmt->execute();
                $this->id = (int) $pdo->lastInsertId();
            } else {
                $table = static::$table;
                $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", static::$columns));
                $sql = "UPDATE {$table} set {$sets} WHERE id = :id;";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':id', $this->id);
                foreach (static::$columns as $column) {
                    $stmt->bindValue($column, $this->$column);
                }
                $stmt->execute();
            }
            return true;
        }
        return false;
    }

    public function update(array $data): bool
    {
        $table = static::$table;
        $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
        $sql = "UPDATE {$table} set {$sets} WHERE id = :id;";

        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $this->id);
        foreach ($data as $column => $value) {
            $stmt->bindValue($column, $value);
            $this->$column = $value;
        }
        $stmt->execute();
        return ($stmt->rowCount() !== 0);
    }

    public function destroy(): bool
    {
        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare("DELETE FROM " . static::$table . " WHERE id = :id;");
        $stmt->bindValue(':id', $this->id);
        $stmt->execute();
        return ($stmt->rowCount() != 0);
    }

    public static function findById(int $id): static|null
    {
        $pdo = Database::getDatabaseConn();
        $attributes = implode(', ', static::$columns);
        $table = static::$table;
        $stmt = $pdo->prepare("SELECT id, {$attributes} FROM {$table} WHERE id = :id;");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            return null;
        }

        return new static($stmt->fetch(PDO::FETCH_ASSOC));
    }

    public static function all(): array
    {
        $attributes = implode(', ', static::$columns);
        $table = static::$table;
        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare("SELECT id, {$attributes} FROM {$table};");
        $stmt->execute();

        return array_map(fn($row) => new static($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public static function paginate(int $page = 1, int $per_page = 10, string $route = null): Paginator
    {
        return new Paginator(
            class: static::class,
            page: $page,
            per_page: $per_page,
            table: static::$table,
            attributes: static::$columns,
            route: $route
        );
    }

    public static function where(array $conditions): array
    {
        $table = static::$table;
        $attributes = implode(', ', static::$columns);
        $sqlConditions = implode(' AND ', array_map(fn($c) => "{$c} = :{$c}", array_keys($conditions)));
        $sql = "SELECT id, {$attributes} FROM {$table} WHERE {$sqlConditions}";

        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare($sql);
        foreach ($conditions as $column => $value) {
            $stmt->bindValue($column, $value);
        }
        $stmt->execute();

        return array_map(fn($row) => new static($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public static function findBy($conditions): ?static
    {
        $resp = self::where($conditions);
        return $resp[0] ?? null;
    }

    public static function exists($conditions): bool
    {
        return !empty(self::where($conditions));
    }

    public function belongsTo(string $related, string $foreignKey): BelongsTo
    {
        return new BelongsTo($this, $related, $foreignKey);
    }

    public function hasMany(string $related, string $foreignKey): HasMany
    {
        return new HasMany($this, $related, $foreignKey);
    }

    public function BelongsToMany(string $related, string $pivot_table, string $from_foreign_key, string $to_foreign_key): BelongsToMany
    {
        return new BelongsToMany($this, $related, $pivot_table, $from_foreign_key, $to_foreign_key);
    }
}
