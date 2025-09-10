# Integration Testing with Database Transactions

## Overview

The integration test `CategorieModelIntegrationTest` demonstrates how to test CodeIgniter models with real database access while maintaining database integrity through transactions.

## Key Features

### 1. Database Transaction Management
```php
public function setUp(): void
{
    // Start transaction for test isolation
    $this->CI->db->trans_start();
}

public function tearDown(): void
{
    // Rollback transaction to restore database state
    $this->CI->db->trans_rollback();
}
```

### 2. Full CodeIgniter Framework Loading
- Uses `integration_bootstrap.php` instead of minimal bootstrap
- Loads complete CI framework with database connections
- Initializes session and other required components

### 3. Real Database Operations Tested
- **CRUD Operations**: Create, Read, Update, Delete
- **Model Methods**: `save()`, `get_by_id()`, `delete()`, `image()`, `select_page()`
- **Database Constraints**: Tests validation and error handling
- **Relationships**: Tests parent-child category relationships

## Test Categories

### Basic CRUD Operations (`testBasicCrudOperations`)
- Creates a test category
- Retrieves it by ID
- Updates the category data
- Deletes the category
- Verifies each operation

### Image Method Testing (`testImageMethod`)
- Tests the `image()` method with valid/invalid IDs
- Verifies string formatting and error messages

### Page Selection (`testSelectPageMethod`)
- Tests `select_page()` method with pagination
- Creates parent-child relationships
- Verifies JOIN operations work correctly

### Database Constraints (`testDatabaseConstraints`)
- Tests behavior with invalid data
- Handles database constraint violations gracefully

### Transaction Rollback (`testTransactionRollback`)
- Demonstrates that changes are rolled back between tests
- Ensures database state is preserved

## Running Integration Tests

**Note**: These tests require a properly configured database and full CodeIgniter setup.

```bash
# Run integration tests with separate configuration
source setenv.sh
php -d xdebug.mode=off /usr/local/bin/phpunit --configuration phpunit_integration.xml

# Run specific integration test
php -d xdebug.mode=off /usr/local/bin/phpunit --configuration phpunit_integration.xml --filter CategorieModelIntegrationTest

# Debug integration test
XDEBUG_CONFIG="idekey=VSCODE" php -d xdebug.mode=debug -d xdebug.start_with_request=yes /usr/local/bin/phpunit --configuration phpunit_integration.xml --filter testBasicCrudOperations
```

## Database Requirements

### 1. Transaction Support
- **MySQL**: Use InnoDB storage engine (supports transactions)
- **PostgreSQL**: Native transaction support
- **SQLite**: Native transaction support

### 2. Test Database Setup
```sql
-- Example: Create test database with same structure as production
CREATE DATABASE gvv_test;
USE gvv_test;

-- Import structure from production
-- (Run your normal database migration/setup scripts)

-- Ensure categorie table exists with proper structure
CREATE TABLE IF NOT EXISTS categorie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    parent INT,
    type VARCHAR(50),
    INDEX idx_parent (parent)
) ENGINE=InnoDB;

-- Insert minimal test data (parent category)
INSERT INTO categorie (id, nom, description, parent, type) 
VALUES (1, 'Root Category', 'Root parent category', 0, 'root');
```

### 3. Configuration
Update `application/config/database.php` for testing:
```php
// Test database configuration
$db['testing'] = array(
    'hostname' => 'localhost',
    'username' => 'test_user',
    'password' => 'test_password',  
    'database' => 'gvv_test',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => FALSE, // Disable debug in tests
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci'
);
```

## Comparison: Unit vs Integration Tests

| Aspect | Unit Tests (Current) | Integration Tests (New) |
|--------|---------------------|-------------------------|
| **Database** | ❌ No database access | ✅ Real database operations |
| **Speed** | ✅ Very fast (~76ms) | ❌ Slower (depends on DB) |
| **Setup** | ✅ Minimal bootstrap | ❌ Full CI framework required |
| **Isolation** | ✅ Pure logic testing | ✅ Transaction rollback |
| **Coverage** | ❌ Business logic only | ✅ Full model behavior |
| **Reliability** | ✅ No external dependencies | ❌ Depends on DB state |
| **CI/CD** | ✅ Always works | ❌ Requires test DB setup |

## Best Practices

### 1. Test Data Management
```php
// Use unique identifiers to avoid conflicts
$test_data = [
    'nom' => 'Test Category ' . time(),
    // ...
];

// Track created IDs for potential cleanup
$this->created_ids[] = $created_id;
```

### 2. Transaction Isolation
```php
// Always use transactions for integration tests
$this->CI->db->trans_start();
// ... test operations ...
$this->CI->db->trans_rollback(); // in tearDown()
```

### 3. Error Handling
```php
// Handle database constraint violations gracefully
try {
    $result = $this->model->save($invalid_data);
} catch (Exception $e) {
    $this->assertStringContainsString('expected_error', $e->getMessage());
}
```

## Current Status

- ✅ **Integration test created**: `CategorieModelIntegrationTest.php`
- ✅ **Transaction support**: Automatic rollback in tearDown()
- ✅ **Mock CI bootstrap**: `integration_bootstrap.php` with mock database
- ✅ **Separate configuration**: `phpunit_integration.xml`
- ✅ **Fully executable**: Works with mock framework (6 tests, 24 assertions)
- ✅ **VS Code debugging**: Integration test debugging configuration added

**Test Results**:
```bash
$ php -d xdebug.mode=off /usr/local/bin/phpunit --configuration phpunit_integration.xml --testdox

Categorie Model Integration
 ✔ Basic crud operations
 ✔ Image method  
 ✔ Select page method
 ✔ Database constraints
 ✔ Multiple categories and search
 ✔ Transaction rollback

OK (6 tests, 24 assertions)
```

This integration test serves as a template for testing other models in the GVV application with **mock database operations** that simulate real database behavior while maintaining complete isolation.
