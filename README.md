
# SimyDB - Lightweight SQLite3 Database Abstraction Layer

![PHP Test](https://github.com/almhdy24/simydb/workflows/PHP%20Test/badge.svg)
![Packagist Version](https://img.shields.io/packagist/v/simy/db)
![PHP Version](https://img.shields.io/packagist/php-v/simy/db)
![License](https://img.shields.io/packagist/l/simy/db)

A modern, dependency-free PHP 8.1+ database abstraction layer for SQLite3 with fluent query builder interface, created by Elmahdi Abdallh.

## 📦 Installation

Install via Composer:

```bash
composer require simy/db
```

Or clone the repository:

```bash
# Using SSH
git clone git@github.com:almhdy24/simydb.git
cd simydb

# Using HTTPS
git clone https://github.com/almhdy24/simydb.git
cd simydb

composer install
```

🚀 Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use Simy\DB\Database;
use Simy\DB\DatabaseException;

try {
    // Create in-memory database
    $db = new Database('sqlite::memory:');
    
    // Create table using migration helper
    $migration = new MigrationHelper($db);
    $migration->createTable('users', [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'name' => 'TEXT NOT NULL',
        'email' => 'TEXT UNIQUE NOT NULL',
        'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
    ]);
    
    // Insert data
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

✨ Features

· 🚀 PHP 8.1+ with strict typing and namespaces
· 📦 Zero dependencies - completely self-contained
· 🔧 Fluent query builder inspired by Laravel Eloquent
· 🛡️ Prepared statements and parameter binding for security
· ⚡ SQLite3 optimized performance
· 🎯 Comprehensive error handling with DatabaseException
· 🔄 Full transaction support (begin, commit, rollback)
· 📊 Migration helper for schema management
· 🧪 Unit-test friendly with in-memory database support
· 📝 PSR-4 autoloading and clean code conventions

📚 Usage Examples

Basic CRUD Operations

```php
// Insert records
$db->table('users')->insert([
    'name' => 'Jane Smith',
    'email' => 'jane@example.com'
]);

// Select all records
$users = $db->table('users')->get();

// Select with conditions
$user = $db->table('users')
    ->where('email', 'john@example.com')
    ->first();

// Update records
$db->table('users')
    ->update(['name' => 'John Updated'])
    ->where('id', 1);

// Delete records
$db->table('users')
    ->delete()
    ->where('id', 5);
```

Complex Queries

```php
// Multiple where conditions
$users = $db->table('users')
    ->where('age', '>', 18)
    ->orWhere('status', 'active')
    ->orderBy('name', 'DESC')
    ->limit(10)
    ->get();

// Where IN clause
$users = $db->table('users')
    ->whereIn('id', [1, 2, 3, 5, 8])
    ->get();

// Count records
$count = $db->table('users')->where('active', 1)->count();

// Return as objects
$users = $db->table('users')->get(true);
foreach ($users as $user) {
    echo $user->name . "\n";
}
```

Transactions

```php
$db->beginTransaction();

try {
    $db->table('users')->insert(['name' => 'User 1', 'email' => 'user1@example.com']);
    $db->table('profiles')->insert(['user_id' => $db->lastInsertId(), 'bio' => 'Test bio']);
    
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
    $db->table('nonexistent_table')->get();
} catch (DatabaseException $e) {
    echo "Error: " . $e->getMessage();
    echo "SQL: " . $e->getSql();
    echo "Params: " . json_encode($e->getParams());
    
    // Log for debugging
    error_log("Database error: " . $e->getMessage());
}
```

Schema Migrations

```php
use Simy\DB\MigrationHelper;

$migration = new MigrationHelper($db);

// Create table
$migration->createTable('posts', [
    'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
    'title' => 'TEXT NOT NULL',
    'content' => 'TEXT',
    'user_id' => 'INTEGER',
    'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    'FOREIGN KEY (user_id) REFERENCES users(id)'
]);

// Add column
$migration->addColumn('users', 'age', 'INTEGER DEFAULT 0');

// Check if table or column exists
if ($migration->tableExists('users')) {
    echo "Users table exists!";
}

if ($migration->columnExists('users', 'email')) {
    echo "Email column exists!";
}
```

🏗️ API Reference

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

🧪 Testing

Run the test suite:

```bash
# Run basic tests
composer test

# Or run directly
php test.php
```

📊 Package Statistics

· Packagist: simy/db
· GitHub: almhdy24/simydb
· Downloads: https://img.shields.io/packagist/dt/simy/db
· License: MIT

🤝 Contributing

1. Fork the repository
2. Create a feature branch: git checkout -b feature/new-feature
3. Make your changes and add tests
4. Commit your changes: git commit -am 'Add new feature'
5. Push to the branch: git push origin feature/new-feature
6. Submit a pull request

📝 Changelog

v1.0.0 (2025-08-24)

· Initial release
· Database connection management
· Fluent query builder with CRUD operations
· Transaction support
· Migration helper for schema management
· Comprehensive error handling
· SQLite3 optimized implementation

📄 License

MIT License. See LICENSE file for details.

👨‍💻 Author

Elmahdi Abdallh (almhdy24)

· GitHub: @almhdy24

🙏 Acknowledgments

· Inspired by Laravel Eloquent and Medoo
· Built with PHP 8.1+ best practices
· Dependency-free design for maximum compatibility 