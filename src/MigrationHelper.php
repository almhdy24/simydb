<?php

declare(strict_types=1);

namespace Simy\DB;

class MigrationHelper
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function createTable(string $table, array $columns): bool
    {
        $columnDefinitions = [];
        
        foreach ($columns as $name => $definition) {
            $columnDefinitions[] = "{$name} {$definition}";
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (" . implode(', ', $columnDefinitions) . ")";
        
        return $this->db->execute($sql);
    }

    public function dropTable(string $table): bool
    {
        $sql = "DROP TABLE IF EXISTS {$table}";
        return $this->db->execute($sql);
    }

    public function addColumn(string $table, string $column, string $definition): bool
    {
        $sql = "ALTER TABLE {$table} ADD COLUMN {$column} {$definition}";
        return $this->db->execute($sql);
    }

    public function dropColumn(string $table, string $column): bool
    {
        // SQLite doesn't support DROP COLUMN directly, so we need to recreate the table
        $this->db->beginTransaction();
        
        try {
            // Get table structure
            $tableInfo = $this->db->query("PRAGMA table_info({$table})");
            
            if (empty($tableInfo)) {
                throw new DatabaseException("Table {$table} does not exist");
            }
            
            // Create new table without the column
            $newTable = "{$table}_temp";
            $columns = [];
            
            foreach ($tableInfo as $col) {
                if ($col['name'] === $column) {
                    continue; // Skip the column we want to remove
                }
                
                $colDef = $col['type'];
                if ($col['notnull'] === 1) {
                    $colDef .= ' NOT NULL';
                }
                if ($col['dflt_value'] !== null) {
                    $colDef .= ' DEFAULT ' . $col['dflt_value'];
                }
                if ($col['pk'] === 1) {
                    $colDef .= ' PRIMARY KEY';
                }
                
                $columns[$col['name']] = $colDef;
            }
            
            // Create new table
            $this->createTable($newTable, $columns);
            
            // Copy data
            $columnNames = array_keys($columns);
            $this->db->execute(
                "INSERT INTO {$newTable} (" . implode(', ', $columnNames) . ") 
                 SELECT " . implode(', ', $columnNames) . " FROM {$table}"
            );
            
            // Drop old table and rename new one
            $this->dropTable($table);
            $this->db->execute("ALTER TABLE {$newTable} RENAME TO {$table}");
            
            return $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw new DatabaseException("Failed to drop column: " . $e->getMessage(), 0, $e);
        }
    }

    public function tableExists(string $table): bool
    {
        $result = $this->db->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name=:table",
            ['table' => $table]
        );
        
        return !empty($result);
    }

    public function columnExists(string $table, string $column): bool
    {
        $result = $this->db->query("PRAGMA table_info({$table})");
        
        foreach ($result as $col) {
            if ($col['name'] === $column) {
                return true;
            }
        }
        
        return false;
    }
}