<?php
require_once 'configNew.php';
$sql = "ALTER TABLE messages ADD COLUMN is_read TINYINT DEFAULT 0, ADD COLUMN read_at TIMESTAMP NULL DEFAULT NULL";
if ($con->query($sql)) {
    echo "Columns is_read and read_at added successfully";
} else {
    echo "Error or columns already exist: " . $con->error;
}
?>
