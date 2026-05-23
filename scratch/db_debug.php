<?php
require_once __DIR__ . '/../configNew.php';

header('Content-Type: text/plain');

echo "=== CHECKING TABLES ===\n";
$tables = [];
$res = $con->query("SHOW TABLES");
if ($res) {
    while ($row = $res->fetch_row()) {
        $tables[] = $row[0];
    }
    echo "Existing tables: " . implode(', ', $tables) . "\n\n";
}

echo "=== USER PUSH TOKENS TABLE ===\n";
if (in_array('user_push_tokens', $tables)) {
    $res = $con->query("SELECT * FROM user_push_tokens ORDER BY last_updated DESC LIMIT 20");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "ID: {$row['id']} | User ID: {$row['user_id']} | Role: {$row['user_role']} | Token: " . substr($row['push_token'], 0, 50) . "... | Updated: {$row['last_updated']}\n";
        }
    } else {
        echo "Error querying user_push_tokens: " . $con->error . "\n";
    }
} else {
    echo "Table 'user_push_tokens' does not exist.\n";
}

echo "\n=== STUDENT TABLE TOKENS ===\n";
$columns = [];
$res_cols = $con->query("SHOW COLUMNS FROM student");
if ($res_cols) {
    while ($col = $res_cols->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
    echo "Student table columns: " . implode(', ', $columns) . "\n";
}

$has_expo = in_array('expo_token', $columns);
$has_fcm = in_array('fcm_token', $columns);

if ($has_expo || $has_fcm) {
    $select_cols = [];
    if ($has_expo) $select_cols[] = 'expo_token';
    if ($has_fcm) $select_cols[] = 'fcm_token';
    $select_cols[] = 's_name';
    $select_cols[] = 's_id';
    
    $where_parts = [];
    if ($has_expo) $where_parts[] = "expo_token IS NOT NULL";
    if ($has_fcm) $where_parts[] = "fcm_token IS NOT NULL";
    $where_str = implode(' OR ', $where_parts);
    
    $query_str = "SELECT " . implode(', ', $select_cols) . " FROM student WHERE $where_str LIMIT 20";
    echo "Running query: $query_str\n";
    
    $res2 = $con->query($query_str);
    if ($res2) {
        while ($row = $res2->fetch_assoc()) {
            $expo_val = $has_expo ? substr($row['expo_token'], 0, 40) : 'N/A';
            $fcm_val = $has_fcm ? substr($row['fcm_token'], 0, 40) : 'N/A';
            echo "ID: {$row['s_id']} | Name: {$row['s_name']} | Expo: $expo_val | FCM: $fcm_val\n";
        }
    } else {
        echo "Error querying student table: " . $con->error . "\n";
    }
} else {
    echo "Neither expo_token nor fcm_token columns exist in 'student' table.\n";
}
?>
