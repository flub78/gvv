<?php

require_once 'application/tests/integration_bootstrap.php';

echo "Testing real database connection...\n";

try {
    $db = new RealDatabase($GLOBALS['test_db_config']);
    echo "✓ Database connection successful\n";
    
    // Test a simple query
    $result = $db->query("SELECT COUNT(*) as count FROM categorie");
    $rows = $result->result_array();
    echo "✓ Query successful: Found " . $rows[0]['count'] . " categories\n";
    
    // Test transaction handling
    $db->trans_start();
    echo "✓ Transaction started\n";
    
    $db->trans_rollback();
    echo "✓ Transaction rolled back\n";
    
    echo "\nAll database connection tests passed!\n";
    
} catch (Exception $e) {
    echo "✗ Database test failed: " . $e->getMessage() . "\n";
    exit(1);
}
