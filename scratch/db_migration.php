<?php
require_once __DIR__ . '/../configNew.php';

header('Content-Type: text/plain');

echo "=== STARTING DATABASE MIGRATION ===\n";

// 1. Create user_push_tokens table
$createTableQuery = "CREATE TABLE IF NOT EXISTS user_push_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_role ENUM('student', 'faculty', 'admin') NOT NULL,
    push_token VARCHAR(255) NOT NULL UNIQUE,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (user_id, user_role)
)";

if ($con->query($createTableQuery)) {
    echo "✓ Table 'user_push_tokens' created or already exists.\n";
} else {
    echo "✗ Failed to create 'user_push_tokens': " . $con->error . "\n";
}

// 2. Add expo_token column to student table if missing
$columns_res = $con->query("SHOW COLUMNS FROM student");
$columns = [];
if ($columns_res) {
    while ($col = $columns_res->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
}

if (!in_array('expo_token', $columns)) {
    if ($con->query("ALTER TABLE student ADD COLUMN expo_token VARCHAR(255) DEFAULT NULL")) {
        echo "✓ Column 'expo_token' successfully added to 'student' table.\n";
    } else {
        echo "✗ Failed to add 'expo_token': " . $con->error . "\n";
    }
} else {
    echo "✓ Column 'expo_token' already exists in 'student' table.\n";
}

if (!in_array('fcm_token', $columns)) {
    if ($con->query("ALTER TABLE student ADD COLUMN fcm_token VARCHAR(255) DEFAULT NULL")) {
        echo "✓ Column 'fcm_token' successfully added to 'student' table.\n";
    } else {
        echo "✗ Failed to add 'fcm_token': " . $con->error . "\n";
    }
} else {
    echo "✓ Column 'fcm_token' already exists in 'student' table.\n";
}

echo "=== MIGRATION COMPLETE ===\n";
?>
