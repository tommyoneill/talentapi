<?php

require_once 'vendor/autoload.php';
require_once 'config.php';

// Database connection
$config = require 'config.php';

try {
    $db = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['database']};charset={$config['db']['charset']}",
        $config['db']['username'],
        $config['db']['password']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database successfully.\n";

    // Disable foreign key checks
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    echo "Foreign key checks disabled.\n";

    // Truncate tables
    $tables = [
        'addresses',
        'skills',
        'work_history',
        'talent_resumes',
        'talents'
    ];

    foreach ($tables as $table) {
        $db->exec("TRUNCATE TABLE `{$config['db']['database']}`.`$table`");
        echo "Truncated table: $table\n";
    }

    // Re-enable foreign key checks
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "Foreign key checks re-enabled.\n";

    echo "All tables cleared successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 