# SimyDB - Lightweight SQLite3 Database Abstraction Layer

A modern, dependency-free PHP 8.1+ database abstraction layer for SQLite3 with fluent query builder interface.

## Features

- 🚀 PHP 8.1+ with strict typing and namespaces
- 📦 Zero dependencies - completely self-contained
- 🔧 Fluent query builder inspired by Laravel Eloquent
- 🛡️ Prepared statements and parameter binding
- ⚡ SQLite3 optimized performance
- 🎯 Comprehensive error handling with DatabaseException
- 🔄 Transaction support
- 📊 Migration helper for schema management
- 🧪 Unit-test friendly with in-memory database support

## Installation

```bash
composer require simy/db
```

Or clone this repository:

```bash
git clone <your-repo-url>
cd simydb
composer install
```

Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use Simy\DB\Database;
use Simy\DB\DatabaseException;

try {
    // Create in-memory database
    $db = new Database('sqlite::memory:');
    
    // Create table
    $db->table('users')->insert([
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);
    
    // Query data
    $user = $db->table('users')->where('id', 1)->first();
    print_r($user);
    
} catch (DatabaseException $e) {
    echo "Error: " . $e->getMessage();
}
```

Usage Examples

Basic CRUD Operations

```php
// Insert
$db->table('users')->insert([
    'name' => 'Jane Smith',
    'email' => 'jane@example.com'
]);

// Select
$users = $db->table('users')->get();
$user = $db->table('users')->where('id', 1)->first();

// Update
$db->table('users')->update(['name' => 'John Updated'])->where('id', 1);

// Delete
$db->table('users')->delete()->where('id', 5);
```

Complex Queries

```php
// Where clauses
$users = $db->table('users')
    ->where('age', '>', 18)
    ->orWhere('status', 'active')
    ->orderBy('name', 'DESC')
    ->limit(10)
    ->get();

// Where IN
$users = $db->table('users')
    ->whereIn('id', [1, 2, 3, 5, 8])
    ->get();

// Count records
$count = $db->table('users')->where('active', 1)->count();
```

Transactions

```php
$db->beginTransaction();

try {
    $db->table('users')->insert(['name' => 'User 1']);
    $db->table('profiles')->insert(['user_id' => $db->lastInsertId()]);
    
    $db->commit();
    echo "Transaction successful!";
} catch (DatabaseException $e) {
    $db->rollback();
    echo "Transaction failed: " . $e->getMessage();
}
```

Error Handling

```php
try {
    $db->table('nonexistent')->get();
} catch (DatabaseException $e) {
    echo "Error: " . $e->getMessage();
    echo "SQL: " . $e->getSql();
    echo "Params: " . json_encode($e->getParams());
}
```

Migrations

```php
use Simy\DB\MigrationHelper;

$migration = new MigrationHelper($db);

// Create table
$migration->createTable('users', [
    'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
    'name' => 'TEXT NOT NULL',
    'email' => 'TEXT UNIQUE NOT NULL',
    'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
]);

// Add column
$migration->addColumn('users', 'age', 'INTEGER DEFAULT 0');

// Check if table or column exists
if ($migration->tableExists('users')) {
    echo "Users table exists!";
}
```

API Reference

Database Class

· __construct(string $filename) - Create database connection
· table(string $table): QueryBuilder - Get query builder for table
· execute(string $sql, array $params = []): bool - Execute raw SQL
· query(string $sql, array $params = [], bool $asObject = false): array - Query raw SQL
· beginTransaction(): bool - Start transaction
· commit(): bool - Commit transaction
· rollback(): bool - Rollback transaction
· lastInsertId(): int - Get last insert ID

QueryBuilder Class

· select(array $columns): self - Set columns to select
· where(string $column, $operator, $value = null): self - Add where clause
· orWhere(string $column, $operator, $value = null): self - Add OR where clause
· whereIn(string $column, array $values): self - Add WHERE IN clause
· orderBy(string $column, string $direction = 'ASC'): self - Add ordering
· limit(int $limit): self - Set limit
· offset(int $offset): self - Set offset
· get(bool $asObject = false): array - Get all results
· first(bool $asObject = false): ?array - Get first result
· insert(array $data): bool - Insert record
· update(array $data): self - Update records
· delete(): self - Delete records
· count(): int - Count records

Testing

Run the test suite:

```bash
php test.php
```

License

MIT License

Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request 
