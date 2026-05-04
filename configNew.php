<?php
// Bridge to the main portal configuration
$root_path = realpath(__DIR__ . '/../genius.mygiit.com');
$old_root = $_SERVER["DOCUMENT_ROOT"];
$_SERVER["DOCUMENT_ROOT"] = $root_path;

require_once $root_path . '/includes/configNew.php';

$_SERVER["DOCUMENT_ROOT"] = $old_root;

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($con, "utf8mb4");
?>
