<?php

declare(strict_types=1);

namespace Simy\DB;

use Closure;

class QueryBuilder
{
    private Database $db;
    private string $table;
    private array $columns = ['*'];
    private array $wheres = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $bindings = [];

    public function __construct(Database $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    public function select(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function where(string $column, $operator, $value = null, string $boolean = 'AND'): self
    {
        // If only two arguments are provided, assume equality
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => count($this->wheres) === 0 ? 'WHERE' : $boolean
        ];

        $this->addBinding($value, 'where');

        return $this;
    }

    public function orWhere(string $column, $operator, $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND'): self
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => count($this->wheres) === 0 ? 'WHERE' : $boolean,
            'placeholders' => $placeholders
        ];

        foreach ($values as $value) {
            $this->addBinding($value, 'where');
        }

        return $this;
    }

    public function orWhereIn(string $column, array $values): self
    {
        return $this->whereIn($column, $values, 'OR');
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC'
        ];

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function get(bool $asObject = false): array
    {
        $sql = $this->buildSelectQuery();
        $bindings = $this->getBindings();
        
        return $this->db->query($sql, $bindings, $asObject);
    }

    public function first(bool $asObject = false): ?array
    {
        $this->limit(1);
        $sql = $this->buildSelectQuery();
        $bindings = $this->getBindings();
        
        $results = $this->db->query($sql, $bindings, $asObject);
        
        return $results[0] ?? null;
    }

    public function insert(array $data): bool
    {
        $columns = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})";
        
        return $this->db->execute($sql, array_values($data));
    }

    public function update(array $data): self
    {
        $setParts = [];
        $bindings = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = ?";
            $bindings[] = $value;
        }
        
        // Add WHERE clause bindings
        foreach ($this->getBindings() as $binding) {
            $bindings[] = $binding;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts);
        
        if (!empty($this->wheres)) {
            $sql .= " " . $this->buildWhereClause();
        }
        
        $this->db->execute($sql, $bindings);
        
        return $this; 
    }

    public function delete(): self
{
    $sql = "DELETE FROM {$this->table}";
    
    if (!empty($this->wheres)) {
        $sql .= " " . $this->buildWhereClause();
    }
    
    $this->db->execute($sql, $this->getBindings());
    
    return $this; // Return self for method chaining
}

    public function count(): int
    {
        $originalColumns = $this->columns;
        
        $this->columns = ['COUNT(*) as count'];
        $sql = $this->buildSelectQuery();
        $bindings = $this->getBindings();
        
        $result = $this->db->query($sql, $bindings);
        
        $this->columns = $originalColumns;
        
        return (int)($result[0]['count'] ?? 0);
    }

    private function buildSelectQuery(): string
    {
        $sql = "SELECT " . implode(', ', $this->columns) . " FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $sql .= " " . $this->buildWhereClause();
        }
        
        if (!empty($this->orderBy)) {
            $orderParts = [];
            foreach ($this->orderBy as $order) {
                $orderParts[] = "{$order['column']} {$order['direction']}";
            }
            $sql .= " ORDER BY " . implode(', ', $orderParts);
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT " . $this->limit;
        }
        
        if ($this->offset !== null) {
            $sql .= " OFFSET " . $this->offset;
        }
        
        return $sql;
    }

    private function buildWhereClause(): string
    {
        $whereClauses = [];
        
        foreach ($this->wheres as $where) {
            if ($where['type'] === 'basic') {
                $whereClauses[] = "{$where['boolean']} {$where['column']} {$where['operator']} ?";
            } elseif ($where['type'] === 'in') {
                $whereClauses[] = "{$where['boolean']} {$where['column']} IN ({$where['placeholders']})";
            }
        }
        
        return implode(' ', $whereClauses);
    }

    private function addBinding($value, string $type = 'where'): void
    {
        $this->bindings[] = $value;
    }

    private function getBindings(): array
    {
        return $this->bindings;
    }
}