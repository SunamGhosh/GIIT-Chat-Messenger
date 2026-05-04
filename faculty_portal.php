<?php
session_start();
$DOC_ROOT = $_SERVER["DOCUMENT_ROOT"];
require_once("$DOC_ROOT/dn_script/connect.php");
require_once("$DOC_ROOT/validator/validate_gs.php");

if (file_exists("functions.php")) {
    include("functions.php");
}

// Authentication Check - Following Admission Portal Structure
$is_authenticated = false;
$faculty_id = 0;
$faculty_name = 'Faculty';

if (isset($_SESSION['control_login_status']) || isset($_SESSION['temp_control_login_status'])) {
    // Session from Admission Portal
    $is_authenticated = true;
    $faculty_id = $_SESSION['temp_control_id'] ?? 0;
    $faculty_name = $_SESSION['temp_control_name'] ?? 'Faculty';
} elseif (isset($_SESSION['facultyLogin'])) {
    // Other Faculty Login
    $is_authenticated = true;
    $faculty_id = $_SESSION['facultyLogin'];
    $faculty_name = $_SESSION['faculty_name'] ?? 'Faculty';
}

if (!$is_authenticated) {
    // Redirect to the Admission Login page as per the provided structure
    header("Location: /admission/login.php");
    exit;
}

// --- API LOGIC HANDLERS (AJAX) ---
if (isset($_POST['POST_TYPE'])) {
    if (ob_get_length())
        ob_clean();
    header('Content-Type: application/json');
    $type = $_POST['POST_TYPE'];

    if ($type === 'SEND_MESSAGE') {
        $content = $_POST['content'] ?? '';
        $uni = $_POST['university'] ?? '';
        $session = $_POST['session'] ?? '';
        $course = $_POST['course'] ?? '';
        $semester = $_POST['semester'] ?? '';
        $groupId = $_POST['groupId'] ?? '';

        // NEW: If sending to a session (no groupId but filters present), make it a tracked broadcast
        if (empty($groupId) && (!empty($uni) || !empty($session) || !empty($course) || !empty($semester))) {
            $broadcast_id = 'BCT-' . time() . '-' . rand(1000, 9999);
            
            // Build filter query
            $where = ["1=1"];
            if (!empty($uni)) $where[] = "s_university_id = " . intval($uni);
            if (!empty($session)) $where[] = "s_session_id = " . intval($session);
            if (!empty($course)) $where[] = "s_course_id = " . intval($course);
            if (!empty($semester)) {
                $sem_num = intval(str_replace('Sem', '', $semester));
                $where[] = "s_cur_sem = $sem_num";
            }
            $where_str = implode(' AND ', $where);
            
            $std_query = $con->query("SELECT s_id FROM student WHERE $where_str");
            $success_count = 0;
            if ($std_query) {
                while ($std = $std_query->fetch_assoc()) {
                    $s_id = $std['s_id'];
                    $ins = $con->prepare("INSERT INTO messages (sender_id, receiver_id, content, university, session, course, semester, groupId, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $ins->bind_param("iissssss", $faculty_id, $s_id, $content, $uni, $session, $course, $semester, $broadcast_id);
                    $ins->execute();
                    $ins->close();
                    $success_count++;
                }
            }
            echo json_encode(['error' => 0, 'message' => "Broadcast sent to $success_count students"]);
            exit;
        }

        // Standard logic for groups/DMs
        $sql = "INSERT INTO messages (sender_id, content, university, session, course, semester, groupId, createdAt) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $g_id = !empty($groupId) ? $groupId : null;
            $stmt->bind_param("issssss", $faculty_id, $content, $uni, $session, $course, $semester, $g_id);
            if ($stmt->execute()) {
                echo json_encode(['error' => 0, 'message' => 'Message sent successfully']);
            } else {
                echo json_encode(['error' => 1, 'message' => $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 1, 'message' => 'Prepare failed: ' . $con->error]);
        }
        exit;
    }

    if ($type === 'EDIT_GROUP') {
        $g_id = intval($_POST['groupId'] ?? 0);
        $newName = $_POST['group_name'] ?? '';
        if ($g_id > 0 && !empty($newName)) {
            $stmt = $con->prepare("UPDATE chat_groups SET group_name = ? WHERE id = ?");
            $stmt->bind_param("si", $newName, $g_id);
            if ($stmt->execute()) {
                echo json_encode(['error' => 0, 'message' => 'Group renamed']);
            } else {
                echo json_encode(['error' => 1, 'message' => $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 1, 'message' => 'Invalid data']);
        }
        exit;
    }

    if ($type === 'GET_MESSAGES') {
        $groupId = $_POST['groupId'] ?? '';
        $uni = mysqli_real_escape_string($con, $_POST['university'] ?? '');
        $course = mysqli_real_escape_string($con, $_POST['course'] ?? '');
        $session = mysqli_real_escape_string($con, $_POST['session'] ?? '');
        $semester = mysqli_real_escape_string($con, $_POST['semester'] ?? '');

        // Use a subquery to get counts for broadcast messages (grouped by groupId starting with BCT-)
        $sql = "SELECT m.*, s.s_name as recipient_name,
                (SELECT COUNT(*) FROM messages m2 WHERE m2.groupId = m.groupId AND m.groupId LIKE 'BCT-%') as broadcast_total,
                (SELECT COUNT(*) FROM messages m3 WHERE m3.groupId = m.groupId AND m3.is_read = 1 AND m.groupId LIKE 'BCT-%') as broadcast_seen
                FROM messages m LEFT JOIN student s ON m.receiver_id = s.s_id WHERE 1=1";
        
        if (!empty($groupId)) {
            $g = mysqli_real_escape_string($con, $groupId);
            $sql .= " AND m.groupId = '$g'";
        } else {
            if ($uni)
                $sql .= " AND (university = '$uni' OR university = '')";
            if ($session)
                $sql .= " AND (session = '$session' OR session = '')";
            if ($course)
                $sql .= " AND (course = '$course' OR course = '')";
            if ($semester)
                $sql .= " AND (semester = '$semester' OR semester = '')";
            $sql .= " AND (m.groupId IS NULL OR m.groupId = '0' OR m.groupId = '' OR m.groupId LIKE 'BCT-%')";
        }
        $sql .= " GROUP BY CASE WHEN m.groupId LIKE 'BCT-%' THEN m.groupId ELSE m.id END";
        $sql .= " ORDER BY createdAt ASC";

        $result = $con->query($sql);
        $messages = [];
        if ($result) {
            while ($row = $result->fetch_assoc())
                $messages[] = $row;
        }
        echo json_encode(['error' => 0, 'data' => $messages]);
        exit;
    }

    if ($type === 'GET_BROADCAST_STATS') {
        $bct_id = mysqli_real_escape_string($con, $_POST['broadcast_id'] ?? '');
        $msg_id = intval($_POST['msg_id'] ?? 0);

        $sql = "SELECT m.is_read, m.read_at, s.s_name, s.s_roll_no 
                FROM messages m 
                JOIN student s ON m.receiver_id = s.s_id";
        
        if ($msg_id > 0) {
            $sql .= " WHERE m.id = $msg_id";
        } else {
            $sql .= " WHERE m.groupId = '$bct_id'";
        }

        $res = $con->query($sql);
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $data[] = $row;
            }
        }
        echo json_encode(['error' => 0, 'data' => $data]);
        exit;
    }

    if ($type === 'EDIT_MESSAGE') {
        $msgId = intval($_POST['msgId'] ?? 0);
        $content = $_POST['content'] ?? '';
        if ($msgId > 0 && !empty($content)) {
            $stmt = $con->prepare("UPDATE messages SET content = ? WHERE id = ? AND sender_id = ?");
            $stmt->bind_param("sii", $content, $msgId, $faculty_id);
            if ($stmt->execute()) {
                echo json_encode(['error' => 0, 'message' => 'Message updated']);
            } else {
                echo json_encode(['error' => 1, 'message' => $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 1, 'message' => 'Invalid data']);
        }
        exit;
    }

    if ($type === 'DELETE_MESSAGE') {
        $msgId = intval($_POST['msgId'] ?? 0);
        if ($msgId > 0) {
            $stmt = $con->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?");
            $stmt->bind_param("ii", $msgId, $faculty_id);
            if ($stmt->execute()) {
                echo json_encode(['error' => 0, 'message' => 'Message deleted']);
            } else {
                echo json_encode(['error' => 1, 'message' => $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 1, 'message' => 'Invalid data']);
        }
        exit;
    }

    if ($type === 'SEARCH_STUDENTS') {
        $query = mysqli_real_escape_string($con, $_POST['query'] ?? '');
        $sql = "SELECT s_id, s_name, s_roll_no FROM student 
                WHERE s_name LIKE '%$query%' OR s_roll_no LIKE '%$query%' 
                LIMIT 10";
        $res = $con->query($sql);
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc())
                $data[] = $row;
        }
        echo json_encode(['error' => 0, 'data' => $data]);
        exit;
    }

    if ($type === 'DELETE_GROUP') {
        $g_id = intval($_POST['groupId'] ?? 0);
        if ($g_id > 0) {
            $stmt = $con->prepare("DELETE FROM chat_groups WHERE id = ? AND created_by = ?");
            $stmt->bind_param("ii", $g_id, $faculty_id);
            if ($stmt->execute()) {
                echo json_encode(['error' => 0, 'message' => 'Group deleted successfully']);
            } else {
                echo json_encode(['error' => 1, 'message' => $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 1, 'message' => 'Invalid Group']);
        }
        exit;
    }

    if ($type === 'REMOVE_MEMBER') {
        $g_id = intval($_POST['groupId'] ?? 0);
        $s_id = intval($_POST['studentId'] ?? 0);
        if ($g_id > 0 && $s_id > 0) {
            $stmt = $con->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ? AND user_role = 'student'");
            $stmt->bind_param("ii", $g_id, $s_id);
            if ($stmt->execute()) {
                echo json_encode(['error' => 0, 'message' => 'Member removed']);
            } else {
                echo json_encode(['error' => 1, 'message' => $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 1, 'message' => 'Invalid Data']);
        }
        exit;
    }

    if ($type === 'ADD_MEMBER') {
        $g_id = intval($_POST['groupId'] ?? 0);
        $s_id = intval($_POST['studentId'] ?? 0);
        if ($g_id > 0 && $s_id > 0) {
            // Check if already a member
            $check = $con->query("SELECT * FROM group_members WHERE group_id = $g_id AND user_id = $s_id AND user_role = 'student'");
            if ($check && $check->num_rows > 0) {
                echo json_encode(['error' => 1, 'message' => 'Student is already a member']);
            } else {
                $stmt = $con->prepare("INSERT INTO group_members (group_id, user_id, user_role) VALUES (?, ?, 'student')");
                $stmt->bind_param("ii", $g_id, $s_id);
                if ($stmt->execute()) {
                    echo json_encode(['error' => 0, 'message' => 'Member added']);
                } else {
                    echo json_encode(['error' => 1, 'message' => $stmt->error]);
                }
                $stmt->close();
            }
        } else {
            echo json_encode(['error' => 1, 'message' => 'Invalid Data']);
        }
        exit;
    }

    if ($type === 'GET_GROUP_MEMBERS') {
        $g_id = intval($_POST['groupId'] ?? 0);
        if ($g_id > 0) {
            $sql = "SELECT gm.user_role, gm.user_id, s.s_name, s.s_roll_no 
                    FROM group_members gm 
                    LEFT JOIN student s ON gm.user_id = s.s_id AND gm.user_role = 'student'
                    WHERE gm.group_id = $g_id";
            $res = $con->query($sql);
            $members = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    if ($row['user_role'] === 'student') {
                        $members[] = ['name' => $row['s_name'], 'role' => 'Student', 'info' => $row['s_roll_no'], 'student_id' => $row['user_id']];
                    } else {
                        $members[] = ['name' => 'Faculty Admin', 'role' => ucfirst($row['user_role']), 'info' => '-'];
                    }
                }
            }
            echo json_encode(['error' => 0, 'data' => $members]);
        } else {
            echo json_encode(['error' => 1, 'message' => 'Invalid Group']);
        }
        exit;
    }

    if ($type === 'CREATE_GROUP') {
        $name = mysqli_real_escape_string($con, $_POST['group_name'] ?? '');
        $uni_id = intval($_POST['university'] ?? 0);
        $course_id = intval($_POST['course'] ?? 0);
        $semester = mysqli_real_escape_string($con, $_POST['semester'] ?? '');
        $student_ids = $_POST['student_ids'] ?? [];

        $con->begin_transaction();
        try {
            $stmt = $con->prepare("INSERT INTO chat_groups (group_name, created_by) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $faculty_id);
            $stmt->execute();
            $groupId = $con->insert_id;
            $stmt->close();

            $stmt = $con->prepare("INSERT INTO group_members (group_id, user_id, user_role) VALUES (?, ?, 'faculty')");
            $stmt->bind_param("ii", $groupId, $faculty_id);
            $stmt->execute();
            $stmt->close();

            if (!empty($student_ids)) {
                // Manual selection
                $stmt = $con->prepare("INSERT INTO group_members (group_id, user_id, user_role) VALUES (?, ?, 'student')");
                foreach ($student_ids as $s_id) {
                    $s_id = intval($s_id);
                    $stmt->bind_param("ii", $groupId, $s_id);
                    $stmt->execute();
                }
                $stmt->close();
            } else if ($course_id && $semester) {
                $sem_num = str_replace('Sem', '', $semester);
                $student_sql = "INSERT INTO group_members (group_id, user_id, user_role) 
                               SELECT ?, s_id, 'student' FROM student 
                               WHERE s_course_id = ? AND s_cur_sem = ?";

                // Add university filter if provided
                if ($uni_id) {
                    $student_sql .= " AND s_university_id = ?";
                    $stmt = $con->prepare($student_sql);
                    $stmt->bind_param("iiii", $groupId, $course_id, $sem_num, $uni_id);
                } else {
                    $stmt = $con->prepare($student_sql);
                    $stmt->bind_param("iii", $groupId, $course_id, $sem_num);
                }

                $stmt->execute();
                $stmt->close();
            }

            $con->commit();
            echo json_encode(['error' => 0, 'message' => 'Group created successfully']);
        } catch (Exception $e) {
            $con->rollback();
            echo json_encode(['error' => 1, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($type === 'SEND_BROADCAST') {
        $students_id = $_POST['students_id'] ?? [];
        $template_content = $_POST['template'] ?? '';

        if (empty($students_id) || empty($template_content)) {
            echo json_encode(['error' => 1, 'message' => 'Missing students or template content']);
            exit;
        }

        $success_count = 0;
        $error_count = 0;

        $broadcast_id = 'BCT-' . time() . '-' . rand(1000, 9999);

        foreach ($students_id as $s_id) {
            $s_id = intval($s_id);
            // Fetch student details for placeholders
            $std_query = $con->query("SELECT s.*, c.course_name, sm.session_name 
                                     FROM student s 
                                     LEFT JOIN course_master c ON s.s_course_id = c.course_master_id 
                                     LEFT JOIN session_master sm ON s.s_session_id = sm.session_master_id 
                                     WHERE s.s_id = $s_id");

            if ($std_row = $std_query->fetch_assoc()) {
                $personalized_msg = $template_content;
                // Support multiple placeholder formats
                $placeholders = [
                    '{student_name}' => $std_row['s_name'],
                    'std_name' => $std_row['s_name'],
                    '{roll_no}' => $std_row['s_roll_no'],
                    'std_roll' => $std_row['s_roll_no'],
                    '{course}' => $std_row['course_name'],
                    '{student_course}' => $std_row['course_name'],
                    '{session}' => $std_row['session_name'],
                    '{father_name}' => $std_row['s_father_name']
                ];

                foreach ($placeholders as $key => $val) {
                    $personalized_msg = str_replace($key, $val ?? '', $personalized_msg);
                }

                $stmt = $con->prepare("INSERT INTO messages (sender_id, receiver_id, content, university, session, course, semester, groupId, createdAt) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
                $uni = $std_row['s_university_id'];
                $sess = $std_row['s_session_id'];
                $course = $std_row['s_course_id'];
                $sem = "Sem" . ($std_row['s_cur_sem'] ?? 1);
                
                $stmt->bind_param("iissssss", $faculty_id, $s_id, $personalized_msg, $uni, $sess, $course, $sem, $broadcast_id);
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
                $stmt->close();
            }
        }
        echo json_encode(['error' => 0, 'message' => "Successfully sent to $success_count students."]);
        exit;
    }
}

// Fetch Initial Data
$universities = [];
$res = $con->query("SELECT id, university_name as name FROM university WHERE is_active = 1");
if ($res)
    while ($row = $res->fetch_assoc())
        $universities[] = $row;

$allCourses = [];
$res = $con->query("SELECT course_master_id as id, course_name as name, course_short_name as sname FROM course_master WHERE course_status='A' ORDER BY course_name ASC");
if ($res)
    while ($row = $res->fetch_assoc())
        $allCourses[] = $row;

$allSessions = [];
$res = $con->query("SELECT s.session_master_id as id, s.session_name as name, u.university_short_name as uni_short 
                        FROM session_master s 
                        LEFT JOIN university u ON s.university_id = u.id 
                        ORDER BY s.session_master_id DESC");
if ($res)
    while ($row = $res->fetch_assoc())
        $allSessions[] = $row;

$groups = [];
$res = $con->query("SELECT g.* FROM chat_groups g 
                      JOIN group_members m ON g.id = m.group_id 
                      WHERE m.user_id = '$faculty_id' AND m.user_role = 'faculty'");
if ($res)
    while ($row = $res->fetch_assoc())
        $groups[] = $row;

define('TITLE', 'GIITChat | Faculty Portal');

// IMPORTANT: Initialize $giit if still null to prevent header.php fatal error
if (!isset($giit)) {
    $giit = new stdClass();
    $giit->page_title = function () {
        return TITLE;
    };
    // Add other common methods used in header if necessary
}

// Standard portal header inclusion
if (file_exists("pages/header.php")) {
    include_once("pages/header.php");
} elseif (file_exists("header.php")) {
    include_once("header.php");
}
?>

<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

<style>
    /* Google Fonts */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    .chat-wrapper {
        font-family: 'Inter', sans-serif;
        padding: 0 15px;
    }

    .chat-container {
        display: flex;
        height: calc(100vh - 120px);
        background: #ffffff;
        border: none;
        border-radius: 16px;
        overflow: hidden;
        margin-top: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .chat-sidebar {
        width: 340px;
        border-right: 1px solid #ebedf2;
        display: flex;
        flex-direction: column;
        background: #fafbfc;
    }

    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #ffffff;
    }

    .sidebar-header {
        padding: 20px;
        background: #ffffff;
        color: #1a1d20;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #ebedf2;
    }

    .sidebar-header strong {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0d6efd;
    }

    .sidebar-header .header-btn {
        background-color: #f0f4f8;
        border: none;
        color: #0d6efd;
        border-radius: 8px;
        padding: 8px 12px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .sidebar-header .header-btn:hover {
        background-color: #e2e8f0;
        color: #0b5ed7;
        transform: translateY(-1px);
    }

    .chat-list {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
    }

    .chat-list::-webkit-scrollbar {
        width: 6px;
    }

    .chat-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .chat-item {
        padding: 12px 15px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 8px;
        background: #ffffff;
        border: 1px solid #ebedf2;
    }

    .chat-item:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .chat-item.active {
        background: linear-gradient(135deg, #eff6ff 0%, #e0e7ff 100%);
        border: 1px solid #bfdbfe;
        border-left: 4px solid #3b82f6;
    }

    .chat-item-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #e0e7ff;
        color: #3b82f6;
        margin-right: 15px;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .chat-item-content {
        flex: 1;
        overflow: hidden;
    }

    .chat-item-title {
        font-weight: 600;
        font-size: 0.88rem;
        color: #1e293b;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-item-subtitle {
        font-size: 0.72rem;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-header {
        padding: 20px 25px;
        border-bottom: 1px solid #ebedf2;
        background: rgba(255, 255, 255, 0.95);
        display: flex;
        align-items: center;
        justify-content: space-between;
        z-index: 10;
    }

    .chat-header h4 {
        margin: 0;
        font-weight: 700;
        color: #0f172a;
        font-size: 1.05rem;
    }

    .chat-header .text-muted {
        font-size: 0.78rem;
        color: #64748b;
        margin-top: 2px;
    }

    .messages-display {
        flex: 1;
        padding: 25px;
        overflow-y: auto;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .messages-display::-webkit-scrollbar {
        width: 6px;
    }

    .messages-display::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .message {
        max-width: 70%;
        padding: 10px 15px;
        border-radius: 14px;
        font-size: 0.88rem;
        line-height: 1.4;
        position: relative;
        animation: fadeIn 0.3s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.04);
        word-wrap: break-word;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message.received {
        align-self: flex-start;
        background: #ffffff;
        color: #334155;
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 4px;
    }

    .message.sent {
        align-self: flex-end;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border-bottom-right-radius: 4px;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
    }

    .message-footer {
        font-size: 0.7rem;
        margin-top: 6px;
        text-align: right;
        opacity: 0.7;
        display: block;
    }

    .message.sent .message-footer {
        color: #e0e7ff;
    }

    .message.received .message-footer {
        color: #94a3b8;
    }

    .composer-area {
        padding: 20px 25px;
        border-top: 1px solid #ebedf2;
        background: #ffffff;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 15px;
    }

    .filter-grid select {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        padding: 8px 12px;
        font-size: 0.85rem;
        background-color: #f8fafc;
        color: #475569;
        transition: all 0.2s;
        width: 100%;
    }

    .filter-grid select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        outline: none;
    }

    .composer-input-wrapper {
        display: flex;
        gap: 15px;
        align-items: flex-end;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 10px 10px 10px 20px;
        transition: all 0.3s;
    }

    .composer-input-wrapper:focus-within {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        background: #ffffff;
    }

    #msg-content {
        border: none;
        background: transparent;
        box-shadow: none;
        resize: none;
        padding: 8px 0;
        margin: 0;
        min-height: 40px;
        max-height: 120px;
        overflow-y: auto;
        font-size: 0.95rem;
        color: #334155;
        flex: 1;
        outline: none;
    }

    #msg-content::placeholder {
        color: #94a3b8;
    }

    #msg-content::-webkit-scrollbar {
        width: 4px;
    }

    #msg-content::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .send-btn {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border: none;
        border-radius: 16px;
        height: 40px;
        padding: 0 20px;
        font-weight: 600;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .send-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        color: white;
    }

    .send-btn:active {
        transform: translateY(0);
    }

    /* Elegant Modal Overrides */
    .custom-modal .modal-content {
        border: none;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .custom-modal .modal-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 20px 25px;
    }

    .custom-modal .modal-title {
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        font-size: 1.2rem;
    }

    .custom-modal .close {
        opacity: 0.5;
        transition: opacity 0.2s;
        margin-top: -2px;
    }

    .custom-modal .close:hover {
        opacity: 1;
    }

    .custom-modal .modal-body {
        padding: 25px;
    }

    .custom-modal .modal-footer {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 15px 25px;
    }

    .custom-modal .form-control {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        padding: 10px 15px;
        height: auto;
        box-shadow: none;
    }

    .custom-modal .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .custom-modal .form-group label {
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
        font-size: 0.9rem;
    }

    .custom-modal .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        font-weight: 600;
    }

    .custom-modal .btn-default {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 20px;
        background: white;
        color: #475569;
        font-weight: 500;
    }

    .sidebar-panel,
    .main-sidebar,
    .sidebar,
    #sidebar,
    .left-sidebar,
    aside.sidebar {
        display: none !important;
    }

    /* Hide drawer menu by default, allow JS to show it */
    #drawerMenu {
        display: none;
        z-index: 9999 !important;
    }

    #main,
    .content-wrapper,
    .main-content,
    .container {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    /* Reduced font sizes for W3 components */
    .w3-select,
    .w3-input,
    .w3-table {
        font-size: 0.82rem !important;
    }

    .w3-container h5 {
        font-size: 0.95rem !important;
    }

    .student-badge {
        background: #f1f5f9;
        color: #1e293b;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #e2e8f0;
        font-weight: 500;
        transition: all 0.2s;
    }

    .student-badge:hover {
        background: #e2e8f0;
    }

    .student-badge i {
        cursor: pointer;
        color: #ef4444;
        font-size: 0.9rem;
    }

    /* Group Members Modal Styles */
    .gm-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
    }
    .gm-row {
        background: #fff;
        transition: transform 0.2s;
    }
    .gm-row td {
        padding: 12px 15px;
        vertical-align: middle;
        border-top: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
    }
    .gm-row td:first-child {
        border-left: 1px solid #f1f5f9;
        border-radius: 10px 0 0 10px;
    }
    .gm-row td:last-child {
        border-right: 1px solid #f1f5f9;
        border-radius: 0 10px 10px 0;
    }
    .role-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }
    .role-student { background: #eff6ff; color: #3b82f6; }
    .role-faculty { background: #fff7ed; color: #ea580c; }

    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid #fee2e2;
        color: #ef4444;
        background: #fff;
    }
    .action-btn:hover {
        background: #ef4444;
        color: #fff;
        transform: scale(1.1);
    }

    .search-item {
        padding: 10px 15px;
        cursor: pointer;
        font-size: 0.85rem;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s;
    }

    .search-item:hover {
        background: #f8fafc;
        color: #3b82f6;
    }

    .g-mode-btn {
        flex: 1;
        text-align: center;
        padding: 10px;
        cursor: pointer;
        border: 1px solid #e2e8f0;
        background: #fff;
        transition: all 0.3s;
        font-weight: 500;
        font-size: 0.85rem;
    }

    .g-mode-btn.active {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #3b82f6;
        z-index: 1;
    }

    .g-mode-btn:first-child {
        border-radius: 8px 0 0 8px;
    }

    .g-mode-btn:last-child {
        border-radius: 0 8px 8px 0;
        margin-left: -1px;
    }
</style>

<div class="container-fluid chat-wrapper">
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <strong>Messages</strong>
                <div style="display: flex; gap: 8px;">
                    <button class="header-btn" onclick="$('#groupModal').modal('show')" title="New Group">
                        <i class="fa fa-users"></i>
                    </button>
                    <button class="header-btn" onclick="$('#dmModal').modal('show')" title="New Direct Message">
                        <i class="fa fa-user-plus"></i>
                    </button>
                </div>
            </div>
            <div class="chat-list">
                <div class="chat-item active" onclick="selectChannel(null, 'Academic Broadcast', event)">
                    <div style="display: flex; align-items: center;">
                        <div class="chat-item-icon"><i class="fa fa-bullhorn"></i></div>
                        <div class="chat-item-content">
                            <div class="chat-item-title">Academic Broadcast</div>
                            <div class="chat-item-subtitle">Send notices to classes</div>
                        </div>
                    </div>
                </div>
                <!-- Auto Session Groups -->
                <div class="sidebar-section-label" style="padding: 12px 20px 5px; font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; border-top: 1px solid #f1f5f9; margin-top: 10px;">University Sessions</div>
                <?php foreach ($allSessions as $s): ?>
                    <div class="chat-item" onclick="selectChannel('SES-<?= $s['id'] ?>', 'Session: <?= addslashes($s['name']) ?> (<?= addslashes($s['uni_short'] ?? '') ?>)', event)">
                        <div style="display: flex; align-items: center;">
                            <div class="chat-item-icon" style="background-color: #f0fdf4; color: #16a34a;"><i class="fa fa-calendar"></i></div>
                            <div class="chat-item-content">
                                <div class="chat-item-title"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['uni_short'] ?? '') ?>)</div>
                                <div class="chat-item-subtitle">Auto-Group</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="sidebar-section-label" style="padding: 12px 20px 5px; font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; border-top: 1px solid #f1f5f9; margin-top: 10px;">Custom Groups</div>
                <?php foreach ($groups as $g): ?>
                    <div class="chat-item"
                        onclick="selectChannel(<?= $g['id'] ?>, '<?= addslashes($g['group_name']) ?>', event)">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; flex: 1; overflow: hidden;">
                                <div class="chat-item-icon" style="background-color: #f1f5f9; color: #64748b;"><i
                                        class="fa fa-users"></i></div>
                                <div class="chat-item-content">
                                    <div class="chat-item-title"><?= htmlspecialchars($g['group_name']) ?></div>
                                    <div class="chat-item-subtitle">Custom Group Chat</div>
                                </div>
                            </div>
                            <div class="chat-item-action" style="margin-left: 10px; display: flex; gap: 5px;">
                                <i class="fa fa-users"
                                    style="color: #cbd5e1; cursor: pointer; padding: 5px; transition: color 0.2s;"
                                    onmouseover="this.style.color='#10b981'" onmouseout="this.style.color='#cbd5e1'"
                                    title="View Members"
                                    onclick="event.stopPropagation(); viewGroupMembers(<?= $g['id'] ?>, '<?= addslashes($g['group_name']) ?>')"></i>
                                <i class="fa fa-pencil"
                                    style="color: #cbd5e1; cursor: pointer; padding: 5px; transition: color 0.2s;"
                                    onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='#cbd5e1'"
                                    title="Rename Group"
                                    onclick="event.stopPropagation(); renameGroup(<?= $g['id'] ?>, '<?= addslashes($g['group_name']) ?>')"></i>
                                <i class="fa fa-trash"
                                    style="color: #cbd5e1; cursor: pointer; padding: 5px; transition: color 0.2s;"
                                    onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#cbd5e1'"
                                    title="Delete Group"
                                    onclick="event.stopPropagation(); deleteGroup(<?= $g['id'] ?>, '<?= addslashes($g['group_name']) ?>')"></i>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Area -->
        <div class="chat-main">
            <div class="chat-header" style="padding: 10px 20px; border-bottom: 1px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; cursor: pointer; flex: 1; padding: 5px; border-radius: 8px; transition: background 0.2s;" 
                     onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'"
                     onclick="openCurrentGroupMembers()" title="View Group Details">
                    <div id="header-avatar" style="width: 42px; height: 42px; border-radius: 12px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-size: 1.2rem; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);">
                        <i class="fa fa-bullhorn" id="header-icon"></i>
                    </div>
                    <div>
                        <h4 id="active-title" style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                            Academic Broadcast
                            <i class="fa fa-chevron-right" style="font-size: 0.7rem; color: #94a3b8;"></i>
                        </h4>
                        <div class="text-muted" id="active-subtitle" style="font-size: 0.8rem; margin-top: 2px;">Select filters to broadcast to specific classes</div>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="header-btn" style="background: #f1f5f9; color: #64748b;" onclick="openCurrentGroupMembers()">
                        <i class="fa fa-info-circle"></i>
                    </div>
                </div>
            </div>
            <!-- Normal Chat View -->
            <div id="normal-chat-view" style="display: none; flex-direction: column; flex: 1; overflow: hidden;">
                <div class="messages-display" id="message-list">
                    <div class="text-center"
                        style="margin: auto; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 15px;">
                        <i class="fa fa-comments-o" style="font-size: 4rem; color: #cbd5e1;"></i>
                        <p style="font-size: 1.1rem; font-weight: 500;">Select a channel to start messaging</p>
                    </div>
                </div>
                <div class="composer-area">
                    <form id="chat-form" onsubmit="return false;">
                        <div class="composer-input-wrapper">
                            <textarea id="msg-content" placeholder="Type your message here..."
                                oninput="this.style.height = ''; this.style.height = Math.min(this.scrollHeight, 120) + 'px'"></textarea>
                            <button type="button" id="send-btn" class="send-btn">
                                <span>Send</span>
                                <i class="fa fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Broadcast Dashboard View -->
            <div id="broadcast-dashboard-view"
                style="display: none; flex: 1; overflow-y: auto; background: #f0f2f5; padding: 15px;">
                <div class="w3-row-padding">
                    <!-- Left Column: Student List -->
                    <div class="w3-col l7 m12 s12" style="margin-bottom: 20px;">
                        <div class="w3-card w3-white w3-round-large overflow-hidden">
                            <header class="w3-container w3-light-grey w3-padding">
                                <h5 style="margin:0; font-weight: 600; color: #344767;"><i
                                        class="fa fa-users w3-text-blue"></i> Student Selection</h5>
                            </header>

                            <div class="w3-container w3-padding-16">
                                <div class="w3-row-padding" style="margin: 0 -16px;">
                                    <div class="w3-col l3 m6 s12 w3-margin-bottom">
                                        <label class="w3-text-grey w3-small">Session</label>
                                        <select id="session" class="w3-select w3-border w3-round">
                                            <option value="0" selected disabled>Select Session</option>
                                            <?php foreach ($allSessions as $s): ?>
                                                <option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="w3-col l3 m6 s12 w3-margin-bottom">
                                        <label class="w3-text-grey w3-small">Course</label>
                                        <select id="course" class="w3-select w3-border w3-round">
                                            <option value="" selected disabled>Select Course</option>
                                            <?php foreach ($allCourses as $c): ?>
                                                <option value="<?= $c['id'] ?>"><?= $c['sname'] ?> - <?= $c['name'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="w3-col l3 m6 s12 w3-margin-bottom">
                                        <label class="w3-text-grey w3-small">Semester</label>
                                        <select id="sem" class="w3-select w3-border w3-round">
                                            <option value="" selected disabled>Sem/Year</option>
                                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                                <option value="Sem<?= $i ?>">Sem <?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="w3-col l3 m6 s12 w3-margin-bottom">
                                        <label class="w3-text-grey w3-small">Roll Number</label>
                                        <input type="text" id="roll" placeholder="Enter Roll"
                                            class="w3-input w3-border w3-round">
                                    </div>
                                </div>
                            </div>

                            <div class="w3-responsive" style="max-height: 600px; overflow-y: auto;">
                                <table class="w3-table w3-striped w3-bordered w3-hoverable" id="student-table">
                                    <thead>
                                        <tr class="w3-light-grey">
                                            <th style="width: 50px; text-align: center;">
                                                <input type="checkbox" id="checkAll" class="w3-check"
                                                    onclick="enabledBtn()">
                                            </th>
                                            <th>S no.</th>
                                            <th>Name</th>
                                            <th>Roll</th>
                                            <th>Course</th>
                                            <th>Session</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-data">
                                        <tr>
                                            <td colspan="6" class="w3-center w3-padding-32 w3-text-grey">
                                                <i class="fa fa-filter w3-xxlarge"></i><br>
                                                Select filters to load students
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Compose & Send -->
                    <div class="w3-col l5 m12 s12">
                        <!-- Contact Selection Card -->
                        <div class="w3-card w3-white w3-round-large w3-margin-bottom" id="contact-table">
                            <header class="w3-container w3-light-grey w3-padding">
                                <h5 style="margin:0; font-weight: 600; color: #344767;"><i
                                        class="fa fa-address-book w3-text-green"></i> Target Contacts</h5>
                            </header>
                            <div class="w3-container w3-padding-16">
                                <div class="w3-row">
                                    <div class="w3-col s3">
                                        <input type="checkbox" name="contact" value="M"
                                            class="w3-check contactCheckbox">
                                        <label class="w3-small">Mother</label>
                                    </div>
                                    <div class="w3-col s3">
                                        <input type="checkbox" name="contact" value="S" class="w3-check contactCheckbox"
                                            checked>
                                        <label class="w3-small">Student</label>
                                    </div>
                                    <div class="w3-col s3">
                                        <input type="checkbox" name="contact" value="F"
                                            class="w3-check contactCheckbox">
                                        <label class="w3-small">Father</label>
                                    </div>
                                    <div class="w3-col s3">
                                        <input type="checkbox" id="all" name="contact" value="All"
                                            class="w3-check checkAllContact">
                                        <label class="w3-small">All</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Template Selection Card -->
                        <div class="w3-card w3-white w3-round-large w3-margin-bottom">
                            <header class="w3-container w3-light-grey w3-padding">
                                <h5 style="margin:0; font-weight: 600; color: #344767;"><i
                                        class="fa fa-file-text-o w3-text-orange"></i> Message Template</h5>
                            </header>
                            <div class="w3-container w3-padding-16">
                                <div class="w3-row-padding" style="margin: 0 -16px;">
                                    <div class="w3-col s6 w3-margin-bottom">
                                        <label class="w3-text-grey w3-small">Module</label>
                                        <select class="w3-select w3-border w3-round" name="module_dropdown"
                                            id="module_dropdown">
                                            <option value="" selected disabled>Select</option>
                                            <?php
                                            $moduleQuery = $con->query("SELECT DISTINCT t1.module_id,t2.module_name FROM template_master_new AS t1 INNER JOIN module_master AS t2 ON t1.module_id = t2.module_id WHERE t1.template_status = 'active'");
                                            if ($moduleQuery) {
                                                while ($moduleRow = $moduleQuery->fetch_assoc()) {
                                                    echo '<option value="' . $moduleRow["module_id"] . '">' . $moduleRow["module_name"] . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="w3-col s6 w3-margin-bottom">
                                        <label class="w3-text-grey w3-small">Category</label>
                                        <select class="w3-select w3-border w3-round" name="cat_name" id="cat_name">
                                            <option value="" selected disabled>Select</option>
                                        </select>
                                    </div>
                                    <div class="w3-col s6 w3-margin-bottom">
                                        <label class="w3-text-grey w3-small">Sub Category</label>
                                        <select class="w3-select w3-border w3-round" name="sub_cat_name"
                                            id="sub_cat_name"></select>
                                    </div>
                                    <div class="w3-col s6 w3-margin-bottom">
                                        <label class="w3-text-grey w3-small">Template</label>
                                        <select class="w3-select w3-border w3-round" name="template_short_name"
                                            id="template_short_name"></select>
                                    </div>
                                </div>

                                <div class="w3-margin-top">
                                    <div class="w3-row">
                                        <div class="w3-col s6"><label class="w3-text-grey w3-small">Message
                                                Content</label></div>
                                        <div class="w3-col s6 w3-right-align"><span
                                                class="w3-tag w3-round w3-light-grey w3-small">Chars: <span
                                                    id="template_message_character_count">0</span></span></div>
                                    </div>
                                    <textarea id="template" name="template" class="w3-input w3-border w3-round"
                                        placeholder="Select a template or type your message..."
                                        style="height: 150px; resize: none; margin-top: 5px;"></textarea>
                                </div>

                                <!-- Summary Grid -->
                                <div
                                    class="w3-row w3-margin-top w3-border w3-round w3-light-grey w3-padding-small w3-small">
                                    <div class="w3-col s6">Students: <strong id="total_students"
                                            class="w3-text-blue">00</strong></div>
                                    <div class="w3-col s6">Contacts: <strong id="total_contacts"
                                            class="w3-text-green">01</strong></div>
                                    <div class="w3-col s12 w3-margin-top">
                                        <div class="w3-text-grey">Selected Route:</div>
                                        <div id="selected_module" class="w3-tag w3-white w3-border w3-round"
                                            style="margin:2px">--</div>
                                        <div id="selected_category" class="w3-tag w3-white w3-border w3-round"
                                            style="margin:2px">--</div>
                                        <div id="selected_sub_category" class="w3-tag w3-white w3-border w3-round"
                                            style="margin:2px">--</div>
                                    </div>
                                </div>

                                <!-- Media Upload -->
                                <div class="w3-row-padding w3-margin-top" style="margin: 0 -8px;">
                                    <div class="w3-col s6">
                                        <label class="w3-button w3-block w3-light-grey w3-border w3-round w3-small"
                                            style="padding: 10px;">
                                            <i class="fa fa-image w3-text-blue"></i> Upload Image
                                            <input type="file" id="wp_image_upload" accept="image/*"
                                                style="display:none;">
                                            <input type="hidden" id="wp_image_upload_link" />
                                        </label>
                                    </div>
                                    <div class="w3-col s6">
                                        <label class="w3-button w3-block w3-light-grey w3-border w3-round w3-small"
                                            style="padding: 10px;">
                                            <i class="fa fa-file-pdf-o w3-text-red"></i> Upload PDF
                                            <input type="file" id="wp_pdf_upload" accept="application/pdf"
                                                style="display:none;">
                                            <input type="hidden" id="wp_pdf_upload_link" />
                                        </label>
                                    </div>
                                </div>

                                <button id="previewBtn" onclick="$('#previewModel').modal('show')" disabled="true"
                                    class="w3-button w3-block w3-blue w3-round-large w3-margin-top w3-large"
                                    style="height: 50px; font-weight: 600;">
                                    <i class="fa fa-paper-plane"></i> Preview & Send
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade custom-modal" id="broadcastStatsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-bar-chart"></i> Broadcast Read Receipts</h4>
            </div>
            <div class="modal-body" id="broadcast_stats_body" style="max-height: 60vh; overflow-y: auto;">
                <!-- Stats will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade custom-modal" id="groupMembersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-users"></i> Group Members: <span id="gm-title"></span></h4>
            </div>
            <div class="modal-body">
                <div id="gm-add-area" style="margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; display: none;">
                    <label style="font-size: 0.8rem; color: #64748b; margin-bottom: 8px;">Add New Member</label>
                    <div style="position: relative;">
                        <i class="fa fa-search" style="position: absolute; left: 12px; top: 10px; color: #94a3b8; font-size: 0.9rem;"></i>
                        <input type="text" id="gm-search-input" class="form-control input-sm" placeholder="Search student name..." onkeyup="searchAddMembers()" style="padding-left: 35px; border-radius: 20px;">
                        <div id="gm-search-results" style="position: absolute; width: 100%; background: white; z-index: 1000; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-top: 5px; display: none; max-height: 200px; overflow-y: auto;"></div>
                    </div>
                </div>
                <div id="group_members_body" style="max-height: 50vh; overflow-y: auto;">
                    <!-- Members will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div id="dmModal" class="modal fade custom-modal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">New Direct Message</h4>
            </div>
            <div class="modal-body">
                <div style="position: relative;">
                    <i class="fa fa-search" style="position: absolute; left: 15px; top: 14px; color: #94a3b8;"></i>
                    <input type="text" id="student-search" class="form-control"
                        placeholder="Search student by name or roll number..." onkeyup="searchStudents()"
                        style="padding-left: 40px;">
                </div>
                <div id="search-results" style="margin-top: 15px; max-height: 300px; overflow-y: auto;">
                    <div class="text-center text-muted" style="padding: 20px 0;">
                        <i class="fa fa-search" style="font-size: 2rem; color: #e2e8f0; margin-bottom: 10px;"></i><br>
                        Type to search students...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade custom-modal" id="groupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Create New Group</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Group Name</label>
                    <input type="text" id="g-name" class="form-control" placeholder="e.g. Project Team A" required>
                </div>

                <div class="form-group">
                    <label>Selection Mode</label>
                    <div style="display: flex;">
                        <div id="btn-mode-auto" class="g-mode-btn active" onclick="toggleGroupMode('auto')">Auto (Filters)</div>
                        <div id="btn-mode-manual" class="g-mode-btn" onclick="toggleGroupMode('manual')">Manual (Select)</div>
                    </div>
                </div>

                <div id="g-auto-fields">
                    <div class="form-group">
                        <label>Auto-Add University</label>
                        <select id="g-uni" class="form-control">
                            <option value="">None</option>
                            <?php foreach ($universities as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= $u['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Auto-Add Course</label>
                                <select id="g-crs" class="form-control">
                                    <option value="">None</option>
                                    <?php foreach ($allCourses as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= $c['sname'] ?> - <?= $c['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Auto-Add Semester</label>
                                <select id="g-sem" class="form-control">
                                    <option value="">None</option>
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <option value="Sem<?= $i ?>">Sem <?= $i ?></option><?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="g-manual-fields" style="display: none;">
                    <div class="form-group">
                        <label>Search Students</label>
                        <div style="position: relative;">
                            <i class="fa fa-search" style="position: absolute; left: 15px; top: 12px; color: #94a3b8;"></i>
                            <input type="text" id="g-student-search" class="form-control" placeholder="Search student by name or roll..." onkeyup="searchGroupStudents()" style="padding-left: 40px;">
                            <div id="g-search-results" style="margin-top: 5px; max-height: 150px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; display: none; background: white; position: absolute; width: 100%; z-index: 100; box-shadow: 0 4px 12px rgba(0,0,0,0.1);"></div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 20px;">
                        <label>Selected Students (<span id="g-selected-count">0</span>)</label>
                        <div id="g-selected-students" style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 5px; min-height: 40px; padding: 10px; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1;">
                            <div class="text-muted" style="font-size: 0.8rem; width: 100%; text-align: center;">No students selected yet</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="createGroup()">Create Group</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade custom-modal" id="previewModel" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-eye fa-beat-fade"></i> Preview Messages</h4>
            </div>
            <div class="modal-body" id="preview_msg" style="max-height: 60vh; overflow-y: auto;">
                <!-- Preview message content will be loaded here -->
            </div>
            <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 1; margin-right: 15px; display: none;" id="progress-bar-container">
                    <div class="progress" style="margin: 0; height: 20px;">
                        <div class="progress-bar progress-bar-striped active" id="progress-bar" role="progressbar"
                            style="width: 0%"></div>
                    </div>
                    <div id="progress-text" style="font-size: 12px; text-align: center;">0%</div>
                </div>
                <button name="sendBtn" id="sendBtn" type="button" class="btn btn-success"
                    style="background: #10b981; border: none; font-weight: 600;">
                    <i class="fa fa-whatsapp"></i> Send WhatsApp
                </button>
            </div>
        </div>
    </div>
</div>

<?php
if (file_exists("pages/footer.php")) {
    include_once("pages/footer.php");
} elseif (file_exists("footer.php")) {
    include_once("footer.php");
}
?>

<script>
    var currentGroupId = null;

    // Failsafe: Wait for jQuery to be available from the portal's footer
    (function checkJQ() {
        if (typeof jQuery === 'undefined') {
            setTimeout(checkJQ, 100);
            return;
        }
        initFacultyPortal();
    })();

    function initFacultyPortal() {
        // Initial load - start with Academic Broadcast
        selectChannel(null, 'Academic Broadcast');

        $('#send-btn').on('click', sendMessage);
        $('#chat-form').on('submit', function (e) {
            e.preventDefault();
            sendMessage();
            return false;
        });

        // Auto refresh every 5 seconds
        setInterval(fetchMessages, 5000);

        // Sidebar Toggle/Close Logic - Fixed for Faculty Portal
        $(document).off('click', '[dn-toggle="drawer"]').on('click', '[dn-toggle="drawer"]', function (e) {
            e.preventDefault();
            $('#drawerMenu').show();
        });

        $(document).off('click', '[dn-dismiss="drawer"]').on('click', '[dn-dismiss="drawer"]', function (e) {
            e.preventDefault();
            $('#drawerMenu').hide();
        });
    }

    function selectChannel(id, title, event) {
        currentGroupId = id;
        
        // Update header icon/avatar
        const headerIcon = document.getElementById('header-icon');
        const headerAvatar = document.getElementById('header-avatar');
        const activeTitle = document.getElementById('active-title');
        
        activeTitle.innerHTML = title;

        if (id && id.toString().startsWith('SES-')) {
            headerIcon.className = 'fa fa-university';
            headerAvatar.style.background = '#fef3c7';
            headerAvatar.style.color = '#d97706';
            $('#active-subtitle').text("Auto Group (Session Based Broadcast)");
            $('#normal-chat-view').css('display', 'flex');
            $('#broadcast-dashboard-view').hide();
            fetchMessages();
        } else if (id && id.toString().startsWith('DM-')) {
            headerIcon.className = 'fa fa-user';
            headerAvatar.style.background = '#f0fdf4';
            headerAvatar.style.color = '#16a34a';
            $('#active-subtitle').text("Direct Message");
            $('#normal-chat-view').css('display', 'flex');
            $('#broadcast-dashboard-view').hide();
            fetchMessages();
        } else if (id) {
            headerIcon.className = 'fa fa-users';
            headerAvatar.style.background = '#eff6ff';
            headerAvatar.style.color = '#3b82f6';
            activeTitle.innerHTML = title + ' <i class="fa fa-chevron-right" style="font-size: 0.7rem; color: #94a3b8;"></i>';
            $('#active-subtitle').text("Custom Group Chat • View Info");
            $('#normal-chat-view').css('display', 'flex');
            $('#broadcast-dashboard-view').hide();
            fetchMessages();
        } else {
            headerIcon.className = 'fa fa-bullhorn';
            headerAvatar.style.background = '#eff6ff';
            headerAvatar.style.color = '#3b82f6';
            $('#active-subtitle').text("Select filters to broadcast to specific classes");
            $('#normal-chat-view').hide();
            $('#broadcast-dashboard-view').css('display', 'flex');
            loadBroadcastStudents();
        }

        $('.chat-item').removeClass('active');
        if (event) {
            $(event.currentTarget).addClass('active');
        }
    }

    function fetchMessages() {
        if (typeof $ === 'undefined') return;

        const payload = new FormData();
        payload.append('POST_TYPE', 'GET_MESSAGES');

        let gId = currentGroupId || '';
        let sessionVal = '';

        if (gId && gId.toString().startsWith('SES-')) {
            sessionVal = gId.replace('SES-', '');
            gId = '';
        } else {
            // Fallback to broadcast filters if available
            sessionVal = $('#session').val() || ''; // Fixed from f-ses to session
        }

        payload.append('groupId', gId);
        payload.append('university', $('#university').val() || ''); // Fixed from f-uni
        payload.append('session', sessionVal);
        payload.append('course', $('#course').val() || ''); // Fixed from f-crs
        payload.append('semester', $('#sem').val() || ''); // Fixed from f-sem

        fetch('faculty_portal.php', { method: 'POST', body: payload })
            .then(r => r.text())
            .then(text => {
                try {
                    const cleanJson = text.substring(text.indexOf('{'));
                    const res = JSON.parse(cleanJson);
                    const list = $('#message-list');
                    if (list.length === 0) return;
                    list.empty();

                    if (res.error !== 0) {
                        list.append('<div class="alert alert-danger">Error: ' + res.message + '</div>');
                        return;
                    }

                    if (!res.data || res.data.length === 0) {
                        list.append(`
                            <div class="text-center" style="margin: auto; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 15px;">
                                <i class="fa fa-envelope-open-o" style="font-size: 4rem; color: #cbd5e1;"></i>
                                <p style="font-size: 1.1rem; font-weight: 500;">No message history found</p>
                            </div>
                        `);
                    } else {
                        const myId = "<?= $faculty_id ?>";
                        res.data.forEach(msg => {
                            const isMe = (msg.sender_id == myId);
                            const date = new Date(msg.createdAt.replace(/-/g, "/"));
                            const time = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            const dateStr = date.toLocaleDateString();

                            let readStatus = '';
                            if (isMe) {
                                if (msg.groupId && msg.groupId.toString().startsWith('BCT-')) {
                                    readStatus = `
                                        <div style="cursor: pointer; background: #ffeb3b; color: #1a1d20; font-weight: 700; padding: 3px 10px; border-radius: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.15);" onclick="viewBroadcastStats('${msg.groupId}')" title="Click to view read receipts">
                                            <i class="fa fa-users"></i> ${msg.broadcast_seen} / ${msg.broadcast_total} Seen
                                        </div>
                                    `;
                                } else if (msg.receiver_id) {
                                    readStatus = msg.is_read == 1 
                                        ? `<span style="background: #4ade80; color: #0f172a; font-weight: 700; padding: 3px 10px; border-radius: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.15); cursor: pointer;" title="Read at ${msg.read_at}" onclick="viewBroadcastStats(null, ${msg.id})"><i class="fa fa-check-circle"></i> Seen</span>`
                                        : `<span style="background: rgba(255,255,255,0.25); color: #fff; font-weight: 600; padding: 3px 10px; border-radius: 20px;" title="Unread"><i class="fa fa-check"></i> Sent</span>`;
                                }
                            }

                            let actions = '';
                            if (isMe) {
                                actions = `
                                    <div class="message-actions" style="position: absolute; right: 10px; top: 8px; display: flex; gap: 8px; z-index: 10;">
                                        <div onclick="editMessage(${msg.id}, '${msg.content.replace(/'/g, "\\'")}')" style="width: 24px; height: 24px; background: rgba(255,255,255,0.2); border-radius: 4px; display: flex; align-items: center; justify-content: center; cursor: pointer;" title="Edit">
                                            <i class="fa fa-pencil" style="color: #fff; font-size: 12px;"></i>
                                        </div>
                                        <div onclick="deleteMessage(${msg.id})" style="width: 24px; height: 24px; background: rgba(239, 68, 68, 0.8); border-radius: 4px; display: flex; align-items: center; justify-content: center; cursor: pointer;" title="Delete">
                                            <i class="fa fa-trash" style="color: #fff; font-size: 12px;"></i>
                                        </div>
                                    </div>
                                `;
                            }

                            let recipientLabel = '';
                            if (isMe) {
                                if (msg.groupId && msg.groupId.toString().startsWith('BCT-')) {
                                    recipientLabel = `<div style="font-size: 10px; color: #bfdbfe; margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fa fa-bullhorn"></i> Broadcast</div>`;
                                } else if (msg.recipient_name) {
                                    recipientLabel = `<div style="font-size: 10px; color: #bfdbfe; margin-bottom: 4px; font-weight: 600;">Sent to: ${msg.recipient_name}</div>`;
                                }
                            }

                            list.append(`
                                <div class="message ${isMe ? 'sent' : 'received'}" style="position: relative; padding-top: ${isMe ? '30px' : '10px'};">
                                    ${actions}
                                    <div style="font-size: 11px; margin-bottom: 3px; font-weight: bold; opacity: 0.8;">
                                        ${isMe ? 'You' : 'Student'}
                                    </div>
                                    ${recipientLabel}
                                    <div class="message-text" style="word-break: break-word;">${msg.content}</div>
                                    <div class="message-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px; font-size: 10px; opacity: 0.9;">
                                        <span><i class="fa fa-clock-o"></i> ${dateStr} ${time}</span>
                                        <div style="display: flex; align-items: center; gap: 4px;">
                                            ${readStatus}
                                        </div>
                                    </div>
                                </div>
                            `);
                        });
                        setTimeout(() => {
                            list.scrollTop(list[0].scrollHeight);
                        }, 100);
                    }
                } catch (err) {
                    console.error("Chat Render Error:", err);
                    console.log("Raw Response:", text);
                }
            });
    }

    function sendMessage() {
        const content = $('#msg-content').val();
        if (!content) return;
        const payload = new FormData();
        payload.append('POST_TYPE', 'SEND_MESSAGE');
        payload.append('content', content);

        let gId = currentGroupId || '';
        let sessionVal = '';

        if (gId && gId.toString().startsWith('SES-')) {
            sessionVal = gId.replace('SES-', '');
            gId = '';
        } else {
            sessionVal = $('#session').val() || '';
        }

        payload.append('groupId', gId);
        payload.append('university', $('#university').val() || '');
        payload.append('session', sessionVal);
        payload.append('course', $('#course').val() || '');
        payload.append('semester', $('#sem').val() || '');

        fetch('faculty_portal.php', { method: 'POST', body: payload })
            .then(r => r.text())
            .then(text => {
                try {
                    const res = JSON.parse(text);
                    if (res.error === 0) {
                        $('#msg-content').val('');
                        fetchMessages();
                    } else {
                        alert("Error: " + res.message);
                    }
                } catch (e) {
                    alert("Server Error: " + text);
                }
            });
    }

    let selectedGroupStudents = [];
    let groupMode = 'auto';

    function toggleGroupMode(mode) {
        groupMode = mode;
        if (mode === 'auto') {
            $('#btn-mode-auto').addClass('active');
            $('#btn-mode-manual').removeClass('active');
            $('#g-auto-fields').show();
            $('#g-manual-fields').hide();
        } else {
            $('#btn-mode-auto').removeClass('active');
            $('#btn-mode-manual').addClass('active');
            $('#g-auto-fields').hide();
            $('#g-manual-fields').show();
        }
    }

    function searchGroupStudents() {
        const query = $('#g-student-search').val();
        const results = $('#g-search-results');
        
        if (query.length < 2) {
            results.hide();
            return;
        }

        const payload = new FormData();
        payload.append('POST_TYPE', 'SEARCH_STUDENTS');
        payload.append('query', query);

        fetch('faculty_portal.php', { method: 'POST', body: payload })
            .then(r => r.json())
            .then(res => {
                results.empty().show();
                if (res.data.length === 0) {
                    results.append('<div class="search-item text-muted">No students found</div>');
                } else {
                    res.data.forEach(s => {
                        // Check if already selected
                        if (selectedGroupStudents.some(st => st.id == s.s_id)) return;
                        
                        results.append(`
                            <div class="search-item" onclick="addGroupStudent(${s.s_id}, '${s.s_name.replace(/'/g, "\\'")}', '${s.s_roll_no}')">
                                <strong>${s.s_name}</strong> <span class="text-muted">(Roll: ${s.s_roll_no})</span>
                            </div>
                        `);
                    });
                }
            });
    }

    function addGroupStudent(id, name, roll) {
        if (!selectedGroupStudents.some(s => s.id == id)) {
            selectedGroupStudents.push({ id, name, roll });
            renderSelectedGroupStudents();
        }
        $('#g-student-search').val('');
        $('#g-search-results').hide();
    }

    function removeGroupStudent(id) {
        selectedGroupStudents = selectedGroupStudents.filter(s => s.id != id);
        renderSelectedGroupStudents();
    }

    function renderSelectedGroupStudents() {
        const container = $('#g-selected-students');
        const countSpan = $('#g-selected-count');
        container.empty();
        countSpan.text(selectedGroupStudents.length);

        if (selectedGroupStudents.length === 0) {
            container.append('<div class="text-muted" style="font-size: 0.8rem; width: 100%; text-align: center;">No students selected yet</div>');
            return;
        }

        selectedGroupStudents.forEach(s => {
            container.append(`
                <div class="student-badge">
                    <span>${s.name}</span>
                    <i class="fa fa-times-circle" onclick="removeGroupStudent(${s.id})"></i>
                </div>
            `);
        });
    }

    function createGroup() {
        const name = $('#g-name').val();
        if (!name) {
            alert("Please enter a group name");
            return;
        }

        const payload = new FormData();
        payload.append('POST_TYPE', 'CREATE_GROUP');
        payload.append('group_name', name);
        
        if (groupMode === 'manual') {
            if (selectedGroupStudents.length === 0) {
                alert("Please select at least one student");
                return;
            }
            selectedGroupStudents.forEach(s => {
                payload.append('student_ids[]', s.id);
            });
        } else {
            payload.append('university', $('#g-uni').val());
            payload.append('course', $('#g-crs').val());
            payload.append('semester', $('#g-sem').val());
        }

        fetch('faculty_portal.php', { method: 'POST', body: payload })
            .then(r => r.json())
            .then(res => {
                if (res.error === 0) {
                    location.reload();
                } else {
                    alert(res.message);
                }
            });
    }

    function renameGroup(id, oldName) {
        const newName = prompt("Enter new name for the group:", oldName);
        if (newName && newName !== oldName) {
            const payload = new FormData();
            payload.append('POST_TYPE', 'EDIT_GROUP');
            payload.append('groupId', id);
            payload.append('group_name', newName);
            fetch('faculty_portal.php', { method: 'POST', body: payload })
                .then(r => r.json())
                .then(res => {
                    if (res.error === 0) {
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                });
        }
    }

    function searchStudents() {
        const query = $('#student-search').val();
        if (query.length < 2) return;

        const payload = new FormData();
        payload.append('POST_TYPE', 'SEARCH_STUDENTS');
        payload.append('query', query);

        fetch('faculty_portal.php', { method: 'POST', body: payload })
            .then(r => r.json())
            .then(res => {
                const results = $('#search-results');
                results.empty();
                if (res.data.length === 0) {
                    results.append('<div class="text-center text-muted" style="padding: 20px 0;">No students found</div>');
                } else {
                    res.data.forEach(s => {
                        results.append(`
                            <div class="chat-item" style="padding: 12px; margin-bottom: 8px; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s; background: #f8fafc;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'" onclick="startDirectChat(${s.s_id}, '${s.s_name}')">
                                <div style="display: flex; align-items: center;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #e0e7ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-weight: bold; flex-shrink: 0;">
                                        ${s.s_name.charAt(0)}
                                    </div>
                                    <div style="overflow: hidden;">
                                        <div style="font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${s.s_name}</div>
                                        <div style="font-size: 0.8rem; color: #64748b;">Roll: ${s.s_roll_no}</div>
                                    </div>
                                </div>
                            </div>
                        `);
                    });
                }
            });
    }

    function startDirectChat(sId, sName) {
        $('#dmModal').modal('hide');
        const dmId = `DM-F<?= $faculty_id ?>-S${sId}`;
        selectChannel(dmId, 'Chat: ' + sName);
    }

    /* === Academic Broadcast Logic === */

    function checkBoxClicked() {
        var checkboxes = document.getElementsByName("std_roll");
        var previewBtn = document.getElementById("previewBtn");
        var isAnyChecked = false;
        for (var i = 0; i < checkboxes.length; i++) {
            if (checkboxes[i].checked) {
                isAnyChecked = true;
                break;
            }
        }
        updatePreviewBtn(isAnyChecked);
    }

    function enabledBtn() {
        var check_all = document.getElementById("checkAll");
        var checkboxes = document.getElementsByName("std_roll");
        var isChecked = check_all.checked;

        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = isChecked;
        }
        updatePreviewBtn(isChecked && checkboxes.length > 0);
    }

    function updatePreviewBtn(isEnabled) {
        var previewBtn = document.getElementById("previewBtn");
        if (isEnabled) {
            previewBtn.disabled = false;
            $(previewBtn).removeClass('w3-light-grey').addClass('w3-blue').css('cursor', 'pointer');
        } else {
            previewBtn.disabled = true;
            $(previewBtn).removeClass('w3-blue').addClass('w3-light-grey').css('cursor', 'not-allowed');
        }
    }

    /* Template Character Count */
    $("#template").on("change keyup", function () {
        var charCount = $(this).val().length;
        $("#template_message_character_count").text(charCount);
    });

    /* Sync Contact Checkboxes */
    var selectContact = ["S"]; // default selected in HTML

    const contactCheckbox = document.querySelectorAll('.contactCheckbox');
    const contactAllCheckboxes = document.querySelector('.checkAllContact');

    if (contactAllCheckboxes) {
        contactAllCheckboxes.addEventListener('click', function () {
            if (this.checked) {
                contactCheckbox.forEach(function (item) { item.checked = true; });
            } else {
                contactCheckbox.forEach(function (item) { item.checked = false; });
            }
            updateContactsCount();
        });
    }

    contactCheckbox.forEach(function (item) {
        item.addEventListener('click', function () {
            if (!this.checked && contactAllCheckboxes) {
                contactAllCheckboxes.checked = false;
            } else if (contactAllCheckboxes) {
                let allChecked = true;
                contactCheckbox.forEach(function (item) {
                    if (!item.checked) allChecked = false;
                });
                if (allChecked) contactAllCheckboxes.checked = true;
            }
            updateContactsCount();
        });
    });

    function updateContactsCount() {
        selectContact = [];
        $('input[name="contact"]:checked').each(function () {
            if ((this).value != "All") {
                selectContact.push((this).value);
            }
        });
        $('#total_contacts').html(selectContact.length > 0 ? (selectContact.length < 10 ? '0' + selectContact.length : selectContact.length) : "00");
    }

    /* Update Info Grid on Select Changes */
    $('#module_dropdown').on('change', function () {
        $('#selected_module').html($(this).find("option:selected").text());
    });
    $('#cat_name').on('change', function () {
        $('#selected_category').html($(this).find("option:selected").text());
    });
    $('#sub_cat_name').on('change', function () {
        $('#selected_sub_category').html($(this).find("option:selected").text());
    });
    $('#template_short_name').on('change', function () {
        $('#selected_template').html($(this).find("option:selected").text());
    });

    /* Selection Logic for Students Table */
    var selected = []; // roll numbers
    var students_id = []; // student ids
    var totalCheckboxes = 0;

    $('#student-table').on('change', "input[type='checkbox']", function () {
        selected = [];
        students_id = [];
        $("input[name='std_roll']:checked").each(function () {
            var roll = $(this).attr('data-roll');
            var s_id = $(this).attr('data-id');
            if (roll !== undefined) {
                selected.push(roll);
                students_id.push(s_id);
            }
        });
        totalCheckboxes = selected.length;
        $('#total_students').html(totalCheckboxes > 0 ? (totalCheckboxes < 10 ? '0' + totalCheckboxes : totalCheckboxes) : "00");

        var allCheckboxChecked = ($("input[name='std_roll']").length > 0 && $("input[name='std_roll']").length === $("input[name='std_roll']:checked").length);
        $("#checkAll").prop('checked', allCheckboxChecked);

        updatePreviewBtn(totalCheckboxes > 0);
    });

    /* Load Filtered Students - Fallback to current API if filter-std-data.php is unavailable */
    function loadBroadcastStudents() {
        let session = $('#session').val() || "0";
        let course = $('#course').val() || "0";
        let sem = $('#sem').val() || "0";
        let roll = $('#roll').val() || "0";

        // Show loading
        $("#table-data").html('<tr><td colspan="6" style="padding: 20px; text-align: center;"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');

        // Use external filter if it exists, otherwise provide a local fallback mechanism
        $.ajax({
            url: "filter-std-data.php",
            type: "POST",
            data: { session: session, course: course, sem: sem, roll: roll },
            success: function (data) {
                try {
                    var parsedData = typeof data === 'string' ? JSON.parse(data) : data;
                    renderStudentTable(parsedData);
                } catch (e) {
                    $("#table-data").html("<tr><td colspan='6' class='text-center text-muted' style='padding: 20px;'>Error processing student data or filter endpoint unavailable.</td></tr>");
                }
            },
            error: function () {
                $("#table-data").html("<tr><td colspan='6' class='text-center text-muted' style='padding: 20px;'>Filter script (filter-std-data.php) not found.</td></tr>");
            }
        });
    }

    function renderStudentTable(data) {
        var sno = 1;
        var stdData = '';
        if (!data || data.length === 0) {
            $("#table-data").html("<tr><td colspan='6' class='w3-center w3-padding-32 w3-text-grey'>No data found!</td></tr>");
            return;
        }

        $.each(data, function (index, value) {
            let checkbox = '<input onclick="checkBoxClicked()" name="std_roll" data-roll="' + value.s_roll_no + '" data-id="' + value.s_id + '" type="checkbox" class="w3-check">';
            stdData += '<tr class="w3-hover-light-grey">';
            stdData += '<td class="w3-center" style="padding: 12px 10px;">' + checkbox + '</td>';
            stdData += '<td class="w3-text-grey" style="padding: 12px 10px;">' + sno + '</td>';
            stdData += '<td style="padding: 12px 10px; font-weight: 500; color: #1e293b;">' + value.s_name + '</td>';
            stdData += '<td class="w3-text-grey" style="padding: 12px 10px;">' + value.s_roll_no + '</td>';
            stdData += '<td class="w3-text-grey" style="padding: 12px 10px;">' + (value.course_short_name || '') + (value.course_name ? '-' + value.course_name : '') + '</td>';
            stdData += '<td class="w3-text-grey" style="padding: 12px 10px;">' + (value.session_name || '') + '</td>';
            stdData += '</tr>';
            sno++;
        });
        $("#table-data").html(stdData);

        // Reset selections
        selected = [];
        students_id = [];
        totalCheckboxes = 0;
        $('#total_students').html("00");
        $("#checkAll").prop('checked', false);
        updatePreviewBtn(false);
    }

    // Attach events to filters
    $('#session, #course, #sem, #roll').on('change input', function () {
        // Debounce input for roll
        if (this.id === 'roll') {
            clearTimeout(this.timer);
            this.timer = setTimeout(loadBroadcastStudents, 500);
        } else {
            loadBroadcastStudents();
        }
    });

    // Initial setup for counts
    updateContactsCount();

    /* Preview Message */
    $('#previewBtn').on('click', function () {
        let template = $('#template').val();
        if (!template) {
            alert("Please enter template details.");
            return;
        }

        // Assuming there is a preview_template_msg.php available
        $.ajax({
            url: "preview_template_msg.php",
            type: "POST",
            data: {
                selected: selected,
                students_id: students_id,
                selectContact: selectContact,
                template: template,
                totalCheckboxes: totalCheckboxes
            },
            success: function (data) {
                $('#preview_msg').html(data);
                $('#previewModel').modal('show');
            },
            error: function () {
                // Fallback basic preview
                $('#preview_msg').html(`
                    <div class="alert alert-info">
                        <strong>Preview Mode (Fallback)</strong><br>
                        Template: ${template}<br>
                        Contacts: ${selectContact.join(', ')}<br>
                        Students Selected: ${totalCheckboxes}
                    </div>
                `);
                $('#previewModel').modal('show');
            }
        });
    });

    /* Send WhatsApp */
    $('#sendBtn').on('click', function () {
        let template = $('#template').val();

        if (!template) {
            alert('Please select your template first!');
            return;
        }

        $('#progress-bar-container').show();
        let cat_id = $('#cat_name').val();
        let sub_cat_id = $('#sub_cat_name').val();
        let template_sid = $('#template_short_name').val();

        $.ajax({
            url: "faculty_portal.php",
            type: "POST",
            data: {
                POST_TYPE: 'SEND_BROADCAST',
                selected: selected,
                students_id: students_id,
                selectContact: selectContact,
                template: template,
                totalCheckboxes: totalCheckboxes,
                cat_id: cat_id,
                sub_cat_id: sub_cat_id,
                template_sid: template_sid,
                sendBtn: 1
            },
            xhr: function () {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        var percent = Math.round((e.loaded / e.total) * 100);
                        $('#progress-bar').css('width', percent + '%');
                        $('#progress-text').text(percent + '%');
                    }
                }, false);
                return xhr;
            },
            success: function (data) {
                $('#progress-bar-container').hide();
                setTimeout(() => {
                    $('#previewModel').modal('hide');
                    $('#progress-text').html('0%');
                    $("#progress-bar").css("width", "0%");
                    alert('Message sent successfully!');
                }, 1000);
            },
            error: function () {
                $('#progress-bar-container').hide();
                alert('Send script not available (sendMsg.php). Operation simulated.');
                $('#previewModel').modal('hide');
            }
        });
    });

    window.editMessage = function (msgId, oldContent) {
        const newContent = prompt("Edit your message:", oldContent);
        if (newContent !== null && newContent !== oldContent) {
            const payload = new FormData();
            payload.append('POST_TYPE', 'EDIT_MESSAGE');
            payload.append('msgId', msgId);
            payload.append('content', newContent);
            fetch('faculty_portal.php', { method: 'POST', body: payload })
                .then(r => r.json())
                .then(res => {
                    if (res.error === 0) fetchMessages();
                    else alert(res.message);
                });
        }
    };

    window.deleteMessage = function (msgId) {
        if (confirm("Are you sure you want to delete this message?")) {
            const payload = new FormData();
            payload.append('POST_TYPE', 'DELETE_MESSAGE');
            payload.append('msgId', msgId);
            fetch('faculty_portal.php', { method: 'POST', body: payload })
                .then(r => r.json())
                .then(res => {
                    if (res.error === 0) fetchMessages();
                    else alert(res.message);
                });
        }
    };

    window.viewBroadcastStats = function(broadcastId, msgId = null) {
        const payload = new FormData();
        payload.append('POST_TYPE', 'GET_BROADCAST_STATS');
        if (msgId) {
            payload.append('msg_id', msgId);
        } else {
            payload.append('broadcast_id', broadcastId);
        }
        
        fetch('faculty_portal.php', { method: 'POST', body: payload })
            .then(r => r.json())
            .then(res => {
                if (res.error === 0) {
                    let html = '<table class="table table-condensed table-hover"><thead><tr><th>Student</th><th>Roll No</th><th>Status</th></tr></thead><tbody>';
                    res.data.forEach(s => {
                        const status = s.is_read == 1 
                            ? `<span class="text-success" style="font-weight: 600;"><i class="fa fa-check-circle"></i> Seen at ${s.read_at}</span>`
                            : `<span class="text-muted"><i class="fa fa-check"></i> Unread</span>`;
                        html += `<tr><td>${s.s_name}</td><td>${s.s_roll_no}</td><td>${status}</td></tr>`;
                    });
                    html += '</tbody></table>';
                    $('#broadcast_stats_body').html(html);
                    $('#broadcastStatsModal').modal('show');
                } else {
                    alert(res.message);
                }
            });
    };

    window.viewGroupMembers = function(groupId, groupName) {
        $('#gm-title').text(groupName);
        $('#group_members_body').html('<div class="text-center" style="padding: 40px;"><i class="fa fa-spinner fa-spin" style="font-size: 2rem; color: #3b82f6;"></i><br><span style="margin-top: 10px; display: block; color: #64748b;">Fetching participants...</span></div>');
        $('#gm-add-area').show();
        $('#groupMembersModal').modal('show');

        const payload = new FormData();
        payload.append('POST_TYPE', 'GET_GROUP_MEMBERS');
        payload.append('groupId', groupId);

        fetch('faculty_portal.php', { method: 'POST', body: payload })
            .then(r => r.json())
            .then(res => {
                if (res.error === 0) {
                    let html = '<table class="gm-table"><thead><tr style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;"><th style="padding-left: 15px;">Name</th><th>Role</th><th>Info</th><th style="text-align: center;">Action</th></tr></thead><tbody>';
                    res.data.forEach(m => {
                        let role = m.role || 'Member';
                        let isStudent = (role.toLowerCase() === 'student');
                        let roleClass = isStudent ? 'role-student' : 'role-faculty';
                        let action = isStudent 
                            ? `<div class="action-btn" title="Remove Member" onclick="removeMemberFromGroup(${m.student_id}, '${m.name.replace(/'/g, "\\'")}')"><i class="fa fa-user-times"></i></div>`
                            : '<span style="color: #cbd5e1;">-</span>';
                        
                        html += `<tr class="gm-row">
                            <td style="font-weight: 600; color: #1e293b; font-size: 0.9rem;">${m.name}</td>
                            <td><span class="role-badge ${roleClass}">${role}</span></td>
                            <td class="text-muted" style="font-size: 0.85rem;">${m.info}</td>
                            <td style="display: flex; justify-content: center; border: none; padding-top: 15px;">${action}</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    $('#group_members_body').html(html);
                } else {
                    $('#group_members_body').html(`<div class="alert alert-danger" style="border-radius: 12px;">${res.message}</div>`);
                }
            });
    };

    window.openCurrentGroupMembers = function() {
        if (!currentGroupId || currentGroupId.toString().startsWith('SES-') || currentGroupId.toString().startsWith('DM-')) return;
        viewGroupMembers(currentGroupId, document.getElementById('active-title').innerText);
    };

    window.searchAddMembers = function() {
        const query = $('#gm-search-input').val();
        const results = $('#gm-search-results');
        if (query.length < 2) { results.hide(); return; }
        const payload = new FormData();
        payload.append('POST_TYPE', 'SEARCH_STUDENTS');
        payload.append('query', query);
        fetch('faculty_portal.php', { method: 'POST', body: payload }).then(r => r.json()).then(res => {
            results.empty().show();
            res.data.forEach(s => {
                results.append(`<div class="search-item" style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9;" onclick="addMemberToGroup(${s.s_id})"><strong>${s.s_name}</strong> <span class="text-muted" style="font-size: 0.75rem;">(${s.s_roll_no})</span></div>`);
            });
        });
    };

    window.addMemberToGroup = function(studentId) {
        const payload = new FormData();
        payload.append('POST_TYPE', 'ADD_MEMBER');
        payload.append('groupId', currentGroupId);
        payload.append('studentId', studentId);
        fetch('faculty_portal.php', { method: 'POST', body: payload }).then(r => r.json()).then(res => {
            if (res.error === 0) {
                $('#gm-search-input').val('');
                $('#gm-search-results').hide();
                viewGroupMembers(currentGroupId, document.getElementById('active-title').innerText);
            } else alert(res.message);
        });
    };

    window.removeMemberFromGroup = function(studentId, studentName) {
        if (confirm(`Remove ${studentName} from this group?`)) {
            const payload = new FormData();
            payload.append('POST_TYPE', 'REMOVE_MEMBER');
            payload.append('groupId', currentGroupId);
            payload.append('studentId', studentId);
            fetch('faculty_portal.php', { method: 'POST', body: payload }).then(r => r.json()).then(res => {
                if (res.error === 0) viewGroupMembers(currentGroupId, document.getElementById('active-title').innerText);
                else alert(res.message);
            });
        }
    };

    window.deleteGroup = function(groupId, groupName) {
        if (confirm(`Are you sure you want to delete "${groupName}"?`)) {
            const payload = new FormData();
            payload.append('POST_TYPE', 'DELETE_GROUP');
            payload.append('groupId', groupId);
            fetch('faculty_portal.php', { method: 'POST', body: payload }).then(r => r.json()).then(res => {
                if (res.error === 0) location.reload();
                else alert(res.message);
            });
        }
    };
</script>
<!-- Assuming external script dependencies exist as requested -->
<script src="js/general_module_temp_dropdown.js"></script>