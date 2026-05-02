<?php

namespace Core\Database\ActiveRecord;

use Core\Database\Database;
use PDO;

class BelongsToMany
{
    public function __construct(
        private Model $model,
        private string $related,
        private string $pivot_table,
        private string $from_foreign_key,
        private string $to_foreign_key
    ) {
    }

    public function get(): array
    {
        $related = $this->related;
        $attributes = implode(', ', array_map(fn($c) => "t.{$c}", $related::columns()));
        $sql = "SELECT t.id, {$attributes} FROM {$related::table()} t
                INNER JOIN {$this->pivot_table} p ON p.{$this->to_foreign_key} = t.id
                WHERE p.{$this->from_foreign_key} = :id";

        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $this->model->id);
        $stmt->execute();

        return array_map(fn($row) => new $related($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
