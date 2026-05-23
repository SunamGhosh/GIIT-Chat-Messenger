<?php
require_once('Config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_type = $_POST['POST_TYPE'] ?? '';
    $post_data = json_decode($_POST['POST_DATA'] ?? '{}', true);
    $csrf = $_POST['POST_CSRF'] ?? '';

    // Simple CSRF check if needed
    // if (!$giit->validate_csrf($csrf)) { die(json_encode(['error' => 'Invalid CSRF token'])); }

    if ($post_type === 'FETCH_STUDENTS' || $post_type === 'EXPORT_EXCEL') {
        $session_id = $post_data['session_id'];
        $course_id = $post_data['course_id'];
        $document_id = $post_data['document_id'];
        $semester_id = $post_data['semester_id'] ?? null;

        // Base query to fetch students for the selected session and course
        // Note: You might need to adjust the table names and join logic based on your actual schema
        $query = "SELECT s.s_id, s.s_roll_no, s.s_name, 
                  (SELECT sd.doc_id FROM student_documents sd WHERE sd.s_id = s.s_id AND sd.doc_type_id = '$document_id' LIMIT 1) as document
                  FROM student_master s
                  WHERE s.s_session_id = '$session_id' 
                  AND s.s_course_id = '$course_id'";
        
        if ($semester_id && $semester_id > 0) {
            $query .= " AND s.s_semester_id = '$semester_id'";
        }

        $res = mysqli_query($con, $query);
        $students = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $students[] = $row;
        }

        if ($post_type === 'FETCH_STUDENTS') {
            echo json_encode(['error' => 0, 'data' => $students]);
            exit;
        }

        if ($post_type === 'EXPORT_EXCEL') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=Student_Document_Report_' . date('Y-m-d') . '.csv');
            $output = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($output, ['Sl', 'Roll No', 'Student Name', 'Document Status']);
            
            foreach ($students as $index => $student) {
                $status = $student['document'] ? 'Uploaded' : 'Not Uploaded';
                fputcsv($output, [
                    $index + 1,
                    $student['s_roll_no'],
                    $student['s_name'],
                    $status
                ]);
            }
            fclose($output);
            exit;
        }
    }
}
?>
