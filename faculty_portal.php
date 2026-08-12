<?php
session_start();
$DOC_ROOT = $_SERVER["DOCUMENT_ROOT"];
define("TITLE", "GIIT Messanger");
require_once("$DOC_ROOT/dn_script/connect.php");
require_once("$DOC_ROOT/validator/validate_gs.php");

// Auto-create group message read status table if it doesn't exist
$con->query("CREATE TABLE IF NOT EXISTS `group_message_read_status` (
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `read_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`, `user_id`, `user_role`),
  CONSTRAINT `fk_gmrs_message_fac` FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;");

if (file_exists("functions.php")) {
    include("functions.php");
}
if (file_exists("firebase_helper.php")) {
    include("firebase_helper.php");
}

/**
 * Expo Push Notification Helper
 */
class ExpoHelper
{
    private static $expo_api_url = 'https://exp.host/--/api/v2/push/send';

    public static function sendNotification($to, $title, $body, $data = [])
    {
        if (empty($to))
            return ['error' => 'No recipients'];
        $notifications = [];
        $recipients = is_array($to) ? $to : [$to];
        foreach ($recipients as $token) {
            if (strpos($token, 'ExponentPushToken') !== 0 && strpos($token, 'ExpoPushToken') !== 0)
                continue;
            $notifications[] = [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'sound' => 'default',
                'priority' => 'high',
                'channelId' => 'default',
                'badge' => 1
            ];
        }
        if (empty($notifications))
            return ['error' => 'No valid tokens'];
        $chunks = array_chunk($notifications, 100);
        $results = [];
        foreach ($chunks as $chunk) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$expo_api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json', 'Accept-Encoding: gzip, deflate']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($chunk));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                error_log("Expo Push Error: " . curl_error($ch));
            }
            $results[] = json_decode($response, true);
            curl_close($ch);
        }
        return $results;
    }

    /**
     * Get all push tokens for a set of student IDs
     */
    public static function getStudentTokens($con, $student_ids)
    {
        if (empty($student_ids))
            return [];
        $ids = is_array($student_ids) ? $student_ids : [$student_ids];
        $ids_str = implode(',', array_map('intval', $ids));

        $tokens = [];

        // 1. Get from new unified table
        $res1 = $con->query("SELECT push_token FROM user_push_tokens WHERE user_id IN ($ids_str) AND user_role = 'student'");
        if ($res1) {
            while ($row = $res1->fetch_assoc())
                $tokens[] = $row['push_token'];
        }

        // 2. Get from legacy student table
        $res2 = $con->query("SELECT expo_token FROM student WHERE s_id IN ($ids_str) AND expo_token IS NOT NULL");
        if ($res2) {
            while ($row = $res2->fetch_assoc())
                $tokens[] = $row['expo_token'];
        }

        return array_unique($tokens);
    }

    /**
     * Smart Send: Routes to Expo or Firebase based on token format
     */
    public static function routeNotification($con, $target_ids, $title, $body, $data = [])
    {
        $all_tokens = self::getStudentTokens($con, $target_ids);
        if (empty($all_tokens))
            return ['error' => 'No tokens found'];

        $expo_tokens = [];
        $fcm_tokens = [];

        foreach ($all_tokens as $token) {
            if (strpos($token, 'ExponentPushToken') === 0 || strpos($token, 'ExpoPushToken') === 0) {
                $expo_tokens[] = $token;
            } else {
                $fcm_tokens[] = $token;
            }
        }

        $results = [];
        if (!empty($expo_tokens)) {
            $results['expo'] = self::sendNotification($expo_tokens, $title, $body, $data);
        }
        if (!empty($fcm_tokens) && class_exists('FirebaseHelper')) {
            $results['firebase'] = FirebaseHelper::sendNotification($fcm_tokens, $title, $body, $data);
        }
        return $results;
    }
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
    if ($type === 'save_expo_token') {
        $token = mysqli_real_escape_string($con, $_POST['token'] ?? '');
        if (!empty($token) && !empty($faculty_id)) {
            // Table creation handled manually by user
            $stmt = $con->prepare("INSERT INTO user_push_tokens (user_id, user_role, push_token) VALUES (?, 'faculty', ?) ON DUPLICATE KEY UPDATE last_updated = NOW()");
            $stmt->bind_param("is", $faculty_id, $token);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Faculty token saved']);
        } else {
            echo json_encode(['error' => 'Missing data or session']);
        }
        exit;
    }

    if ($type === 'SEND_MESSAGE') {
        $content = $_POST['content'] ?? '';
        $uni = $_POST['university'] ?? '';
        $session = $_POST['session'] ?? '';
        $course = $_POST['course'] ?? '';
        $semester = $_POST['semester'] ?? '';
        $groupId = $_POST['groupId'] ?? '';

        $file_path = null;
        $file_name = null;
        $file_type = null;

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/chat_files/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $tmp_name = $_FILES['attachment']['tmp_name'];
            $file_name_original = basename($_FILES['attachment']['name']);
            $file_type = $_FILES['attachment']['type'];

            $ext = pathinfo($file_name_original, PATHINFO_EXTENSION);
            $new_name = 'chat_' . time() . '_' . rand(1000, 9999) . '.' . $ext;

            if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                $file_path = 'uploads/chat_files/' . $new_name;
                $file_name = $file_name_original;
            }
        }

        // NEW: If sending to a session (no groupId but filters present), make it a tracked broadcast
        if (empty($groupId) && (!empty($uni) || !empty($session) || !empty($course) || !empty($semester))) {
            $broadcast_id = 'BCT-' . time() . '-' . rand(1000, 9999);

            // Build filter query
            $where = ["1=1"];
            if (!empty($uni))
                $where[] = "s_university_id = " . intval($uni);
            if (!empty($session))
                $where[] = "s_session_id = " . intval($session);
            if (!empty($course))
                $where[] = "s_course_id = " . intval($course);
            if (!empty($semester)) {
                $sem_num = intval(str_replace('Sem', '', $semester));
                $where[] = "s_cur_sem = $sem_num";
            }
            $where_str = implode(' AND ', $where);

            // Fetch exclusions for this session if it's a session-based broadcast
            $exclusion_key = !empty($session) ? "SES-" . $session : "GLOBAL";

            $std_query = $con->query("SELECT s_id FROM student WHERE $where_str AND s_status != 'dropout' AND s_id NOT IN (SELECT student_id FROM chat_group_exclusions WHERE group_key = '$exclusion_key')");
            $success_count = 0;
            if ($std_query) {
                while ($std = $std_query->fetch_assoc()) {
                    $s_id = $std['s_id'];
                    $ins = $con->prepare("INSERT INTO messages (sender_id, receiver_id, content, university, session, course, semester, groupId, file_path, file_name, file_type, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $ins->bind_param("iisssssssss", $faculty_id, $s_id, $content, $uni, $session, $course, $semester, $broadcast_id, $file_path, $file_name, $file_type);
                    if ($ins->execute()) {
                        $success_count++;
                    }
                    $ins->close();
                }
            }

            // NEW: Send Push (Expo + Firebase)
            if ($success_count > 0) {
                // Fetch student IDs matching the criteria
                $token_query = $con->query("SELECT s_id FROM student WHERE $where_str AND s_status != 'dropout'");
                $target_ids = [];
                while ($tr = $token_query->fetch_assoc())
                    $target_ids[] = $tr['s_id'];

                if (!empty($target_ids)) {
                    $notification_body = strlen($content) > 100 ? substr($content, 0, 97) . '...' : $content;
                    ExpoHelper::routeNotification($con, $target_ids, "Academic Notice", $notification_body, ['type' => 'notice', 'broadcast_id' => $broadcast_id, 'channelId' => 'default']);
                }
            }

            echo json_encode(['error' => 0, 'message' => "Broadcast sent to $success_count students"]);
            exit;
        }

        // Standard logic for groups/DMs
        $receiver_id = null;
        if (!empty($groupId) && strpos($groupId, 'DM-') === 0) {
            if (preg_match('/-S(\d+)$/', $groupId, $matches)) {
                $receiver_id = intval($matches[1]);
            }
        }

        $sql = "INSERT INTO messages (sender_id, receiver_id, content, university, session, course, semester, groupId, file_path, file_name, file_type, createdAt) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $g_id = !empty($groupId) ? $groupId : null;
            $stmt->bind_param("iisssssssss", $faculty_id, $receiver_id, $content, $uni, $session, $course, $semester, $g_id, $file_path, $file_name, $file_type);
            if ($stmt->execute()) {
                // NEW: Send Push for Group / DM Message
                if (!empty($g_id)) {
                    if (strpos($g_id, 'DM-') === 0) {
                        // It is a Direct Message (DM-F<faculty_id>-S<student_id>)
                        if (preg_match('/-S(\d+)$/', $g_id, $matches)) {
                            $target_student_id = intval($matches[1]);

                            $f_name = !empty($faculty_name) ? $faculty_name : 'Faculty';
                            $notification_body = strlen($content) > 100 ? substr($content, 0, 97) . '...' : $content;

                            ExpoHelper::routeNotification($con, [$target_student_id], "New Message from $f_name", $notification_body, ['type' => 'dm', 'groupId' => $g_id, 'channelId' => 'default']);
                        }
                    } else {
                        // Fetch Group Name
                        $gn_query = $con->query("SELECT group_name FROM chat_groups WHERE id = " . intval($g_id));
                        $g_name = ($gn_query && $gn_row = $gn_query->fetch_assoc()) ? $gn_row['group_name'] : 'Group Message';

                        // Fetch Group Members' tokens (excluding faculty)
                        $members_query = $con->query("SELECT user_id FROM group_members WHERE group_id = " . intval($g_id) . " AND user_role = 'student'");
                        $member_ids = [];
                        while ($mr = $members_query->fetch_assoc())
                            $member_ids[] = $mr['user_id'];

                        if (!empty($member_ids)) {
                            $notification_body = strlen($content) > 100 ? substr($content, 0, 97) . '...' : $content;
                            ExpoHelper::routeNotification($con, $member_ids, "New Message in $g_name", $notification_body, ['type' => 'group', 'groupId' => $g_id, 'channelId' => 'default']);
                        }
                    }
                }

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
                (SELECT COUNT(*) FROM messages m3 WHERE m3.groupId = m.groupId AND m3.is_read = 1 AND m.groupId LIKE 'BCT-%') as broadcast_seen,
                (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = CAST(m.groupId AS SIGNED) AND gm.user_role IN ('student', 'shortterm_student')) as group_total,
                (SELECT COUNT(*) FROM group_message_read_status gmrs WHERE gmrs.message_id = m.id AND gmrs.user_role IN ('student', 'shortterm_student')) as group_seen
                FROM messages m LEFT JOIN student s ON m.receiver_id = s.s_id WHERE 1=1";

        if (!empty($groupId)) {
            $g = mysqli_real_escape_string($con, $groupId);
            $sql .= " AND m.groupId = '$g'";

            // Mark messages as read when faculty retrieves them
            if (strpos($g, 'DM-') === 0) {
                $con->query("UPDATE messages SET is_read = 1, read_at = NOW() WHERE groupId = '$g' AND sender_id != $faculty_id AND is_read = 0");
            } else if (is_numeric($g)) {
                $gIdInt = intval($g);
                $gmrsStmt = $con->prepare("INSERT IGNORE INTO group_message_read_status (message_id, user_id, user_role) SELECT id, ?, 'faculty' FROM messages WHERE groupId = ? AND sender_id != ?");
                if ($gmrsStmt) {
                    $gmrsStmt->bind_param("isi", $faculty_id, $gIdInt, $faculty_id);
                    $gmrsStmt->execute();
                    $gmrsStmt->close();
                }
            }
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
        $bct_id = $_POST['broadcast_id'] ?? '';
        $msg_id = intval($_POST['msg_id'] ?? 0);

        if ($msg_id > 0) {
            $msg_query = $con->query("SELECT groupId, receiver_id FROM messages WHERE id = $msg_id");
            if ($msg_query && $msg_row = $msg_query->fetch_assoc()) {
                $gId = $msg_row['groupId'];
                
                if ($gId !== null && strpos($gId, 'DM-') !== 0 && strpos($gId, 'BCT-') !== 0 && is_numeric($gId)) {
                    $sql = "SELECT 
                                IF(gmrs.read_at IS NOT NULL, 1, 0) as is_read, 
                                gmrs.read_at, 
                                IF(gm.user_role = 'student', s.s_name, sts.sts_name) as s_name, 
                                IF(gm.user_role = 'student', s.s_roll_no, sts.sts_roll_no) as s_roll_no
                            FROM group_members gm
                            LEFT JOIN student s ON gm.user_id = s.s_id AND gm.user_role = 'student'
                            LEFT JOIN short_term_student sts ON gm.user_id = sts.sts_id AND gm.user_role = 'shortterm_student'
                            LEFT JOIN group_message_read_status gmrs ON gmrs.message_id = $msg_id AND gmrs.user_id = gm.user_id AND gmrs.user_role = gm.user_role
                            WHERE gm.group_id = " . intval($gId) . " AND gm.user_role IN ('student', 'shortterm_student')";
                } else {
                    $sql = "SELECT m.is_read, m.read_at, s.s_name, s.s_roll_no 
                            FROM messages m 
                            JOIN student s ON m.receiver_id = s.s_id
                            WHERE m.id = $msg_id";
                }
            } else {
                echo json_encode(['error' => 1, 'message' => 'Message not found']);
                exit;
            }
        } else {
            $bct_id_esc = mysqli_real_escape_string($con, $bct_id);
            $sql = "SELECT m.is_read, m.read_at, s.s_name, s.s_roll_no 
                    FROM messages m 
                    JOIN student s ON m.receiver_id = s.s_id
                    WHERE m.groupId = '$bct_id_esc'";
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
        $include_shortterm = isset($_POST['include_shortterm']) ? intval($_POST['include_shortterm']) : 0;

        if ($include_shortterm) {
            $sql = "SELECT s_id, s_name, s_roll_no, 'student' as s_type FROM student 
                    WHERE (s_name LIKE '%$query%' OR s_roll_no LIKE '%$query%') AND s_status != 'dropout'
                    UNION
                    SELECT sts_id as s_id, sts_name as s_name, sts_roll_no as s_roll_no, 'shortterm_student' as s_type FROM short_term_student
                    WHERE sts_name LIKE '%$query%' OR sts_roll_no LIKE '%$query%'
                    LIMIT 10";
        } else {
            $sql = "SELECT s_id, s_name, s_roll_no, 'student' as s_type FROM student 
                    WHERE (s_name LIKE '%$query%' OR s_roll_no LIKE '%$query%') AND s_status != 'dropout'
                    LIMIT 10";
        }
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
        $s_type = mysqli_real_escape_string($con, $_POST['studentType'] ?? 'student');
        if ($g_id > 0 && $s_id > 0) {
            $stmt = $con->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ? AND user_role = ?");
            $stmt->bind_param("iis", $g_id, $s_id, $s_type);
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
        $s_type = mysqli_real_escape_string($con, $_POST['studentType'] ?? 'student');
        if ($g_id > 0 && $s_id > 0) {
            // Check if already a member
            $check = $con->query("SELECT * FROM group_members WHERE group_id = $g_id AND user_id = $s_id AND user_role = '$s_type'");
            if ($check && $check->num_rows > 0) {
                echo json_encode(['error' => 1, 'message' => 'Student is already a member']);
            } else {
                $stmt = $con->prepare("INSERT INTO group_members (group_id, user_id, user_role) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $g_id, $s_id, $s_type);
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
            $sql = "SELECT gm.user_role, gm.user_id, s.s_name, s.s_roll_no, sts.sts_name as shortterm_name, sts.sts_roll_no as shortterm_roll 
                    FROM group_members gm 
                    LEFT JOIN student s ON gm.user_id = s.s_id AND gm.user_role = 'student'
                    LEFT JOIN short_term_student sts ON gm.user_id = sts.sts_id AND gm.user_role = 'shortterm_student'
                    WHERE gm.group_id = $g_id";
            $res = $con->query($sql);
            $members = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    if ($row['user_role'] === 'student') {
                        $members[] = ['name' => $row['s_name'], 'role' => 'Student', 'info' => $row['s_roll_no'], 'student_id' => $row['user_id'], 'user_role' => 'student'];
                    } else if ($row['user_role'] === 'shortterm_student') {
                        $members[] = ['name' => $row['shortterm_name'], 'role' => 'Short Term Student', 'info' => $row['shortterm_roll'], 'student_id' => $row['user_id'], 'user_role' => 'shortterm_student'];
                    } else {
                        $members[] = ['name' => 'Faculty Admin', 'role' => ucfirst($row['user_role']), 'info' => '-', 'user_role' => $row['user_role']];
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
                $student_types = $_POST['student_types'] ?? [];
                // Manual selection
                $stmt = $con->prepare("INSERT INTO group_members (group_id, user_id, user_role) VALUES (?, ?, ?)");
                foreach ($student_ids as $index => $s_id) {
                    $s_id = intval($s_id);
                    $s_type = isset($student_types[$index]) ? $student_types[$index] : 'student';
                    $stmt->bind_param("iis", $groupId, $s_id, $s_type);
                    $stmt->execute();
                }
                $stmt->close();
            } else if ($course_id && $semester) {
                $sem_num = str_replace('Sem', '', $semester);
                $student_sql = "INSERT INTO group_members (group_id, user_id, user_role) 
                               SELECT ?, s_id, 'student' FROM student 
                               WHERE s_course_id = ? AND s_cur_sem = ? AND s_status != 'dropout'";

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
        ob_start(); // Buffer any stray output (warnings/notices) to protect JSON
        $students_id = $_POST['students_id'] ?? [];
        $template_content = $_POST['template'] ?? '';
        // Accept session_id from JS to include in redirect info
        $sent_session_id = intval($_POST['session_id'] ?? 0);

        $file_path = null;
        $file_name = null;
        $file_type = null;

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/chat_files/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $tmp_name = $_FILES['attachment']['tmp_name'];
            $file_name_original = basename($_FILES['attachment']['name']);
            $file_type = $_FILES['attachment']['type'];

            $ext = pathinfo($file_name_original, PATHINFO_EXTENSION);
            $new_name = 'chat_' . time() . '_' . rand(1000, 9999) . '.' . $ext;

            if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                $file_path = 'uploads/chat_files/' . $new_name;
                $file_name = $file_name_original;
            }
        }

        if (empty($students_id) || empty($template_content)) {
            ob_clean();
            echo json_encode(['error' => 1, 'message' => 'Missing students or template content']);
            exit;
        }

        $success_count = 0;
        $error_count = 0;
        $sent_session_name = '';
        $sent_uni_short = '';

        $broadcast_id = 'BCT-' . time() . '-' . rand(1000, 9999);
        $expo_tokens = [];
        $pushed_tokens = []; // Track to avoid duplicates

        foreach ($students_id as $s_id) {
            $s_id = intval($s_id);
            // Fetch student details
            $std_query = $con->query("SELECT s.*, c.course_name, sm.session_name 
                                     FROM student s 
                                     LEFT JOIN course_master c ON s.s_course_id = c.course_master_id 
                                     LEFT JOIN session_master sm ON s.s_session_id = sm.session_master_id 
                                     WHERE s.s_id = $s_id");

            if ($std_row = $std_query->fetch_assoc()) {
                $personalized_msg = $template_content;
                $roll_no = $std_row['s_roll_no'];

                // -------------------------------------------------------
                // STEP 1: DB-driven placeholder resolution
                // (Same logic as preview_template_msg.php)
                // Extract all {placeholders} from template
                // -------------------------------------------------------
                preg_match_all('/\{[^{}]*\}/', $personalized_msg, $ph_matches);
                $found_words = $ph_matches[0];

                $tabArr = [];
                foreach ($found_words as $ph_value) {
                    $ph_esc = mysqli_real_escape_string($con, $ph_value);
                    $ph_query = $con->query("SELECT t1.table_id, t1.table_field_id, t1.new_field_name,
                                                    t2.table_name, t3.field_name
                                            FROM temp_variable_master AS t1
                                            INNER JOIN table_master AS t2 ON t1.table_id = t2.table_id
                                            INNER JOIN table_field_master AS t3 ON t1.table_field_id = t3.field_id
                                            WHERE t1.new_field_name = '$ph_esc' LIMIT 1");
                    if ($ph_query && $ph_query->num_rows > 0) {
                        $ph_row = $ph_query->fetch_assoc();
                        $tabArr[] = [
                            $ph_value => [
                                'table_name' => $ph_row['table_name'],
                                'field_name' => $ph_row['field_name'],
                                'new_field_name' => $ph_row['new_field_name']
                            ]
                        ];
                    }
                }

                // Resolve each placeholder against the DB
                foreach ($tabArr as $tableArr) {
                    foreach ($tableArr as $tableData) {
                        $table_new_field = $tableData['new_field_name'];
                        $newField = $tableData['field_name'];
                        $tblName = $tableData['table_name'];

                        // Determine which column to use as the WHERE condition
                        $attributeFields = ["std_roll", "s_roll_no", "roll_no", "student_id", "std_id", "s_id", "roll"];
                        $check_field = '';
                        $condition = '';

                        $schema = $con->query("DESCRIBE $tblName");
                        if ($schema) {
                            $columns = [];
                            while ($sr = $schema->fetch_assoc())
                                $columns[] = $sr['Field'];
                            foreach ($attributeFields as $attrName) {
                                if (in_array($attrName, $columns)) {
                                    if ($attrName === 'std_roll') {
                                        $check_field = 'std_roll';
                                        $condition = "$check_field = $roll_no";
                                    } elseif ($attrName === 's_roll_no') {
                                        $check_field = 's_roll_no';
                                        $condition = "$check_field = $roll_no";
                                    } elseif ($attrName === 'student_id') {
                                        $check_field = 'student_id';
                                        $condition = "$check_field = $s_id";
                                    } elseif ($attrName === 'std_id') {
                                        $check_field = 'std_id';
                                        $condition = "$check_field = $s_id";
                                    } elseif ($attrName === 's_id') {
                                        $check_field = 's_id';
                                        $condition = "$check_field = $s_id";
                                    } elseif ($attrName === 'roll') {
                                        $check_field = 'roll';
                                        $condition = "$check_field = $roll_no";
                                    } else {
                                        $check_field = 'roll_no';
                                        $condition = "$check_field = $roll_no";
                                    }
                                    break;
                                }
                            }
                        }

                        if (empty($condition))
                            continue;

                        // Fee fields
                        if ($newField === 'due_fee' || $newField === 'fee_year_name') {
                            $feeQ = $con->query("SELECT $newField FROM $tblName WHERE $condition AND fee_option_status = 'active'");
                            if ($feeQ && $feeQ->num_rows > 0) {
                                $feeRow = $feeQ->fetch_assoc();
                                $personalized_msg = str_replace($table_new_field, $feeRow[$newField] ?? '', $personalized_msg);
                            }
                        }
                        // Course ID → course name
                        elseif (in_array($newField, ['s_course_id', 'course_id', 'subject_course_id'])) {
                            $cQ = $con->query("SELECT t1.$newField, t2.course_name FROM $tblName AS t1
                                              INNER JOIN course_master AS t2 ON t1.$newField = t2.course_master_id
                                              WHERE $condition");
                            if ($cQ && $cQ->num_rows > 0) {
                                $cRow = $cQ->fetch_assoc();
                                $personalized_msg = str_replace($table_new_field, $cRow['course_name'] ?? '', $personalized_msg);
                            }
                        }
                        // Session ID → session name
                        elseif (in_array($newField, ['s_session_id', 'session_id'])) {
                            $sQ = $con->query("SELECT t1.$newField, t2.session_name FROM $tblName AS t1
                                              INNER JOIN session_master AS t2 ON t1.$newField = t2.session_master_id
                                              WHERE $condition");
                            if ($sQ && $sQ->num_rows > 0) {
                                $sRow = $sQ->fetch_assoc();
                                $personalized_msg = str_replace($table_new_field, $sRow['session_name'] ?? '', $personalized_msg);
                            }
                        }
                        // General field
                        else {
                            if ($table_new_field !== '{student_name}') {
                                $gQ = $con->query("SELECT $newField FROM $tblName WHERE $condition");
                                if ($gQ && $gQ->num_rows > 0) {
                                    $gRow = $gQ->fetch_assoc();
                                    $personalized_msg = str_replace($table_new_field, $gRow[$newField] ?? '', $personalized_msg);
                                }
                            }
                        }
                    }
                }

                // -------------------------------------------------------
                // STEP 2: Fallback – simple hardcoded placeholders
                // (handles {student_name} and others not in temp_variable_master)
                // -------------------------------------------------------
                $fallback = [
                    '{student_name}' => $std_row['s_name'],
                    '{roll_no}' => $std_row['s_roll_no'],
                    '{course}' => $std_row['course_name'],
                    '{student_course}' => $std_row['course_name'],
                    '{session}' => $std_row['session_name'],
                    '{father_name}' => $std_row['s_father_name'],
                ];
                foreach ($fallback as $key => $val) {
                    $personalized_msg = str_replace($key, $val ?? '', $personalized_msg);
                }

                // -------------------------------------------------------
                // STEP 3: Save to messages table
                // -------------------------------------------------------
                $stmt = $con->prepare("INSERT INTO messages (sender_id, receiver_id, content, university, session, course, semester, groupId, file_path, file_name, file_type, createdAt) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                $uni = $std_row['s_university_id'];
                $sess = $std_row['s_session_id'];
                $crs = $std_row['s_course_id'];
                $sem = "Sem" . ($std_row['s_cur_sem'] ?? 1);

                // Capture session info from first student for redirect
                if (empty($sent_session_name) && !empty($std_row['session_name'])) {
                    $sent_session_name = $std_row['session_name'];
                    if ($sent_session_id === 0)
                        $sent_session_id = $sess;
                    // Fetch university short name
                    $uni_res = $con->query("SELECT university_short_name FROM university WHERE id = " . intval($uni));
                    if ($uni_res && $ur = $uni_res->fetch_assoc()) {
                        $sent_uni_short = $ur['university_short_name'] ?? '';
                    }
                }

                $stmt->bind_param("iisssssssss", $faculty_id, $s_id, $personalized_msg, $uni, $sess, $crs, $sem, $broadcast_id, $file_path, $file_name, $file_type);
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
                $stmt->close();
            }
        }

        // NEW: Send Push (Expo + Firebase)
        if (!empty($students_id) && class_exists('ExpoHelper')) {
            $notification_title = "New Academic Notice";
            $notification_body = strlen($template_content) > 100 ? substr($template_content, 0, 97) . '...' : $template_content;
            ExpoHelper::routeNotification($con, $students_id, $notification_title, $notification_body, ['type' => 'notice', 'broadcast_id' => $broadcast_id, 'channelId' => 'default']);
        }

        ob_clean(); // Discard any stray output before sending JSON
        echo json_encode([
            'error' => 0,
            'message' => "Successfully sent to $success_count students.",
            'session_id' => $sent_session_id,
            'session_name' => $sent_session_name,
            'uni_short' => $sent_uni_short,
        ]);
        exit;
    }

    if ($type === 'GET_SESSION_COURSES') {
        $session_id = intval($_POST['session_id'] ?? 0);
        $exclusion_key = "SES-" . $session_id;
        $sql = "SELECT c.course_master_id as id, c.course_short_name as sname, c.course_name as name, COUNT(s.s_id) as student_count 
                FROM student s 
                JOIN course_master c ON s.s_course_id = c.course_master_id 
                WHERE s.s_session_id = $session_id AND s.s_status != 'dropout'
                AND s.s_id NOT IN (SELECT student_id FROM chat_group_exclusions WHERE group_key = '$exclusion_key')
                GROUP BY c.course_master_id 
                ORDER BY student_count DESC";
        $res = $con->query($sql);
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc())
                $data[] = $row;
        }
        echo json_encode(['error' => 0, 'data' => $data]);
        exit;
    }

    if ($type === 'GET_COURSE_STUDENTS') {
        $session_id = intval($_POST['session_id'] ?? 0);
        $course_id = intval($_POST['course_id'] ?? 0);
        $exclusion_key = "SES-" . $session_id;

        $sql = "SELECT s_id, s_name, s_roll_no FROM student 
                WHERE s_session_id = $session_id AND s_course_id = $course_id AND s_status != 'dropout'
                AND s_id NOT IN (SELECT student_id FROM chat_group_exclusions WHERE group_key = '$exclusion_key')
                ORDER BY s_name ASC";
        $res = $con->query($sql);
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc())
                $data[] = $row;
        }
        echo json_encode(['error' => 0, 'data' => $data]);
        exit;
    }

    if ($type === 'REMOVE_FROM_AUTO_GROUP') {
        $s_id = intval($_POST['student_id'] ?? 0);
        $session_id = intval($_POST['session_id'] ?? 0);

        if ($s_id > 0 && $session_id > 0) {
            // Fetch student name and roll for logging
            $std_info = $con->query("SELECT s_name, s_roll_no FROM student WHERE s_id = $s_id")->fetch_assoc();
            $s_name = $std_info['s_name'] ?? 'Unknown';
            $s_roll = $std_info['s_roll_no'] ?? '-';

            $group_key = "SES-" . $session_id;
            $stmt = $con->prepare("INSERT IGNORE INTO chat_group_exclusions (student_id, student_name, student_roll, group_key, excluded_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $s_id, $s_name, $s_roll, $group_key);
            if ($stmt->execute()) {
                echo json_encode(['error' => 0, 'message' => 'Student excluded from this session group']);
            } else {
                echo json_encode(['error' => 1, 'message' => $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 1, 'message' => 'Invalid Data']);
        }
        exit;
    }

    // --- NEW: FACULTY CHAT SYSTEM HANDLERS (Option B) ---
    if ($type === 'GET_FACULTY_MESSAGES') {
        $groupId = $_POST['groupId'] ?? '';
        $deptId = intval($_POST['department_id'] ?? 0);

        $sql = "SELECT m.*, f.faculty_name as sender_name, f.faculty_short_name as sender_short,
                fr.faculty_name as recipient_name
                FROM faculty_messages m 
                LEFT JOIN faculty_master f ON m.sender_id = f.faculty_master_id
                LEFT JOIN faculty_master fr ON m.receiver_id = fr.faculty_master_id
                WHERE 1=1";

        if (!empty($groupId)) {
            $g = mysqli_real_escape_string($con, $groupId);
            if (strpos($g, 'DM-') === 0) {
                $sql .= " AND m.groupId = '$g'";
            } else {
                $g_id = intval($g);
                $sql .= " AND m.groupId = '$g_id'";
            }
        } elseif ($deptId > 0) {
            $sql .= " AND (m.department_id = $deptId OR m.groupId = 'DEP-$deptId')";
        } else {
            $sql .= " AND (m.groupId IS NULL OR m.groupId = '0' OR m.groupId = '')";
        }
        $sql .= " ORDER BY m.createdAt ASC";

        if (!empty($groupId)) {
            $g = mysqli_real_escape_string($con, $groupId);
            $con->query("UPDATE faculty_messages SET is_read = 1, read_at = NOW() WHERE groupId = '$g' AND sender_id != $faculty_id AND is_read = 0");
        } elseif ($deptId > 0) {
            $con->query("UPDATE faculty_messages SET is_read = 1, read_at = NOW() WHERE (groupId = 'DEP-$deptId' OR department_id = $deptId) AND sender_id != $faculty_id AND is_read = 0");
        }

        $result = $con->query($sql);
        $messages = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
        }
        echo json_encode(['error' => 0, 'data' => $messages]);
        exit;
    }

    if ($type === 'SEND_FACULTY_MESSAGE') {
        $content = $_POST['content'] ?? '';
        $groupId = $_POST['groupId'] ?? '';
        $deptId = intval($_POST['department_id'] ?? 0);

        if (empty($content)) {
            echo json_encode(['error' => 1, 'message' => 'Message content is empty']);
            exit;
        }

        if (empty($groupId) && $deptId > 0) {
            $g_id = "DEP-" . $deptId;
            $stmt = $con->prepare("INSERT INTO faculty_messages (sender_id, content, department_id, groupId, createdAt) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isis", $faculty_id, $content, $deptId, $g_id);
            if ($stmt->execute()) {
                $tok_query = $con->query("SELECT push_token FROM user_push_tokens WHERE user_role = 'faculty' AND user_id IN (SELECT faculty_master_id FROM faculty_master WHERE faculty_department_id = $deptId AND faculty_master_id != $faculty_id)");
                $tokens = [];
                if ($tok_query) {
                    while ($r = $tok_query->fetch_assoc())
                        $tokens[] = $r['push_token'];
                }
                if (!empty($tokens) && class_exists('ExpoHelper')) {
                    $body = strlen($content) > 100 ? substr($content, 0, 97) . '...' : $content;
                    ExpoHelper::sendNotification($tokens, "Department Announcement", $body, ['type' => 'fac_dept', 'groupId' => $g_id]);
                }
                echo json_encode(['error' => 0, 'message' => 'Broadcast sent']);
            } else {
                echo json_encode(['error' => 1, 'message' => $stmt->error]);
            }
            $stmt->close();
            exit;
        }

        if (!empty($groupId)) {
            $g_id = mysqli_real_escape_string($con, $groupId);
            if (strpos($g_id, 'DM-') === 0) {
                $parts = explode('-', $g_id);
                $receiver_id = 0;
                foreach ($parts as $part) {
                    if (strpos($part, 'F') === 0) {
                        $id_val = intval(substr($part, 1));
                        if ($id_val != $faculty_id) {
                            $receiver_id = $id_val;
                            break;
                        }
                    }
                }
                if ($receiver_id == 0 && count($parts) >= 3) {
                    $receiver_id = intval(substr($parts[2], 1));
                }

                $stmt = $con->prepare("INSERT INTO faculty_messages (sender_id, receiver_id, content, groupId, createdAt) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("iiss", $faculty_id, $receiver_id, $content, $g_id);
                if ($stmt->execute()) {
                    $tok_query = $con->query("SELECT push_token FROM user_push_tokens WHERE user_id = $receiver_id AND user_role = 'faculty'");
                    $tokens = [];
                    if ($tok_query) {
                        while ($r = $tok_query->fetch_assoc())
                            $tokens[] = $r['push_token'];
                    }
                    if (!empty($tokens) && class_exists('ExpoHelper')) {
                        $body = strlen($content) > 100 ? substr($content, 0, 97) . '...' : $content;
                        ExpoHelper::sendNotification($tokens, "New message from $faculty_name", $body, ['type' => 'fac_dm', 'groupId' => $g_id]);
                    }
                    echo json_encode(['error' => 0, 'message' => 'DM sent']);
                } else {
                    echo json_encode(['error' => 1, 'message' => $stmt->error]);
                }
                $stmt->close();
            } else {
                $g_id_int = intval($groupId);
                $stmt = $con->prepare("INSERT INTO faculty_messages (sender_id, content, groupId, createdAt) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iss", $faculty_id, $content, $g_id_int);
                if ($stmt->execute()) {
                    $gn_query = $con->query("SELECT group_name FROM faculty_chat_groups WHERE id = $g_id_int");
                    $g_name = ($gn_query && $r = $gn_query->fetch_assoc()) ? $r['group_name'] : 'Faculty Group';

                    $m_query = $con->query("SELECT user_id FROM faculty_group_members WHERE group_id = $g_id_int AND user_id != $faculty_id");
                    $m_ids = [];
                    if ($m_query) {
                        while ($r = $m_query->fetch_assoc())
                            $m_ids[] = $r['user_id'];
                    }
                    if (!empty($m_ids)) {
                        $ids_str = implode(',', array_map('intval', $m_ids));
                        $tok_query = $con->query("SELECT push_token FROM user_push_tokens WHERE user_role = 'faculty' AND user_id IN ($ids_str)");
                        $tokens = [];
                        if ($tok_query) {
                            while ($r = $tok_query->fetch_assoc())
                                $tokens[] = $r['push_token'];
                        }
                        if (!empty($tokens) && class_exists('ExpoHelper')) {
                            $body = strlen($content) > 100 ? substr($content, 0, 97) . '...' : $content;
                            ExpoHelper::sendNotification($tokens, "New message in $g_name", $body, ['type' => 'fac_group', 'groupId' => $g_id_int]);
                        }
                    }
                    echo json_encode(['error' => 0, 'message' => 'Group message sent']);
                } else {
                    echo json_encode(['error' => 1, 'message' => $stmt->error]);
                }
                $stmt->close();
            }
            exit;
        }

        echo json_encode(['error' => 1, 'message' => 'Invalid destination']);
        exit;
    }

    if ($type === 'SEARCH_FACULTY') {
        $query = mysqli_real_escape_string($con, $_POST['query'] ?? '');
        $sql = "SELECT faculty_master_id as id, faculty_name as name, faculty_id as code 
                FROM faculty_master 
                WHERE (faculty_name LIKE '%$query%' OR faculty_id LIKE '%$query%')
                AND faculty_status = 'Active' AND Current_faculty = 'Current' AND faculty_master_id != $faculty_id
                LIMIT 10";
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

    if ($type === 'CREATE_FACULTY_GROUP') {
        $name = mysqli_real_escape_string($con, $_POST['group_name'] ?? '');
        $faculty_ids = $_POST['faculty_ids'] ?? [];

        $con->begin_transaction();
        try {
            $stmt = $con->prepare("INSERT INTO faculty_chat_groups (group_name, created_by) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $faculty_id);
            $stmt->execute();
            $groupId = $con->insert_id;
            $stmt->close();

            $stmt = $con->prepare("INSERT INTO faculty_group_members (group_id, user_id, user_role) VALUES (?, ?, 'faculty')");
            $stmt->bind_param("ii", $groupId, $faculty_id);
            $stmt->execute();

            foreach ($faculty_ids as $fid) {
                $fid = intval($fid);
                $stmt->bind_param("ii", $groupId, $fid);
                $stmt->execute();
            }
            $stmt->close();

            $con->commit();
            echo json_encode(['error' => 0, 'message' => 'Faculty group created successfully']);
        } catch (Exception $e) {
            $con->rollback();
            echo json_encode(['error' => 1, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($type === 'EDIT_FACULTY_GROUP') {
        $g_id = intval($_POST['groupId'] ?? 0);
        $newName = $_POST['group_name'] ?? '';
        if ($g_id > 0 && !empty($newName)) {
            $stmt = $con->prepare("UPDATE faculty_chat_groups SET group_name = ? WHERE id = ?");
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

    if ($type === 'DELETE_FACULTY_GROUP') {
        $g_id = intval($_POST['groupId'] ?? 0);
        if ($g_id > 0) {
            $stmt = $con->prepare("DELETE FROM faculty_chat_groups WHERE id = ? AND created_by = ?");
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

    if ($type === 'GET_FACULTY_GROUP_MEMBERS') {
        $g_id = intval($_POST['groupId'] ?? 0);
        if ($g_id > 0) {
            $sql = "SELECT gm.user_id, f.faculty_name, f.faculty_id 
                    FROM faculty_group_members gm 
                    LEFT JOIN faculty_master f ON gm.user_id = f.faculty_master_id
                    WHERE gm.group_id = $g_id";
            $res = $con->query($sql);
            $members = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $members[] = [
                        'name' => $row['faculty_name'],
                        'role' => 'Faculty',
                        'info' => $row['faculty_id'],
                        'student_id' => $row['user_id']
                    ];
                }
            }
            echo json_encode(['error' => 0, 'data' => $members]);
        } else {
            echo json_encode(['error' => 1, 'message' => 'Invalid Group']);
        }
        exit;
    }

    if ($type === 'ADD_FACULTY_GROUP_MEMBER') {
        $g_id = intval($_POST['groupId'] ?? 0);
        $fid = intval($_POST['facultyId'] ?? 0);
        if ($g_id > 0 && $fid > 0) {
            $check = $con->query("SELECT * FROM faculty_group_members WHERE group_id = $g_id AND user_id = $fid");
            if ($check && $check->num_rows > 0) {
                echo json_encode(['error' => 1, 'message' => 'Faculty member is already in the group']);
            } else {
                $stmt = $con->prepare("INSERT INTO faculty_group_members (group_id, user_id, user_role) VALUES (?, ?, 'faculty')");
                $stmt->bind_param("ii", $g_id, $fid);
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

    if ($type === 'REMOVE_FACULTY_GROUP_MEMBER') {
        $g_id = intval($_POST['groupId'] ?? 0);
        $fid = intval($_POST['facultyId'] ?? 0);
        if ($g_id > 0 && $fid > 0) {
            $stmt = $con->prepare("DELETE FROM faculty_group_members WHERE group_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $g_id, $fid);
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

    if ($type === 'GET_DEPT_FACULTY') {
        $deptId = intval($_POST['department_id'] ?? 0);
        $sql = "SELECT faculty_master_id as id, faculty_name as name, faculty_id as code 
                FROM faculty_master 
                WHERE faculty_department_id = $deptId AND faculty_status = 'Active' AND Current_faculty = 'Current'
                ORDER BY faculty_name ASC";
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
$res = $con->query("
    SELECT s.session_master_id as id, s.session_name as name, u.university_short_name as uni_short,
           (SELECT content FROM messages WHERE session = CAST(s.session_master_id AS CHAR) ORDER BY createdAt DESC LIMIT 1) as last_msg,
           (SELECT createdAt FROM messages WHERE session = CAST(s.session_master_id AS CHAR) ORDER BY createdAt DESC LIMIT 1) as last_msg_time
    FROM session_master s 
    LEFT JOIN university u ON s.university_id = u.id 
    ORDER BY last_msg_time DESC, s.session_master_id DESC
");
if ($res)
    while ($row = $res->fetch_assoc())
        $allSessions[] = $row;

$session_courses = [];
if (!empty($allSessions)) {
    $session_ids = array_column($allSessions, 'id');
    $ids_str = implode(',', array_map('intval', $session_ids));
    $courses_sql = "SELECT s.s_session_id as session_id, c.course_short_name as sname, COUNT(s.s_id) as student_count 
                    FROM student s 
                    JOIN course_master c ON s.s_course_id = c.course_master_id 
                    WHERE s.s_session_id IN ($ids_str) AND s.s_status != 'dropout'
                    AND s.s_id NOT IN (SELECT student_id FROM chat_group_exclusions WHERE group_key = CONCAT('SES-', s.s_session_id))
                    GROUP BY s.s_session_id, c.course_master_id 
                    ORDER BY student_count DESC";
    $c_res = $con->query($courses_sql);
    if ($c_res) {
        while ($cr = $c_res->fetch_assoc()) {
            $session_courses[$cr['session_id']][] = $cr;
        }
    }
}

$groups = [];
$res = $con->query("
    SELECT g.*, 
           (SELECT content FROM messages WHERE groupId = CAST(g.id AS CHAR) ORDER BY createdAt DESC LIMIT 1) as last_msg,
           (SELECT createdAt FROM messages WHERE groupId = CAST(g.id AS CHAR) ORDER BY createdAt DESC LIMIT 1) as last_msg_time 
    FROM chat_groups g 
    JOIN group_members m ON g.id = m.group_id 
    WHERE m.user_id = '$faculty_id' AND m.user_role = 'faculty'
    ORDER BY last_msg_time DESC, g.id DESC
");
if ($res)
    while ($row = $res->fetch_assoc())
        $groups[] = $row;

// --- NEW: FACULTY CHAT INITIALIZATION (Option B) ---
$is_admin = (isset($_SESSION["giit_admin_department"]) && (string) $_SESSION["giit_admin_department"] === '99');
$faculty_groups = [];
$departments = [];
$active_faculty_list = [];

if ($is_admin) {
    // Fetch all active faculty members
    $res = $con->query("SELECT faculty_master_id as id, faculty_name as name, faculty_id as code FROM faculty_master WHERE faculty_status = 'Active' AND Current_faculty = 'Current' AND faculty_master_id != '$faculty_id' ORDER BY faculty_name ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $active_faculty_list[] = $row;
        }
    }

    // Fetch all departments
    $res = $con->query("SELECT department_master_id as id, department_name as name, department_short_name as sname FROM department_master ORDER BY department_name ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $departments[] = $row;
        }
    }
}

// Fetch faculty groups current user is a member of
$res = $con->query("SELECT g.* FROM faculty_chat_groups g 
                      JOIN faculty_group_members m ON g.id = m.group_id 
                      WHERE m.user_id = '$faculty_id'");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $faculty_groups[] = $row;
    }
}

$dms = [];
$res = $con->query("
    SELECT m.groupId, MAX(m.createdAt) as last_msg_time,
           (SELECT content FROM messages WHERE groupId = m.groupId ORDER BY createdAt DESC LIMIT 1) as last_msg,
           s.s_name, s.s_id, s.s_roll_no
    FROM messages m
    JOIN student s ON m.groupId = CONCAT('DM-F', '$faculty_id', '-S', s.s_id)
    WHERE m.groupId LIKE 'DM-F$faculty_id-S%'
    GROUP BY m.groupId
    ORDER BY last_msg_time DESC
");
if ($res) {
    while ($row = $res->fetch_assoc())
        $dms[] = $row;
}

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
$PAGE_NAME = "GIIT MESSANGER";
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

    :root {
        --primary: #6366f1;
        --primary-light: #eef2ff;
        --primary-hover: #4f46e5;
        --accent-purple: #a855f7;
        --accent-pink: #ec4899;
        --accent-orange: #f97316;
        --accent-teal: #14b8a6;
        --secondary: #64748b;
        --success: #22c55e;
        --danger: #ef4444;
        --warning: #f59e0b;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --bg-sidebar: #f8fafc;
        --bg-main: #ffffff;
        --border-soft: #f1f5f9;
        --border-medium: #e2e8f0;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        --grad-primary: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
    }

    .chat-wrapper {
        font-family: 'Inter', sans-serif;
        padding: 0;
        background: #f1f5f9;
    }

    .chat-container {
        display: flex;
        height: calc(100vh - 65px);
        /* Adjusted to fit below site header */
        background: var(--bg-main);
        border: none;
        border-top: 4px solid #6366f1;
        border-radius: 0;
        overflow: hidden;
        margin-top: 0;
        box-shadow: none;
        position: relative;
    }

    .chat-container::before {
        content: '';
        position: absolute;
        top: -4px;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--grad-primary);
        z-index: 100;
    }

    .chat-sidebar {
        width: 400px;
        border-right: 1px solid var(--border-medium);
        display: flex;
        flex-direction: column;
        background: var(--bg-sidebar);
    }

    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: var(--bg-main);
    }

    .sidebar-header {
        padding: 24px 20px;
        background: var(--bg-main);
        color: var(--text-main);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-medium);
    }

    .sidebar-header strong {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--primary);
        letter-spacing: -0.025em;
    }

    .sidebar-header .header-btn {
        background-color: var(--primary-light);
        border: none;
        color: var(--primary);
        border-radius: 10px;
        padding: 10px 14px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .sidebar-header .header-btn:hover {
        background-color: var(--primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }

    .chat-list {
        flex: 1;
        overflow-y: auto;
        padding: 15px 12px;
    }

    .chat-list::-webkit-scrollbar {
        width: 5px;
    }

    .chat-list::-webkit-scrollbar-thumb {
        background: var(--border-medium);
        border-radius: 10px;
    }

    .chat-item {
        padding: 14px 16px;
        border-radius: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 10px;
        background: var(--bg-main);
        border: 1px solid transparent;
        box-shadow: var(--shadow-sm);
    }

    .chat-item:hover {
        background: #ffffff;
        border-color: var(--primary-light);
        transform: translateX(4px);
        box-shadow: var(--shadow-md);
    }

    .chat-item.active {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(168, 85, 247, 0.05) 100%);
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-left: 5px solid #6366f1;
    }

    .chat-item-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background-color: var(--primary-light);
        color: var(--primary);
        margin-right: 15px;
        font-size: 1.15rem;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }

    .chat-item:hover .chat-item-icon {
        background-color: var(--primary);
        color: white;
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
        font-weight: 800;
        color: var(--text-main);
        font-size: 1.15rem;
        letter-spacing: -0.02em;
    }

    .chat-header .text-muted {
        font-size: 0.8rem;
        color: var(--text-muted);
        font-weight: 500;
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
        color: var(--text-main);
        border: 1px solid var(--border-medium);
        border-bottom-left-radius: 4px;
        box-shadow: var(--shadow-sm);
    }

    .message.sent {
        align-self: flex-end;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
        color: white;
        border-bottom-right-radius: 4px;
        box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
    }

    .message-footer {
        font-size: 0.7rem;
        margin-top: 8px;
        text-align: right;
        font-weight: 500;
        display: block;
    }

    .message.sent .message-footer {
        color: rgba(255, 255, 255, 0.85);
    }

    .message.received .message-footer {
        color: var(--text-muted);
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
        border-radius: 10px;
        border: 1px solid var(--border-medium);
        padding: 10px 14px;
        font-size: 0.9rem;
        background-color: var(--bg-sidebar);
        color: var(--text-main);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        width: 100%;
        font-weight: 500;
    }

    .filter-grid select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px var(--primary-light);
        background-color: white;
        outline: none;
    }

    .composer-input-wrapper {
        display: flex;
        gap: 15px;
        align-items: flex-end;
        background: var(--bg-sidebar);
        border: 1px solid var(--border-medium);
        border-radius: 16px;
        padding: 12px 14px 12px 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
        color: white;
        border: none;
        border-radius: 12px;
        height: 44px;
        padding: 0 24px;
        font-weight: 700;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }

    .send-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
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
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
        border: none;
        border-radius: 10px;
        padding: 10px 24px;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
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

    .role-student {
        background: #eff6ff;
        color: #3b82f6;
    }

    .role-faculty {
        background: #fff7ed;
        color: #ea580c;
    }

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

    .filter-container {
        display: flex;
        background: #f1f5f9;
        padding: 5px;
        border-radius: 12px;
        gap: 2px;
        margin-top: 15px;
    }

    .filter-btn {
        flex: 1;
        text-align: center;
        padding: 10px 4px;
        cursor: pointer;
        border: none;
        background: transparent;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 600;
        font-size: 0.85rem;
        border-radius: 8px;
        color: #64748b;
    }

    .filter-btn:hover {
        color: #1e293b;
    }

    .filter-btn.active {
        background: #ffffff;
        color: var(--primary);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    }

    .g-mode-btn:first-child {
        border-radius: 8px 0 0 8px;
    }

    .g-mode-btn:last-child {
        border-radius: 0 8px 8px 0;
        margin-left: -1px;
    }

    /* Course Badge Styles */
    .course-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
        margin-right: 5px;
        margin-top: 5px;
    }

    .course-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .course-badge.blue {
        background: #eff6ff;
        color: #4f46e5;
        border-color: #c7d2fe;
    }

    .course-badge.green {
        background: #ecfdf5;
        color: #059669;
        border-color: #a7f3d0;
    }

    .course-badge.purple {
        background: #f5f3ff;
        color: #7c3aed;
        border-color: #ddd6fe;
    }

    .course-badge.orange {
        background: #fff7ed;
        color: #ea580c;
        border-color: #fed7aa;
    }

    .course-badge.red {
        background: #fef2f2;
        color: #dc2626;
        border-color: #fecaca;
    }

    .student-count {
        background: rgba(255, 255, 255, 0.8);
        padding: 1px 6px;
        border-radius: 10px;
        font-size: 0.65rem;
    }
</style>

<div class="container-fluid chat-wrapper">
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="chat-sidebar">
            <div class="sidebar-header" style="flex-direction: column; align-items: stretch; gap: 5px; padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <strong style="font-size: 1.3rem;">GIITChat</strong>
                    <div style="display: flex; gap: 8px;">
                        <button class="header-btn" onclick="$('#groupModal').modal('show')" title="New Group"
                            style="font-weight: 700; font-size: 0.85rem; padding: 8px 12px;">
                            <i class="fa fa-users" style="margin-right: 5px;"></i> New Group
                        </button>
                        <button class="header-btn" onclick="$('#dmModal').modal('show')" title="New Direct Message"
                            style="font-weight: 700; font-size: 0.85rem; padding: 8px 12px;">
                            <i class="fa fa-user-plus" style="margin-right: 5px;"></i> Direct Msg
                        </button>
                    </div>
                </div>
                <div class="filter-container">
                    <button class="filter-btn active" onclick="filterSidebar('all', this)" id="flt-all">All</button>
                    <button class="filter-btn" onclick="filterSidebar('broadcast', this)"
                        id="flt-broadcast">Broadcasts</button>
                    <button class="filter-btn" onclick="filterSidebar('admin', this)" id="flt-admin">Admin Grps</button>
                    <button class="filter-btn" onclick="filterSidebar('dm', this)" id="flt-dm">Direct Msgs</button>
                </div>
            </div>
            <div class="chat-list">
                <div class="chat-item active chat-item-broadcast"
                    onclick="selectChannel(null, 'Academic Broadcast', event)">
                    <div style="display: flex; align-items: center;">
                        <div class="chat-item-icon"
                            style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); color: white;"><i
                                class="fa fa-bullhorn"></i></div>
                        <div class="chat-item-content">
                            <div class="chat-item-title">Click here to send manually</div>
                            <div class="chat-item-subtitle">Send notices to classes</div>
                        </div>
                    </div>
                </div>
                <div class="sidebar-section-label sidebar-section-admin"
                    style="padding: 12px 20px 5px; font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; border-top: 1px solid #f1f5f9; margin-top: 10px;">
                    <b>Groups Made by Admin</b>
                </div>
                <?php foreach ($groups as $g): ?>
                    <div class="chat-item chat-item-admin"
                        onclick="selectChannel(<?= $g['id'] ?>, '<?= addslashes($g['group_name']) ?>', event)">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; flex: 1; overflow: hidden;">
                                <div class="chat-item-icon"
                                    style="background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); color: white;"><i
                                        class="fa fa-users"></i></div>
                                <div class="chat-item-content">
                                    <div class="chat-item-title"><?= htmlspecialchars($g['group_name']) ?></div>
                                    <div class="chat-item-subtitle"
                                        style="display: flex; justify-content: space-between; gap: 5px;">
                                        <span
                                            style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= !empty($g['last_msg']) ? htmlspecialchars($g['last_msg']) : 'Custom Group Chat' ?>
                                        </span>
                                        <?php if (!empty($g['last_msg_time'])): ?>
                                            <span
                                                style="font-size: 0.65rem; color: #94a3b8; flex-shrink: 0; margin-left: 5px;"><?= date('H:i', strtotime($g['last_msg_time'])) ?></span>
                                        <?php endif; ?>
                                    </div>
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

                <div class="sidebar-section-label sidebar-section-broadcast"
                    style="padding: 12px 20px 5px; font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; border-top: 1px solid #f1f5f9; margin-top: 10px;">
                    Existing broadcast</div>
                <?php foreach ($allSessions as $s): ?>
                    <div class="chat-item chat-item-broadcast"
                        onclick="selectChannel('SES-<?= $s['id'] ?>', 'Session: <?= addslashes($s['name']) ?> (<?= addslashes($s['uni_short'] ?? '') ?>)', event)">
                        <div style="display: flex; align-items: center;">
                            <div class="chat-item-icon"
                                style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;"><i
                                    class="fa fa-calendar"></i></div>
                            <div class="chat-item-content">
                                <div class="chat-item-title"><?= htmlspecialchars($s['name']) ?>
                                    (<?= htmlspecialchars($s['uni_short'] ?? '') ?>)</div>
                                <div class="chat-item-subtitle"
                                    style="display: flex; justify-content: space-between; gap: 5px;">
                                    <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= !empty($s['last_msg']) ? htmlspecialchars($s['last_msg']) : 'Auto Group (Session Based Broadcast)' ?>
                                    </span>
                                    <?php if (!empty($s['last_msg_time'])): ?>
                                        <span
                                            style="font-size: 0.65rem; color: #94a3b8; flex-shrink: 0; margin-left: 5px;"><?= date('H:i', strtotime($s['last_msg_time'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (isset($session_courses[$s['id']]) && !empty($session_courses[$s['id']])): ?>
                                    <div style="display: flex; gap: 4px; margin-top: 6px; flex-wrap: wrap;">
                                        <?php
                                        $colors = ['#eff6ff', '#f0fdf4', '#fdf4ff', '#fff7ed', '#fef2f2'];
                                        $text_colors = ['#3b82f6', '#10b981', '#a855f7', '#f97316', '#ef4444'];
                                        foreach ($session_courses[$s['id']] as $idx => $sc):
                                            $c_idx = $idx % count($colors);
                                            ?>
                                            <span
                                                style="font-size: 0.65rem; background: <?= $colors[$c_idx] ?>; color: <?= $text_colors[$c_idx] ?>; border: 1px solid <?= $text_colors[$c_idx] ?>40; padding: 2px 6px; border-radius: 12px; font-weight: 600;">
                                                <?= htmlspecialchars($sc['sname']) ?> <span
                                                    style="opacity: 0.7; margin-left: 2px;"><?= $sc['student_count'] ?></span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="sidebar-section-label sidebar-section-dm"
                    style="padding: 12px 20px 5px; font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; border-top: 1px solid #f1f5f9; margin-top: 10px;">
                    Direct Messages</div>
                <?php foreach ($dms as $dm): ?>
                    <div class="chat-item chat-item-dm"
                        onclick="selectChannel('<?= htmlspecialchars($dm['groupId']) ?>', 'Chat: <?= addslashes($dm['s_name']) ?>', event)">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; flex: 1; overflow: hidden;">
                                <div class="chat-item-icon"
                                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                                    <i class="fa fa-user"></i>
                                </div>
                                <div class="chat-item-content">
                                    <div class="chat-item-title"><?= htmlspecialchars($dm['s_name']) ?></div>
                                    <div class="chat-item-subtitle"
                                        style="display: flex; justify-content: space-between; gap: 5px;">
                                        <span
                                            style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= !empty($dm['last_msg']) ? htmlspecialchars($dm['last_msg']) : 'No messages yet' ?>
                                        </span>
                                        <?php if (!empty($dm['last_msg_time'])): ?>
                                            <span
                                                style="font-size: 0.65rem; color: #94a3b8; flex-shrink: 0; margin-left: 5px;"><?= date('H:i', strtotime($dm['last_msg_time'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Area -->
        <div class="chat-main">
            <div class="chat-header"
                style="padding: 10px 20px; border-bottom: 1px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; cursor: pointer; flex: 1; padding: 5px; border-radius: 8px; transition: background 0.2s;"
                    onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'"
                    onclick="openCurrentGroupMembers()" title="View Group Details">
                    <div id="header-avatar"
                        style="width: 42px; height: 42px; border-radius: 12px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-size: 1.2rem; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);">
                        <i class="fa fa-bullhorn" id="header-icon"></i>
                    </div>
                    <div>
                        <h4 id="active-title"
                            style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                            Academic Broadcast
                            <i class="fa fa-chevron-right" style="font-size: 0.7rem; color: #94a3b8;"></i>
                        </h4>
                        <div class="text-muted" id="active-subtitle" style="font-size: 0.8rem; margin-top: 2px;">Select
                            filters to broadcast to specific classes</div>
                        <div id="course-buttons-container" style="display: flex; flex-wrap: wrap; margin-top: 8px;">
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="header-btn" style="background: #f1f5f9; color: #64748b;"
                        onclick="openCurrentGroupMembers()">
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
                        <!-- Attachment Preview -->
                        <div id="attachment-preview"
                            style="display: none; align-items: center; gap: 8px; background: #e0e7ff; padding: 6px 12px; border-radius: 12px; font-size: 0.8rem; color: #3b82f6; width: fit-content; margin-bottom: 8px; border: 1px solid #c7d2fe;">
                            <i class="fa fa-file"></i> <span id="attachment-name"></span>
                            <i class="fa fa-times" style="cursor: pointer; color: #ef4444; margin-left: 8px;"
                                onclick="clearAttachment()"></i>
                        </div>
                        <div class="composer-input-wrapper">
                            <label for="chat-attachment"
                                style="cursor: pointer; margin: 0; padding: 10px; color: #64748b; border-radius: 50%; transition: background 0.2s;"
                                onmouseover="this.style.background='#f1f5f9'"
                                onmouseout="this.style.background='transparent'" title="Attach File">
                                <i class="fa fa-paperclip" style="font-size: 1.2rem;"></i>
                            </label>
                            <input type="file" id="chat-attachment" style="display: none;"
                                onchange="updateAttachmentPreview(this)">

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
                            <header
                                style="padding: 14px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px 12px 0 0;">
                                <h5 style="margin:0; font-weight: 700; color: #fff; letter-spacing: 0.3px;"><i
                                        class="fa fa-users" style="margin-right: 8px;"></i> Student Selection</h5>
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
                                        <tr
                                            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff;">
                                            <th style="width: 50px; text-align: center; padding: 13px 10px;">
                                                <input type="checkbox" id="checkAll" class="w3-check"
                                                    onclick="enabledBtn()">
                                            </th>
                                            <th
                                                style="padding: 13px 10px; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">
                                                S no.</th>
                                            <th
                                                style="padding: 13px 10px; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">
                                                Name</th>
                                            <th
                                                style="padding: 13px 10px; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">
                                                Roll</th>
                                            <th
                                                style="padding: 13px 10px; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">
                                                Course</th>
                                            <th
                                                style="padding: 13px 10px; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">
                                                Session</th>
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
                            <header
                                style="padding: 14px 20px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                                <h5 style="margin:0; font-weight: 700; color: #fff;"><i class="fa fa-address-book"
                                        style="margin-right: 8px;"></i> Target Contacts</h5>
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
                            <header
                                style="padding: 14px 20px; background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);">
                                <h5 style="margin:0; font-weight: 700; color: #fff;"><i class="fa fa-file-text-o"
                                        style="margin-right: 8px;"></i> Message Template</h5>
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

                                <!-- Unified File Attachment -->
                                <div class="w3-margin-top">
                                    <div id="bc-attachment-preview"
                                        style="display: none; align-items: center; gap: 8px; background: #e0e7ff; padding: 8px 14px; border-radius: 10px; font-size: 0.82rem; color: #4f46e5; margin-bottom: 10px; border: 1px solid #c7d2fe;">
                                        <i class="fa fa-file-o"></i>
                                        <span id="bc-attachment-name"
                                            style="font-weight: 600; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                                        <i class="fa fa-times" style="cursor: pointer; color: #ef4444;"
                                            onclick="clearBcAttachment()"></i>
                                    </div>
                                    <label for="bc-attachment"
                                        style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; border: 2px dashed #c7d2fe; border-radius: 12px; cursor: pointer; background: #f5f7ff; transition: all 0.25s; color: #4f46e5; font-weight: 600; font-size: 0.85rem;"
                                        onmouseover="this.style.background='#eef2ff'; this.style.borderColor='#818cf8'"
                                        onmouseout="this.style.background='#f5f7ff'; this.style.borderColor='#c7d2fe'">
                                        <i class="fa fa-paperclip" style="font-size: 1.1rem;"></i> Attach File (Image /
                                        PDF / Doc)
                                        <input type="file" id="bc-attachment"
                                            accept="image/*,application/pdf,.doc,.docx" style="display:none;"
                                            onchange="updateBcAttachmentPreview(this)">
                                    </label>
                                </div>

                                <button id="previewBtn" onclick="$('#previewModel').modal('show')" disabled="true"
                                    class="w3-button w3-block w3-blue w3-round-large w3-margin-top w3-large"
                                    style="height: 50px; font-weight: 700; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; letter-spacing: 0.5px; box-shadow: 0 4px 15px rgba(102,126,234,0.4); transition: all 0.3s;">
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
                <button type="button" class="close" data-dismiss="modal"
                    onclick="$('#broadcastStatsModal').modal('hide')">&times;</button>
                <h4 class="modal-title"><i class="fa fa-bar-chart"></i> Broadcast Read Receipts</h4>
            </div>
            <div class="modal-body" id="broadcast_stats_body" style="max-height: 60vh; overflow-y: auto;">
                <!-- Stats will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"
                    onclick="$('#broadcastStatsModal').modal('hide')">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade custom-modal" id="groupMembersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"
                    onclick="$('#groupMembersModal').modal('hide')">&times;</button>
                <h4 class="modal-title"><i class="fa fa-users"></i> Group Members: <span id="gm-title"></span></h4>
            </div>
            <div class="modal-body">
                <div id="gm-add-area"
                    style="margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; display: none;">
                    <label style="font-size: 0.8rem; color: #64748b; margin-bottom: 8px;">Add New Member</label>
                    <div style="position: relative;">
                        <i class="fa fa-search"
                            style="position: absolute; left: 12px; top: 10px; color: #94a3b8; font-size: 0.9rem;"></i>
                        <input type="text" id="gm-search-input" class="form-control input-sm"
                            placeholder="Search student name..." onkeyup="searchAddMembers()"
                            style="padding-left: 35px; border-radius: 20px;">
                        <div id="gm-search-results"
                            style="position: absolute; width: 100%; background: white; z-index: 1000; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-top: 5px; display: none; max-height: 200px; overflow-y: auto;">
                        </div>
                    </div>
                </div>
                <div id="group_members_body" style="max-height: 50vh; overflow-y: auto;">
                    <!-- Members will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"
                    onclick="$('#groupMembersModal').modal('hide')">Close</button>
            </div>
        </div>
    </div>
</div>

<div id="dmModal" class="modal fade custom-modal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"
                    onclick="$('#dmModal').modal('hide')">&times;</button>
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
                <button type="button" class="close" data-dismiss="modal"
                    onclick="$('#groupModal').modal('hide')">&times;</button>
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
                        <div id="btn-mode-auto" class="g-mode-btn active" onclick="toggleGroupMode('auto')">Auto
                            (Filters)</div>
                        <div id="btn-mode-manual" class="g-mode-btn" onclick="toggleGroupMode('manual')">Manual (Select)
                        </div>
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
                            <i class="fa fa-search"
                                style="position: absolute; left: 15px; top: 12px; color: #94a3b8;"></i>
                            <input type="text" id="g-student-search" class="form-control"
                                placeholder="Search student by name or roll..." onkeyup="searchGroupStudents()"
                                style="padding-left: 40px;">
                            <div id="g-search-results"
                                style="margin-top: 5px; max-height: 150px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; display: none; background: white; position: absolute; width: 100%; z-index: 100; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            </div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 20px;">
                        <label>Selected Students (<span id="g-selected-count">0</span>)</label>
                        <div id="g-selected-students"
                            style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 5px; min-height: 40px; padding: 10px; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1;">
                            <div class="text-muted" style="font-size: 0.8rem; width: 100%; text-align: center;">No
                                students selected yet</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"
                    onclick="$('#groupModal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="createGroup()">Create Group</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade custom-modal" id="previewModel" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"
                    onclick="$('#previewModel').modal('hide')">&times;</button>
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

<div class="modal fade custom-modal" id="courseStudentsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"
                    onclick="$('#courseStudentsModal').modal('hide')">&times;</button>
                <h4 class="modal-title"><i class="fa fa-users"></i> Students in <span id="cs-course-name"></span></h4>
                <p class="text-muted" style="margin:0; font-size: 0.8rem;">Session: <span id="cs-session-name"></span>
                </p>
            </div>
            <div class="modal-body">
                <div id="cs-loading" class="text-center" style="display: none;">
                    <i class="fa fa-spinner fa-spin fa-2x w3-text-blue"></i>
                    <p>Loading students...</p>
                </div>
                <div id="course_students_body" style="max-height: 50vh; overflow-y: auto;">
                    <!-- Students will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"
                    onclick="$('#courseStudentsModal').modal('hide')">Close</button>
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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tributejs/5.1.3/tribute.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/tributejs/5.1.3/tribute.min.js"></script>

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

        // Initialize Tribute.js for @mentions
        var tribute = new Tribute({
            trigger: '@',
            selectTemplate: function (item) {
                if (typeof item === 'undefined') return null;
                return '@' + item.original.name;
            },
            menuItemTemplate: function (item) {
                return '<span style="font-weight:600; color:#1e293b;">' + item.original.name + '</span> <span style="font-size:0.8em;color:#64748b;">(' + item.original.info + ') - <span class="w3-tag w3-tiny ' + (item.original.type === 'Student' ? 'w3-blue' : 'w3-orange') + '">' + item.original.type + '</span></span>';
            },
            values: function (text, cb) {
                if (text.length < 2) { cb([]); return; }

                Promise.all([
                    fetch('faculty_portal.php', {
                        method: 'POST',
                        body: (() => { let f = new FormData(); f.append('POST_TYPE', 'SEARCH_STUDENTS'); f.append('include_shortterm', '1'); f.append('query', text); return f; })()
                    }).then(r => r.json()),
                    fetch('faculty_portal.php', {
                        method: 'POST',
                        body: (() => { let f = new FormData(); f.append('POST_TYPE', 'SEARCH_FACULTY'); f.append('query', text); return f; })()
                    }).then(r => r.json())
                ]).then(([studentRes, facultyRes]) => {
                    let results = [];
                    if (studentRes.error === 0 && studentRes.data) {
                        results = results.concat(studentRes.data.map(s => ({ name: s.s_name, info: s.s_roll_no, type: 'Student' })));
                    }
                    if (facultyRes.error === 0 && facultyRes.data) {
                        results = results.concat(facultyRes.data.map(f => ({ name: f.name, info: f.code, type: 'Faculty' })));
                    }
                    cb(results);
                }).catch(() => { cb([]); });
            }
        });

        var msgContent = document.getElementById('msg-content');
        var templateContent = document.getElementById('template');
        if (msgContent) tribute.attach(msgContent);
        if (templateContent) tribute.attach(templateContent);

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

    function filterSidebar(type, btn) {
        $('.filter-btn').removeClass('active');
        if (btn) {
            $(btn).addClass('active');
        } else {
            $('#flt-' + type).addClass('active');
        }

        if (type === 'all') {
            $('.chat-item').show();
            $('.sidebar-section-label').show();
        } else {
            $('.chat-item').hide();
            $('.sidebar-section-label').hide();

            if (type === 'broadcast') {
                $('.chat-item-broadcast').show();
                $('.sidebar-section-broadcast').show();
            } else if (type === 'admin') {
                $('.chat-item-admin').show();
                $('.sidebar-section-admin').show();
            } else if (type === 'dm') {
                $('.chat-item-dm').show();
                $('.sidebar-section-dm').show();
            }
        }
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
            headerAvatar.style.background = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
            headerAvatar.style.color = 'white';
            $('#active-subtitle').text("Auto Group (Session Based Broadcast)");
            $('#normal-chat-view').css('display', 'flex');
            $('#broadcast-dashboard-view').hide();
            fetchMessages();
        } else if (id && id.toString().startsWith('DM-')) {
            headerIcon.className = 'fa fa-user';
            headerAvatar.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            headerAvatar.style.color = 'white';
            $('#active-subtitle').text("Direct Message");
            $('#normal-chat-view').css('display', 'flex');
            $('#broadcast-dashboard-view').hide();
            fetchMessages();
        } else if (id) {
            headerIcon.className = 'fa fa-users';
            headerAvatar.style.background = 'linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%)';
            headerAvatar.style.color = 'white';
            activeTitle.innerHTML = title + ' <i class="fa fa-chevron-right" style="font-size: 0.7rem; color: #94a3b8;"></i>';
            $('#active-subtitle').text("Custom Group Chat • View Info");
            $('#normal-chat-view').css('display', 'flex');
            $('#broadcast-dashboard-view').hide();
            fetchMessages();
        } else {
            headerIcon.className = 'fa fa-bullhorn';
            headerAvatar.style.background = 'linear-gradient(135deg, #ec4899 0%, #db2777 100%)';
            headerAvatar.style.color = 'white';
            $('#active-subtitle').text("Select filters to broadcast to specific classes");
            $('#normal-chat-view').hide();
            $('#broadcast-dashboard-view').css('display', 'flex');
            loadBroadcastStudents();
        }

        $('.chat-item').removeClass('active');
        if (event) {
            $(event.currentTarget).addClass('active');
        }

        // Handle Session Course Buttons
        const btnContainer = $('#course-buttons-container');
        btnContainer.empty();
        if (id && id.toString().startsWith('SES-')) {
            const sessionId = id.replace('SES-', '');
            loadSessionCourses(sessionId, title);
        }
    }

    function loadSessionCourses(sessionId, sessionTitle) {
        const payload = new FormData();
        payload.append('POST_TYPE', 'GET_SESSION_COURSES');
        payload.append('session_id', sessionId);

        fetch('faculty_portal.php', { method: 'POST', body: payload })
            .then(r => r.json())
            .then(res => {
                if (res.error === 0) {
                    const colors = ['blue', 'green', 'purple', 'orange', 'red'];
                    const container = $('#course-buttons-container');
                    res.data.forEach((course, index) => {
                        const colorClass = colors[index % colors.length];
                        const btn = $(`
                            <div class="course-badge ${colorClass}" onclick="viewCourseStudents(${course.id}, '${course.sname}', ${sessionId}, '${sessionTitle.replace(/'/g, "\\'")}')">
                                <span>${course.sname}</span>
                                <span class="student-count">${course.student_count}</span>
                            </div>
                        `);
                        container.append(btn);
                    });
                }
            });
    }

    function viewCourseStudents(courseId, courseName, sessionId, sessionName) {
        $('#cs-course-name').text(courseName);
        $('#cs-session-name').text(sessionName);
        $('#course_students_body').empty();
        $('#cs-loading').show();
        $('#courseStudentsModal').modal('show');

        const payload = new FormData();
        payload.append('POST_TYPE', 'GET_COURSE_STUDENTS');
        payload.append('session_id', sessionId);
        payload.append('course_id', courseId);

        fetch('faculty_portal.php', { method: 'POST', body: payload })
            .then(r => r.json())
            .then(res => {
                $('#cs-loading').hide();
                if (res.error === 0) {
                    const list = $('#course_students_body');
                    if (res.data.length === 0) {
                        list.append('<p class="text-center text-muted">No students found.</p>');
                        return;
                    }

                    const table = $('<table class="gm-table"></table>');
                    res.data.forEach(s => {
                        table.append(`
                            <tr class="gm-row">
                                <td>
                                    <div style="font-weight: 600; color: #1e293b;">${s.s_name}</div>
                                    <div style="font-size: 0.7rem; color: #64748b;">Roll: ${s.s_roll_no}</div>
                                </td>
                                <td style="text-align: right;">
                                    <div class="action-btn" onclick="removeStudentFromAutoGroup(${s.s_id}, ${courseId}, '${s.s_name.replace(/'/g, "\\'")}', this)" title="Remove Student">
                                        <i class="fa fa-trash"></i>
                                    </div>
                                </td>
                            </tr>
                        `);
                    });
                    list.append(table);
                }
            });
    }

    function removeStudentFromAutoGroup(studentId, courseId, name, btn) {
        if (!confirm(`Are you sure you want to remove ${name} from this chat group?`)) return;

        const payload = new FormData();
        payload.append('POST_TYPE', 'REMOVE_FROM_AUTO_GROUP');
        payload.append('student_id', studentId);
        // Get session ID from the current channel context
        if (currentGroupId && currentGroupId.toString().startsWith('SES-')) {
            payload.append('session_id', currentGroupId.replace('SES-', ''));
        }

        fetch('faculty_portal.php', { method: 'POST', body: payload })
            .then(r => r.json())
            .then(res => {
                if (res.error === 0) {
                    $(btn).closest('tr').fadeOut(300, function () { $(this).remove(); });
                    // Optionally refresh the counts in the header
                    // We can just re-select the channel to update counts
                    const currentId = currentGroupId;
                    const currentTitle = $('#active-title').text();
                    selectChannel(currentId, currentTitle);
                } else {
                    alert("Error: " + res.message);
                }
            });
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
                                        <div style="cursor: pointer; background: rgba(255,255,255,0.2); color: #fff; font-weight: 700; padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.3);" onclick="viewBroadcastStats('${msg.groupId}')" title="Click to view read receipts">
                                            <i class="fa fa-users"></i> ${msg.broadcast_seen} / ${msg.broadcast_total} Seen
                                        </div>
                                    `;
                                } else if (msg.groupId && !msg.groupId.toString().startsWith('DM-')) {
                                    readStatus = `
                                        <div style="cursor: pointer; background: rgba(255,255,255,0.2); color: #fff; font-weight: 700; padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.3);" onclick="viewBroadcastStats(null, ${msg.id})" title="Click to view read receipts">
                                            <i class="fa fa-users"></i> ${msg.group_seen} / ${msg.group_total} Seen
                                        </div>
                                    `;
                                } else if (msg.receiver_id) {
                                    readStatus = msg.is_read == 1
                                        ? `<span style="background: var(--success); color: #fff; font-weight: 700; padding: 4px 12px; border-radius: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" title="Read at ${msg.read_at}" onclick="viewBroadcastStats(null, ${msg.id})"><i class="fa fa-check-circle"></i> Seen</span>`
                                        : `<span style="background: rgba(255,255,255,0.2); color: #fff; font-weight: 600; padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2);" title="Unread"><i class="fa fa-check"></i> Sent</span>`;
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
                                if (msg.groupId && msg.groupId.toString().startsWith('BCT-') && msg.broadcast_total > 1) {
                                    recipientLabel = `<div style="font-size: 10px; color: #bfdbfe; margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fa fa-bullhorn"></i> Broadcast</div>`;
                                } else if (msg.recipient_name) {
                                    recipientLabel = `<div style="font-size: 10px; color: #bfdbfe; margin-bottom: 4px; font-weight: 600;"><i class="fa fa-user"></i> Direct Message with ${msg.recipient_name}</div>`;
                                } else {
                                    recipientLabel = `<div style="font-size: 10px; color: #bfdbfe; margin-bottom: 4px; font-weight: 600;"><i class="fa fa-user"></i> Direct Message</div>`;
                                }
                            }

                            // Build file attachment HTML if present
                            let fileHtml = '';
                            if (msg.file_path) {
                                const isImg = msg.file_type && msg.file_type.startsWith('image/');
                                const fileUrl = '<?= $DOC_ROOT ?>/../' + msg.file_path;
                                if (isImg) {
                                    fileHtml = `
                                        <div style="margin-top: 8px;">
                                            <a href="${msg.file_path}" target="_blank">
                                                <img src="${msg.file_path}" alt="${msg.file_name}" style="max-width: 220px; max-height: 180px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); display: block;">
                                            </a>
                                        </div>`;
                                } else {
                                    const iconClass = msg.file_type && msg.file_type.includes('pdf') ? 'fa-file-pdf-o' : 'fa-file-o';
                                    fileHtml = `
                                        <div style="margin-top: 8px;">
                                            <a href="${msg.file_path}" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.2); padding: 8px 14px; border-radius: 10px; color: inherit; text-decoration: none; border: 1px solid rgba(255,255,255,0.3); font-size: 0.8rem; font-weight: 600; backdrop-filter: blur(4px);">
                                                <i class="fa ${iconClass}" style="font-size: 1.2rem;"></i>
                                                <span style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${msg.file_name || 'Download File'}</span>
                                                <i class="fa fa-download"></i>
                                            </a>
                                        </div>`;
                                }
                            }

                            list.append(`
                                <div class="message ${isMe ? 'sent' : 'received'}" style="position: relative; padding-top: ${isMe ? '30px' : '10px'};">
                                    ${actions}
                                    <div style="font-size: 11px; margin-bottom: 3px; font-weight: bold; opacity: 0.8;">
                                        ${isMe ? 'You' : 'Student'}
                                    </div>
                                    ${recipientLabel}
                                    ${msg.content ? `<div class="message-text" style="word-break: break-word;">${msg.content}</div>` : ''}
                                    ${fileHtml}
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

    function updateAttachmentPreview(input) {
        if (input.files && input.files[0]) {
            $('#attachment-name').text(input.files[0].name);
            $('#attachment-preview').css('display', 'flex');
        }
    }

    function clearAttachment() {
        $('#chat-attachment').val('');
        $('#attachment-preview').hide();
    }

    function sendMessage() {
        const content = $('#msg-content').val();
        const fileInput = document.getElementById('chat-attachment');

        if (!content && (!fileInput.files || fileInput.files.length === 0)) return;

        const payload = new FormData();
        payload.append('POST_TYPE', 'SEND_MESSAGE');
        payload.append('content', content);
        if (fileInput.files && fileInput.files.length > 0) {
            payload.append('attachment', fileInput.files[0]);
        }

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
                        clearAttachment();
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
        payload.append('include_shortterm', '1');
        payload.append('query', query);

        fetch('faculty_portal.php', { method: 'POST', body: payload })
            .then(r => r.json())
            .then(res => {
                results.empty().show();
                if (res.data.length === 0) {
                    results.append('<div class="search-item text-muted">No students found</div>');
                } else {
                    res.data.forEach(s => {
                        const sType = s.s_type || 'student';
                        // Check if already selected
                        if (selectedGroupStudents.some(st => st.id == s.s_id && st.s_type == sType)) return;

                        const roleBadge = sType === 'shortterm_student' ? '<span class="w3-tag w3-tiny w3-blue" style="font-size:10px; padding:2px;">Short Term</span>' : '<span class="w3-tag w3-tiny w3-green" style="font-size:10px; padding:2px;">Regular</span>';

                        results.append(`
                            <div class="search-item" onclick="addGroupStudent(${s.s_id}, '${s.s_name.replace(/'/g, "\\'")}', '${s.s_roll_no}', '${sType}')">
                                <strong>${s.s_name}</strong> <span class="text-muted">(Roll: ${s.s_roll_no})</span> ${roleBadge}
                            </div>
                        `);
                    });
                }
            });
    }

    function addGroupStudent(id, name, roll, s_type = 'student') {
        if (!selectedGroupStudents.some(s => s.id == id && s.s_type == s_type)) {
            selectedGroupStudents.push({ id, name, roll, s_type });
            renderSelectedGroupStudents();
        }
        $('#g-student-search').val('');
        $('#g-search-results').hide();
    }

    function removeGroupStudent(id, s_type = 'student') {
        selectedGroupStudents = selectedGroupStudents.filter(s => !(s.id == id && s.s_type == s_type));
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
            const extraStyle = s.s_type === 'shortterm_student' ? 'background: #3b82f6 !important; color: white;' : '';
            container.append(`
                <div class="student-badge" style="${extraStyle}">
                    <span>${s.name}</span>
                    <i class="fa fa-times-circle" onclick="removeGroupStudent(${s.id}, '${s.s_type}')"></i>
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
                payload.append('student_types[]', s.s_type || 'student');
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

    /* Send WhatsApp / Broadcast */
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
        const fileInput = document.getElementById('bc-attachment');

        const payload = new FormData();
        payload.append('POST_TYPE', 'SEND_BROADCAST');
        students_id.forEach(id => payload.append('students_id[]', id));
        selected.forEach(r => payload.append('selected[]', r));
        selectContact.forEach(c => payload.append('selectContact[]', c));
        payload.append('template', template);
        payload.append('totalCheckboxes', totalCheckboxes);
        payload.append('cat_id', cat_id || '');
        payload.append('sub_cat_id', sub_cat_id || '');
        payload.append('template_sid', template_sid || '');
        payload.append('session_id', $('#session').val() || 0);
        payload.append('sendBtn', 1);
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            payload.append('attachment', fileInput.files[0]);
        }

        fetch('faculty_portal.php', {
            method: 'POST',
            body: payload
        })
            .then(r => r.json())
            .then(data => {
                $('#progress-bar-container').hide();
                $('#progress-text').html('0%');
                $('#progress-bar').css('width', '0%');
                $('#previewModel').modal('hide');
                clearBcAttachment();

                if (data && data.session_id) {
                    var sesId = data.session_id;
                    var sesName = data.session_name || 'Session';
                    var uniShort = data.uni_short || '';
                    var channelId = 'SES-' + sesId;
                    var channelTitle = 'Session: ' + sesName + (uniShort ? ' (' + uniShort + ')' : '');

                    $('.chat-item').removeClass('active');
                    $('.chat-item').each(function () {
                        var onclick = $(this).attr('onclick') || '';
                        if (onclick.indexOf("'SES-" + sesId + "'") !== -1 ||
                            onclick.indexOf('"SES-' + sesId + '"') !== -1) {
                            $(this).addClass('active');
                        }
                    });
                    selectChannel(channelId, channelTitle);
                }
            })
            .catch(() => {
                $('#progress-bar-container').hide();
                alert('Send error. Please try again.');
                $('#previewModel').modal('hide');
            });
    });

    function updateBcAttachmentPreview(input) {
        if (input.files && input.files[0]) {
            $('#bc-attachment-name').text(input.files[0].name);
            $('#bc-attachment-preview').css('display', 'flex');
        }
    }

    function clearBcAttachment() {
        $('#bc-attachment').val('');
        $('#bc-attachment-preview').hide();
    }

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

    window.viewBroadcastStats = function (broadcastId, msgId = null) {
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

    window.viewGroupMembers = function (groupId, groupName) {
        $('#gm-title').text(groupName);
        $('#group_members_body').html('<div class="text-center" style="padding: 40px;"><i class="fa fa-spinner fa-spin" style="font-size: 2rem; color: #3b82f6;"></i><br><span style="margin-top: 10px; display: block; color: #64748b;">Fetching participants...</span></div>');
        $('#gm-add-area').show();
        $('#groupMembersModal').modal('show');

        const payload = new FormData();
        payload.append('POST_TYPE', 'GET_GROUP_MEMBERS');
        payload.append('groupId', groupId);

        fetch('faculty_portal.php', { method: 'POST', body: payload })
            .then(r => r.text())
            .then(text => {
                try {
                    let res = JSON.parse(text);
                    if (res.error === 0) {
                        let html = '<table class="gm-table"><thead><tr style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;"><th style="padding-left: 15px;">Name</th><th>Role</th><th>Info</th><th style="text-align: center;">Action</th></tr></thead><tbody>';
                        res.data.forEach(m => {
                            let role = m.role || 'Member';
                            let isStudent = (role.toLowerCase() === 'student' || role.toLowerCase() === 'short term student');
                            let roleClass = isStudent ? 'role-student' : 'role-faculty';
                            let uRole = m.user_role || 'student';
                            let mName = m.name || 'Unknown User';
                            let action = isStudent
                                ? `<div class="action-btn" title="Remove Member" onclick="removeMemberFromGroup(${m.student_id}, '${mName.replace(/'/g, "\\'")}', '${uRole}')"><i class="fa fa-user-times"></i></div>`
                                : '<span style="color: #cbd5e1;">-</span>';

                            html += `<tr class="gm-row">
                                <td style="font-weight: 600; color: #1e293b; font-size: 0.9rem;">${mName}</td>
                                <td><span class="role-badge ${roleClass}">${role}</span></td>
                                <td class="text-muted" style="font-size: 0.85rem;">${m.info || '-'}</td>
                                <td style="display: flex; justify-content: center; border: none; padding-top: 15px;">${action}</td>
                            </tr>`;
                        });
                        html += '</tbody></table>';
                        $('#group_members_body').html(html);
                    } else {
                        $('#group_members_body').html(`<div class="alert alert-danger" style="border-radius: 12px;">${res.message}</div>`);
                    }
                } catch (e) {
                    console.error('Failed to parse GET_GROUP_MEMBERS response:', text);
                    $('#group_members_body').html(`<div class="alert alert-danger" style="border-radius: 12px;">Failed to load members. Server returned invalid response.</div>`);
                }
            })
            .catch(err => {
                $('#group_members_body').html(`<div class="alert alert-danger" style="border-radius: 12px;">Network error: ${err.message}</div>`);
            });
    };

    window.openCurrentGroupMembers = function () {
        if (!currentGroupId || currentGroupId.toString().startsWith('SES-') || currentGroupId.toString().startsWith('DM-')) return;
        viewGroupMembers(currentGroupId, document.getElementById('active-title').innerText);
    };

    window.searchAddMembers = function () {
        const query = $('#gm-search-input').val();
        const results = $('#gm-search-results');
        if (query.length < 2) { results.hide(); return; }
        const payload = new FormData();
        payload.append('POST_TYPE', 'SEARCH_STUDENTS');
        payload.append('include_shortterm', '1');
        payload.append('query', query);
        fetch('faculty_portal.php', { method: 'POST', body: payload }).then(r => r.json()).then(res => {
            results.empty().show();
            res.data.forEach(s => {
                const sType = s.s_type || 'student';
                const roleBadge = sType === 'shortterm_student' ? '<span class="w3-tag w3-tiny w3-blue" style="font-size:10px; padding:2px;">Short Term</span>' : '';
                results.append(`<div class="search-item" style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9;" onclick="addMemberToGroup(${s.s_id}, '${sType}')"><strong>${s.s_name}</strong> <span class="text-muted" style="font-size: 0.75rem;">(${s.s_roll_no})</span> ${roleBadge}</div>`);
            });
        });
    };

    window.addMemberToGroup = function (studentId, sType = 'student') {
        const payload = new FormData();
        payload.append('POST_TYPE', 'ADD_MEMBER');
        payload.append('groupId', currentGroupId);
        payload.append('studentId', studentId);
        payload.append('studentType', sType);
        fetch('faculty_portal.php', { method: 'POST', body: payload }).then(r => r.json()).then(res => {
            if (res.error === 0) {
                $('#gm-search-input').val('');
                $('#gm-search-results').hide();
                viewGroupMembers(currentGroupId, document.getElementById('active-title').innerText);
            } else alert(res.message);
        });
    };

    window.removeMemberFromGroup = function (studentId, studentName, sType = 'student') {
        if (confirm(`Remove ${studentName} from this group?`)) {
            const payload = new FormData();
            payload.append('POST_TYPE', 'REMOVE_MEMBER');
            payload.append('groupId', currentGroupId);
            payload.append('studentId', studentId);
            payload.append('studentType', sType);
            fetch('faculty_portal.php', { method: 'POST', body: payload }).then(r => r.json()).then(res => {
                if (res.error === 0) viewGroupMembers(currentGroupId, document.getElementById('active-title').innerText);
                else alert(res.message);
            });
        }
    };

    window.deleteGroup = function (groupId, groupName) {
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