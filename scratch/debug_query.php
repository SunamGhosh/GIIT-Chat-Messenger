<?php
require_once 'configNew.php';
// Simulate student 816
$student_id = 816;
$university = "1"; // Assuming it might be 1
$course = "16";
$session_id = "14";
$semester = "Sem3";

$sql = "SELECT m.* FROM messages m WHERE 1=1";
$sql .= " AND (m.groupId IS NULL OR m.groupId = 0)";
$sql .= " AND (m.receiver_id = $student_id OR m.receiver_id IS NULL OR m.receiver_id = 0)";

if ($university)
    $sql .= " AND (m.university = '" . mysqli_real_escape_string($con, $university) . "' OR m.university = '' OR m.receiver_id = $student_id)";
if ($course)
    $sql .= " AND (m.course = '" . mysqli_real_escape_string($con, $course) . "' OR m.course = '' OR m.receiver_id = $student_id)";
if ($session_id)
    $sql .= " AND (m.session = '" . mysqli_real_escape_string($con, $session_id) . "' OR m.session = '' OR m.receiver_id = $student_id)";
if ($semester)
    $sql .= " AND (m.semester = '" . mysqli_real_escape_string($con, $semester) . "' OR m.semester = '' OR m.receiver_id = $student_id)";

echo "Query: $sql\n\n";

$res = $con->query($sql);
if ($res) {
    echo "Results found: " . $res->num_rows . "\n";
    while($row = $res->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - Content: " . substr($row['content'], 0, 20) . "...\n";
    }
} else {
    echo "Error: " . $con->error;
}
?>
