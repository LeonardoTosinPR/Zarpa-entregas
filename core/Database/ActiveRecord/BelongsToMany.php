<?php

namespace Core\Database\ActiveRecord;

use Core\Database\Database;
use PDO;

class BelongsToMany
{
    public function __construct(
        private Model  $model,
        private string $related,
        private string $pivot_table,
        private string $from_foreign_key,
        private string $to_foreign_key,
    ) {
    }

    /**
     * @return array<Model>
     */
    public function get()
    {
        $fromTable = $this->model::table();
        $toTable = $this->related::table();

        $attributes = $toTable . '.id, ';
        foreach ($this->related::columns() as $column) {
            $attributes .= $toTable . '.' . $column . ', ';
        }
        $attributes = rtrim($attributes, ', ');

        $sql = <<<SQL
            SELECT 
                {$attributes}
            FROM 
                {$fromTable}, {$toTable}, {$this->pivot_table}
            WHERE 
                {$toTable}.id = {$this->pivot_table}.{$this->to_foreign_key} AND
                {$fromTable}.id = {$this->pivot_table}.{$this->from_foreign_key} AND
                {$fromTable}.id = :id
        SQL;

        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(':id', $this->model->id);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $models = [];
        foreach ($rows as $row) {
            $models[] = new $this->related($row);
        }

        return $models;
    }

    public function attach(int $id): void
    {
        $sql = <<<SQL
            INSERT INTO {$this->pivot_table} ({$this->from_foreign_key}, {$this->to_foreign_key})
            VALUES (:from_id, :to_id);
        SQL;

        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':from_id', $this->model->id);
        $stmt->bindValue(':to_id', $id);
        $stmt->execute();
    }

    public function detach(int $id): void
    {
        $sql = <<<SQL
            DELETE FROM {$this->pivot_table}
            WHERE {$this->from_foreign_key} = :from_id AND {$this->to_foreign_key} = :to_id;
        SQL;

        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':from_id', $this->model->id);
        $stmt->bindValue(':to_id', $id);
        $stmt->execute();
    }

    public function exists(int $id): bool
    {
        $sql = <<<SQL
            SELECT 1
            FROM {$this->pivot_table}
            WHERE {$this->from_foreign_key} = :from_id AND {$this->to_foreign_key} = :to_id
            LIMIT 1;
        SQL;

        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':from_id', $this->model->id);
        $stmt->bindValue(':to_id', $id);
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    public function count(): int
    {
        $fromTable = $this->model::table();
        $toTable = $this->related::table();

        $sql = <<<SQL
        SELECT 
            count({$toTable}.id) as total
        FROM 
            {$fromTable}, {$toTable}, {$this->pivot_table}
        WHERE 
            {$toTable}.id = {$this->pivot_table}.{$this->to_foreign_key} AND
            {$fromTable}.id = {$this->pivot_table}.{$this->from_foreign_key} AND
            {$fromTable}.id = :id
        SQL;

        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $this->model->id);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows[0]['total'];
    }
}
