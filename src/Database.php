<?php

declare(strict_types=1);

namespace Simy\DB;

use SQLite3;
use Throwable;

class Database
{
    private SQLite3 $connection;
    private string $errorMode = 'throw';
    private ?string $lastQuery = null;
    private ?array $lastParams = null;

    public function __construct(string $filename, int $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE, string $encryptionKey = '')
    {
        try {
            $this->connection = new SQLite3($filename, $flags, $encryptionKey);
            $this->connection->enableExceptions(true);
            
            // Enable foreign keys
            $this->connection->exec('PRAGMA foreign_keys = ON');
        } catch (Throwable $e) {
            throw new DatabaseException(
                "Failed to connect to database: " . $e->getMessage(),
                (int)$e->getCode(),
                $e
            );
        }
    }

    public function setErrorMode(string $mode): self
    {
        $this->errorMode = $mode;
        return $this;
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    public function getConnection(): SQLite3
    {
        return $this->connection;
    }

    public function execute(string $sql, array $params = []): bool
    {
        $this->lastQuery = $sql;
        $this->lastParams = $params;

        try {
            $stmt = $this->connection->prepare($sql);
            
            if ($stmt === false) {
                throw new DatabaseException(
                    "Failed to prepare statement: " . $this->connection->lastErrorMsg(),
                    $this->connection->lastErrorCode(),
                    null,
                    $sql,
                    $params
                );
            }
            
            foreach ($params as $key => $value) {
                $bindName = is_int($key) ? $key + 1 : $key;
                $stmt->bindValue($bindName, $value, $this->getParamType($value));
            }
            
            $result = $stmt->execute();
            
            if ($result === false) {
                throw new DatabaseException(
                    "Failed to execute statement: " . $this->connection->lastErrorMsg(),
                    $this->connection->lastErrorCode(),
                    null,
                    $sql,
                    $params
                );
            }
            
            $result->finalize();
            $stmt->close();
            
            return true;
        } catch (Throwable $e) {
            if ($e instanceof DatabaseException) {
                throw $e;
            }
            
            throw new DatabaseException(
                "Database error: " . $e->getMessage(),
                (int)$e->getCode(),
                $e,
                $sql,
                $params
            );
        }
    }

    public function query(string $sql, array $params = [], bool $asObject = false)
    {
        $this->lastQuery = $sql;
        $this->lastParams = $params;

        try {
            $stmt = $this->connection->prepare($sql);
            
            if ($stmt === false) {
                throw new DatabaseException(
                    "Failed to prepare statement: " . $this->connection->lastErrorMsg(),
                    $this->connection->lastErrorCode(),
                    null,
                    $sql,
                    $params
                );
            }
            
            foreach ($params as $key => $value) {
                $bindName = is_int($key) ? $key + 1 : $key;
                $stmt->bindValue($bindName, $value, $this->getParamType($value));
            }
            
            $result = $stmt->execute();
            
            if ($result === false) {
                throw new DatabaseException(
                    "Failed to execute statement: " . $this->connection->lastErrorMsg(),
                    $this->connection->lastErrorCode(),
                    null,
                    $sql,
                    $params
                );
            }
            
            $data = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $data[] = $asObject ? (object)$row : $row;
            }
            
            $result->finalize();
            $stmt->close();
            
            return $data;
        } catch (Throwable $e) {
            if ($e instanceof DatabaseException) {
                throw $e;
            }
            
            throw new DatabaseException(
                "Database error: " . $e->getMessage(),
                (int)$e->getCode(),
                $e,
                $sql,
                $params
            );
        }
    }

    public function lastInsertId(): int
    {
        return $this->connection->lastInsertRowID();
    }

    public function beginTransaction(): bool
    {
        return $this->execute('BEGIN TRANSACTION');
    }

    public function commit(): bool
    {
        return $this->execute('COMMIT');
    }

    public function rollback(): bool
    {
        return $this->execute('ROLLBACK');
    }

    public function getLastQuery(): ?string
    {
        return $this->lastQuery;
    }

    public function getLastParams(): ?array
    {
        return $this->lastParams;
    }

    private function getParamType($value): int
    {
        if (is_int($value)) {
            return SQLITE3_INTEGER;
        }
        
        if (is_float($value)) {
            return SQLITE3_FLOAT;
        }
        
        if (is_null($value)) {
            return SQLITE3_NULL;
        }
        
        return SQLITE3_TEXT;
    }

    public function __destruct()
    {
        $this->connection->close();
    }
}