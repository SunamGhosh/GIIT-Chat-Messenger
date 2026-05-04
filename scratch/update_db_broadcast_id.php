<?php
require_once 'configNew.php';
$sql = "ALTER TABLE messages ADD COLUMN broadcast_id VARCHAR(50) DEFAULT NULL";
if ($con->query($sql)) {
    echo "Column broadcast_id added successfully";
} else {
    echo "Error or column already exists: " . $con->error;
}
?>
