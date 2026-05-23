<?php
require_once __DIR__ . '/../configNew.php';

$con->select_db('mygiit');

$res = $con->query("DESCRIBE messages");
if ($res) {
    echo "Columns of messages table in 'mygiit':\n";
    while ($row = $res->fetch_assoc()) {
        echo " - " . $row['Field'] . " (" . $row['Type'] . ") Null:" . $row['Null'] . "\n";
    }
} else {
    echo "Error describing messages table: " . $con->error . "\n";
}
