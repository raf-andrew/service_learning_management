<?php

declare(strict_types=1);

namespace MCP\Models;

use MCP\Core\Database\ConnectionManager;
use PDO;
use PDOStatement;

abstract class Model
{
    protected string $table;
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected array $dates = ['created_at', 'updated_at'];
    protected bool $timestamps = true;
    protected string $primaryKey = 'id';
    protected ?string $connection = null;
    protected array $where = [];

    public function __construct(
        protected ConnectionManager $connectionManager
    ) {}

    public function create(array $data): int
    {
        $data = $this->filterFillable($data);
        $columns = array_keys($data);
        $values = array_values($data);

        if ($this->timestamps) {
            $columns[] = 'created_at';
            $columns[] = 'updated_at';
            $values[] = date('Y-m-d H:i:s');
            $values[] = date('Y-m-d H:i:s');
        }

        $placeholders = array_fill(0, count($columns), '?');
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($values);

        return (int) $this->getConnection()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $sql = sprintf(
            "SELECT * FROM %s WHERE %s = ?",
            $this->table,
            $this->primaryKey
        );

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->castAttributes($result) : null;
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->filterFillable($data);
        $columns = array_keys($data);
        $values = array_values($data);

        if ($this->timestamps) {
            $columns[] = 'updated_at';
            $values[] = date('Y-m-d H:i:s');
        }

        $set = implode(' = ?, ', $columns) . ' = ?';
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = ?",
            $this->table,
            $set,
            $this->primaryKey
        );

        $values[] = $id;
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        $sql = sprintf(
            "DELETE FROM %s WHERE %s = ?",
            $this->table,
            $this->primaryKey
        );

        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $this->where[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }

    public function get(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($this->where)) {
            $where = [];
            foreach ($this->where as $condition) {
                $where[] = "{$condition['column']} {$condition['operator']} ?";
                $params[] = $condition['value'];
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'castAttributes'], $results);
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];

        if (!empty($this->where)) {
            $where = [];
            foreach ($this->where as $condition) {
                $where[] = "{$condition['column']} {$condition['operator']} ?";
                $params[] = $condition['value'];
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    protected function getConnection(): PDO
    {
        return $this->connectionManager->getConnection($this->connection);
    }

    protected function filterFillable(array $data): array
    {
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function castAttributes(array $data): array
    {
        foreach ($this->casts as $key => $type) {
            if (isset($data[$key])) {
                $data[$key] = match ($type) {
                    'int', 'integer' => (int) $data[$key],
                    'float', 'double' => (float) $data[$key],
                    'string' => (string) $data[$key],
                    'bool', 'boolean' => (bool) $data[$key],
                    'array' => json_decode($data[$key], true),
                    'json' => json_decode($data[$key], true),
                    'date' => new \DateTime($data[$key]),
                    default => $data[$key]
                };
            }
        }

        return array_diff_key($data, array_flip($this->hidden));
    }
} 