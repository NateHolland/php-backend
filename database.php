<?php

// This script is for initializing the database.
// It should be run from the command line: `php database.php`

try {
    // Create (or open) the database file
    $pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');

    // Set attributes for error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Database connection established.\n";

    // --- Create users table ---
    // The user ID from the request is a string (e.g., "user123"),
    // so we use it as the primary key.
    $sqlUsers = "
    CREATE TABLE IF NOT EXISTS users (
        id TEXT PRIMARY KEY NOT NULL
    );";
    $pdo->exec($sqlUsers);
    echo "Table 'users' created or already exists.\n";


    // --- Create shots table ---
    $sqlShots = "
    CREATE TABLE IF NOT EXISTS shots (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        arcQuality REAL NOT NULL,
        shortQuality REAL NOT NULL,
        longQuality REAL NOT NULL,
        brick INTEGER, -- Storing boolean as 0 or 1, nullable
        timestamp INTEGER NOT NULL,
        user_id TEXT NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );";
    $pdo->exec($sqlShots);
    echo "Table 'shots' created or already exists.\n";


    // --- Create an index for faster lookups ---
    $sqlIndex = "CREATE INDEX IF NOT EXISTS idx_user_id ON shots(user_id);";
    $pdo->exec($sqlIndex);
    echo "Index on 'shots.user_id' created or already exists.\n";


    echo "\nDatabase setup complete!\n";

} catch (PDOException $e) {
    // Handle connection errors
    die("Database setup failed: " . $e->getMessage() . "\n");
}
