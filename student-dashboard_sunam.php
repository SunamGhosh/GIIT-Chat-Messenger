<?php
session_start();
if (!isset($_SESSION['s_id'])) {
    header('Location: student-login.php');
    exit;
}
require_once("configNew.php");
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    // 1. Check if student has pending document request
    if ($_GET['action'] === 'check_doc_request') {
        $s_id = (int) $_SESSION['s_id'];
        $stmt = $con->prepare("
            SELECT
                di.id AS issue_id,
                dt.type_name,
                dt.type_short_name,
                u.university_name,
                sm.session_name,
                cm.course_name,
                di.semester_id
            FROM student_doc_issue di
            JOIN student_document_type_master dt ON dt.type_id = di.document_type_id
            JOIN university u ON di.university_id = u.id
            JOIN session_master sm ON di.session_id = sm.session_master_id
            JOIN course_master cm ON di.course_id = cm.course_master_id
            WHERE di.student_id = ?
              AND di.send_notification = 1
              AND di.student_response IS NULL
              AND di.is_active = 1
            ORDER BY di.id DESC
            LIMIT 1
        ");
        if (!$stmt) {
            echo json_encode(['error' => $con->error]);
            exit;
        }
        $stmt->bind_param("i", $s_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $doc_display = $row['type_short_name'] ? $row['type_short_name'] . " - " . $row['type_name'] : $row['type_name'];
            echo json_encode([
                'has_request' => true,
                'issue_id' => (int) $row['issue_id'],
                'document_type' => $doc_display,
                'university' => $row['university_name'],
                'session' => $row['session_name'],
                'course' => $row['course_name'],
                'semester' => $row['semester_id']
            ]);
        } else {
            echo json_encode(['has_request' => false]);
        }
        $stmt->close();
        exit;
    }
    // 2. Student responds to document request (1 = Self/Yes, 0 = Others/No)
    if ($_GET['action'] === 'respond_doc' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $issue_id = (int) ($_POST['issue_id'] ?? 0);
        $response = (int) ($_POST['response'] ?? -1);
        $s_id = (int) $_SESSION['s_id'];
        if ($issue_id <= 0 || !in_array($response, [0, 1])) {
            echo json_encode(['success' => false, 'error' => 'invalid data']);
            exit;
        }
        // Record response without setting 'accepted/rejected' - allow details for both
        $stmt = $con->prepare("
            UPDATE student_doc_issue
            SET
                student_response = ?,
                response_timestamp = NOW(),
                status = 'responded'
            WHERE id = ?
              AND student_id = ?
              AND student_response IS NULL
        ");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => $con->error]);
            exit;
        }
        $stmt->bind_param("iii", $response, $issue_id, $s_id);
        $success = $stmt->execute() && $stmt->affected_rows > 0;
        echo json_encode(['success' => $success]);
        $stmt->close();
        exit;
    }

    // 3. Check for active elective form
    if ($_GET['action'] === 'check_elective_form') {
        $s_id = (int) $_SESSION['s_id'];
        $course_id = (int) $_SESSION['s_course_id'];
        $uni_id = (int) $_SESSION['s_university_id'];
        $session_id = (int) $_SESSION['s_session_id'];

        // Get student current semester
        $s_stmt = $con->prepare("SELECT s_cur_sem FROM student WHERE s_id = ?");
        $s_stmt->bind_param("i", $s_id);
        $s_stmt->execute();
        $student_data = $s_stmt->get_result()->fetch_assoc();
        $current_sem = $student_data['s_cur_sem'];
        $s_stmt->close();

        // NEW: Check if already finalized in master (if so, don't show the form alert)
        $master_check = $con->prepare("SELECT id FROM student_elective_master WHERE student_id = ? AND semester_id = ?");
        $master_check->bind_param("ii", $s_id, $current_sem);
        $master_check->execute();
        if ($master_check->get_result()->num_rows > 0) {
            echo json_encode(['has_form' => false, 'reason' => 'finalized']);
            $master_check->close();
            exit;
        }
        $master_check->close();

        // Check if form is active and get deadline
        $stmt = $con->prepare("
            SELECT fs.id, fs.deadline, sub.status as sub_status
            FROM elective_form_settings fs
            JOIN elective_groups eg ON eg.university_id = fs.university_id AND eg.course_id = fs.course_id
            LEFT JOIN student_elective_submissions sub ON sub.student_id = ? AND sub.group_id = eg.id
            WHERE fs.university_id = ? AND fs.session_id = ? AND fs.course_id = ? 
              AND FIND_IN_SET(?, fs.semester) AND fs.is_active = 1
            ORDER BY sub.id DESC
            LIMIT 1
        ");
        $stmt->bind_param("iiiii", $s_id, $uni_id, $session_id, $course_id, $current_sem);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $deadline_time = strtotime($row['deadline']);
            $current_time = time();

            // Strictly check deadline
            if ($deadline_time >= $current_time) {
                echo json_encode([
                    'has_form' => true,
                    'deadline' => date('d M Y, h:i A', $deadline_time),
                    'submitted' => !is_null($row['sub_status']),
                    'sub_status' => $row['sub_status']
                ]);
            } else {
                echo json_encode(['has_form' => false, 'reason' => 'deadline_passed']);
            }
        } else {
            echo json_encode(['has_form' => false]);
        }
        $stmt->close();
        exit;
    }

    // 4. Get elective form data (Groups and Subjects)
    if ($_GET['action'] === 'get_elective_form_data') {
        $s_id = (int) $_SESSION['s_id'];
        $course_id = (int) $_SESSION['s_course_id'];
        $uni_id = (int) $_SESSION['s_university_id'];

        // Get current semester
        $s_stmt = $con->prepare("SELECT s_cur_sem FROM student WHERE s_id = ?");
        $s_stmt->bind_param("i", $s_id);
        $s_stmt->execute();
        $student_data = $s_stmt->get_result()->fetch_assoc();
        $current_sem = $student_data['s_cur_sem'];
        $s_stmt->close();

        // Get groups
        $g_stmt = $con->prepare("SELECT id, group_name FROM elective_groups WHERE university_id = ? AND course_id = ? AND FIND_IN_SET(?, semester) AND status = 1");
        $g_stmt->bind_param("iii", $uni_id, $course_id, $current_sem);
        $g_stmt->execute();
        $groups = $g_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $g_stmt->close();

        // Get subjects for all these groups in this semester
        $subjects = [];
        if (!empty($groups)) {
            $g_ids = array_column($groups, 'id');
            $placeholders = implode(',', array_fill(0, count($g_ids), '?'));
            $sql = "SELECT id, group_id, elective_no, subject_name, short_name FROM elective_subjects WHERE group_id IN ($placeholders) AND semester = ? AND status = 1 ORDER BY elective_no ASC, subject_name ASC";
            $params = array_merge($g_ids, [$current_sem]);
            $stmt = $con->prepare($sql);
            $stmt->bind_param(str_repeat('i', count($g_ids)) . 's', ...$params);
            $stmt->execute();
            $subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }

        // Get existing submission for this batch
        $sub_stmt = $con->prepare("
            SELECT sub.* 
            FROM student_elective_submissions sub
            JOIN elective_groups eg ON sub.group_id = eg.id
            WHERE sub.student_id = ? AND eg.university_id = ? AND eg.course_id = ?
            ORDER BY sub.id DESC LIMIT 1
        ");
        $sub_stmt->bind_param("iii", $s_id, $uni_id, $course_id);
        $sub_stmt->execute();
        $submission = $sub_stmt->get_result()->fetch_assoc();
        $sub_stmt->close();

        echo json_encode([
            'groups' => $groups,
            'subjects' => $subjects,
            'semester' => $current_sem,
            'submission' => $submission
        ]);
        exit;
    }

    // 5. Submit elective choice via AJAX
    if ($_GET['action'] === 'submit_elective_choice' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $s_id = (int) $_SESSION['s_id'];
        $group_id = (int) $_POST['group_id'];
        $choices = json_encode($_POST['choices'] ?? []);

        // 1. Get Batch Info for this group
        $g_stmt = $con->prepare("SELECT university_id, course_id FROM elective_groups WHERE id = ?");
        $g_stmt->bind_param("i", $group_id);
        $g_stmt->execute();
        $g_info = $g_stmt->get_result()->fetch_assoc();
        $g_stmt->close();

        if (!$g_info) {
            echo json_encode(['success' => false, 'error' => 'Invalid group selected']);
            exit;
        }

        // 2. Check for ANY existing submission for THIS student in THIS course/batch
        $check = $con->prepare("
            SELECT sub.id FROM student_elective_submissions sub
            JOIN elective_groups eg ON sub.group_id = eg.id
            WHERE sub.student_id = ? AND eg.university_id = ? AND eg.course_id = ?
        ");
        $check->bind_param("iii", $s_id, $g_info['university_id'], $g_info['course_id']);
        $check->execute();
        $res = $check->get_result();

        if ($row = $res->fetch_assoc()) {
            // Update existing
            $stmt = $con->prepare("UPDATE student_elective_submissions SET group_id = ?, subject_choices = ?, status = 'pending' WHERE id = ?");
            $stmt->bind_param("isi", $group_id, $choices, $row['id']);
        } else {
            // Insert new
            $stmt = $con->prepare("INSERT INTO student_elective_submissions (student_id, group_id, subject_choices, status) VALUES (?, ?, ?, 'pending')");
            $stmt->bind_param("iis", $s_id, $group_id, $choices);
        }

        // DEBUG: Log received data
        error_log("Elective Submission - Student: $s_id, Group: $group_id, Choices: $choices");

        $success = $stmt->execute();
        if (!$success) {
            error_log("DB Error: " . $con->error);
        }
        echo json_encode(['success' => $success, 'error' => $con->error]);
        $stmt->close();
        exit;
    }
}
// Added: Relationships handler (copied from admin to make it accessible here)
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    if ($_GET['ajax'] === 'relationships') {
        $stmt = $con->prepare("
            SELECT id, relationship_name, shortname
            FROM relationship_master
            WHERE is_active = 1
            ORDER BY relationship_name
        ");
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);
        echo json_encode($data);
        $stmt->close();
        exit;
    }
    // Added: Update details handler (copied from admin for consistency)
    if ($_GET['ajax'] === 'update_details' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $issue_id = (int) $_POST['issue_id'];
        $relationship_id = isset($_POST['relationship_id']) && $_POST['relationship_id'] !== '' ? (int) $_POST['relationship_id'] : null;
        $relationship_phone = $con->real_escape_string($_POST['relationship_phone'] ?? '');
        $relationship_aadhar = $con->real_escape_string($_POST['relationship_aadhar'] ?? '');
        $faculty_id = (int) $_POST['faculty_id'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $photo_file = '';
        if (isset($_POST['photo_base64']) && !empty($_POST['photo_base64'])) {
            $base64 = $_POST['photo_base64'];
            $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
            $image = base64_decode($base64);
            if ($image !== false) {
                $upload_dir = 'uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $filename = 'photo_' . $issue_id . '_' . time() . '.png';
                $file_path = $upload_dir . $filename;
                if (file_put_contents($file_path, $image)) {
                    $photo_file = $file_path;
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to save image']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid base64 data']);
                exit;
            }
        } else {
            $photo_file = $con->real_escape_string($_POST['photo_file'] ?? '');
        }
        $stmt = $con->prepare("
            UPDATE student_doc_issue
            SET relationship_id = ?, relationship_phone = ?, relationship_aadhar = ?, photo_file = ?, faculty_id = ?, is_active = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isssiii", $relationship_id, $relationship_phone, $relationship_aadhar, $photo_file, $faculty_id, $is_active, $issue_id);
        $success = $stmt->execute();
        echo json_encode(['success' => $success]);
        $stmt->close();
        exit;
    }
}
?>
<?php include("header.php"); ?>
<script src="notification_helper.js"></script>
<meta name="author" content="Sunam Ghosh --@SunamGhosh">

<?php
// Fetch student details
$stmt = $con->prepare("SELECT * FROM student WHERE s_id = ?");
$stmt->bind_param("i", $_SESSION['s_id']);
if (!$stmt->execute()) {
    error_log("Student query failed: " . $stmt->error);
    $student = [];
} else {
    $student = $stmt->get_result()->fetch_assoc() ?: [];
}
$stmt->close();

// ==================== NEW: FETCH ELECTIVE ASSIGNMENT ====================
$elective_group_name = '';
if (!empty($student['s_cur_sem'])) {
    $stmt = $con->prepare("
        SELECT eg.group_name
        FROM student_elective_master sem
        JOIN elective_groups eg ON sem.elective_group_id = eg.id
        WHERE sem.student_id = ? AND sem.semester_id = ?
    ");
    $stmt->bind_param("ii", $_SESSION['s_id'], $student['s_cur_sem']);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $elective_group_name = $row['group_name'];
        }
    }
    $stmt->close();
}
// =========================================================================
// Default links (since university_links table doesn't exist)
$programLink = "#";
$timeTable = "#";
$university_id = $_SESSION['s_university_id'] ?? 0;
$course_id = $_SESSION['s_course_id'] ?? 0;
// Fetch fee details
$s_roll = $_SESSION['s_roll_no'] ?? '';
$due_fee = $due_days = 0;
if ($s_roll !== '') {
    $fee_query = "
        SELECT
            sfom.roll AS s_roll_no,
            SUM(sfod.due_amount) as due_fee,
            DATEDIFF(NOW(), sfod.due_date) as due_days
        FROM
            student_fee_option_master sfom,
            student_fee_option_details sfod
        WHERE
            sfod.roll = ?
            AND sfod.due_amount > 0
            AND sfom.id = sfod.student_fee_option_master_id
            AND sfom.fee_option_status = 'active'
            AND DATEDIFF(NOW(), sfod.due_date) > 0
            AND sfod.fee_status != 'fpd'
        GROUP BY
            sfod.roll
    ";
    $stmt = $con->prepare($fee_query);
    $stmt->bind_param("s", $s_roll);
    if (!$stmt->execute()) {
        error_log("Fee query failed: " . $stmt->error);
    } else {
        $fee_res = $stmt->get_result();
        if ($fee_row = $fee_res->fetch_object()) {
            $due_fee = $fee_row->due_fee;
            $due_days = $fee_row->due_days;
        }
    }
    $stmt->close();
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
    rel="stylesheet">
<style type="text/css">
    :root {
        --primary: #4f46e5;
        --primary-light: #818cf8;
        --primary-dark: #312e81;
        --secondary: #6366f1;
        --accent: #4f46e5;
        --bg-main: #f5f5ff;
        --card-bg: rgba(255, 255, 255, 0.88);
        --text-main: #0f172a;
        --text-muted: #64748b;
        --glass-border: rgba(99, 102, 241, 0.15);
        --shadow-sm: 0 1px 2px 0 rgb(79 70 229 / 0.06);
        --shadow-md: 0 4px 6px -1px rgb(79 70 229 / 0.12), 0 2px 4px -2px rgb(79 70 229 / 0.08);
        --shadow-lg: 0 10px 15px -3px rgb(79 70 229 / 0.15), 0 4px 6px -4px rgb(79 70 229 / 0.1);
        --radius-lg: 20px;
        --radius-md: 12px;
    }

    body {
        background-color: var(--bg-main);
        background-image:
            radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.07) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.05) 0px, transparent 50%);
        font-family: 'Outfit', sans-serif !important;
        color: var(--text-main);
        -webkit-font-smoothing: antialiased;
    }

    /* Hide Site Header */
    .header1,
    .header2,
    .header3,
    #mu-menu {
        display: none !important;
    }

    body {
        padding-top: 0 !important;
    }

    #mainContainer {
        margin-top: 10px;
        padding-bottom: 40px;
    }

    @keyframes headerShimmer {
        0% {
            transform: translateX(-100%) skewX(-15deg);
        }

        100% {
            transform: translateX(400%) skewX(-15deg);
        }
    }

    @keyframes coursePulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.3), 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(255, 255, 255, 0.08), 0 4px 20px rgba(0, 0, 0, 0.2);
        }
    }

    @keyframes badgeFadeIn {
        from {
            opacity: 0;
            transform: scale(0.8) translateY(4px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .college-branding {
        text-align: center;
        margin-bottom: 0px !important;
        background: #ffffff;
        padding: 12px 15px;
        border-radius: var(--radius-md) var(--radius-md) 0 0;
        box-shadow: none;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-bottom: none;
        animation: fadeInUp 0.8s ease-out;
    }

    .college-name {
        font-weight: 800;
        font-size: 2rem;
        color: #ce1126;
        /* Official College Red */
        letter-spacing: 0.02em;
        text-transform: uppercase;
        margin-bottom: 4px;
        display: block;
        line-height: 1.1;
    }

    .college-affiliation {
        font-size: 0.85rem;
        color: #000000;
        /* Sharp Black */
        font-weight: 700;
        letter-spacing: 0.01em;
        display: block;
        white-space: nowrap;
    }

    .welcome-header {
        background: linear-gradient(135deg, #1e1b6e 0%, #312e81 35%, #4f46e5 70%, #818cf8 100%);
        padding: 22px 26px !important;
        border-radius: var(--radius-lg);
        color: white !important;
        margin-bottom: 25px !important;
        box-shadow: 0 20px 40px -8px rgba(49, 46, 129, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.08) inset;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: none !important;
        position: relative;
        overflow: hidden;
    }

    /* Decorative bubble top-right */
    .welcome-header::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, transparent 65%);
        border-radius: 50%;
        pointer-events: none;
    }

    /* Shimmer sweep */
    .welcome-header::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 60px;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.18), transparent);
        animation: headerShimmer 3.5s ease-in-out infinite;
        pointer-events: none;
    }

    .welcome-text {
        font-weight: 800;
        font-size: 1.45rem;
        letter-spacing: -0.025em;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        line-height: 1.2;
    }

    .welcome-text span {
        display: block;
        font-size: 0.82rem;
        font-weight: 400;
        opacity: 0.82;
        margin-top: 5px;
        letter-spacing: 0.01em;
    }

    /* ── Attractive course-info pill ── */
    .course-info {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        padding: 8px 18px 8px 14px;
        border-radius: 50px;
        color: #fff;
        border: 1.5px solid rgba(255, 255, 255, 0.4);
        box-shadow: 0 4px 18px rgba(49, 46, 129, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        animation: coursePulse 3s ease-in-out infinite, badgeFadeIn 0.6s ease-out both;
        white-space: nowrap;
        position: relative;
        z-index: 1;
        flex-shrink: 0;
        text-transform: uppercase;
    }

    .course-info .ci-icon {
        width: 26px;
        height: 26px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        flex-shrink: 0;
        color: #fff !important;
        border: 1.5px solid transparent;
        background-image: linear-gradient(rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.1)), linear-gradient(135deg, #f97316, #fbbf24);
        background-origin: border-box;
        background-clip: content-box, border-box;
    }

    .course-info .ci-uni {
        font-size: 0.68rem;
        font-weight: 700;
        opacity: 1;
        color: #fff;
        display: block;
        letter-spacing: 0.03em;
    }

    .content-panel {
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
    }

    .dashboard-link-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px 10px;
        margin-bottom: 20px;
        background: var(--card-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-md);
        text-decoration: none !important;
        color: var(--text-main) !important;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        border: 1px solid var(--glass-border);
        height: 110px !important;
        position: relative;
    }

    .dashboard-link-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 12px 28px -4px rgba(79, 70, 229, 0.22);
        background: white;
        border-color: #818cf8;
    }

    .icon-box {
        width: 48px;
        height: 48px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        transition: all 0.3s ease;
        background: transparent;
        border: 1.5px solid rgba(0, 0, 0, 0.05);
    }

    .dashboard-link-card:hover .icon-box {
        transform: scale(1.1) rotate(-5deg);
        background: white;
        border-color: rgba(79, 70, 229, 0.1);
    }

    /* Icon Color Schemes - Themed Circles & Borders */
    .icon-blue {
        background: #eff6ff !important;
        border-color: #dbeafe !important;
    }

    .icon-blue i {
        color: #2563eb !important;
        -webkit-text-fill-color: #2563eb !important;
    }

    .icon-emerald {
        background: #ecfdf5 !important;
        border-color: #d1fae5 !important;
    }

    .icon-emerald i {
        color: #059669 !important;
        -webkit-text-fill-color: #059669 !important;
    }

    .icon-violet {
        background: #f5f3ff !important;
        border-color: #ede9fe !important;
    }

    .icon-violet i {
        color: #7c3aed !important;
        -webkit-text-fill-color: #7c3aed !important;
    }

    .icon-amber {
        background: #fffbeb !important;
        border-color: #fef3c7 !important;
    }

    .icon-amber i {
        color: #d97706 !important;
        -webkit-text-fill-color: #d97706 !important;
    }

    .icon-orange {
        background: #fff7ed !important;
        border-color: #ffedd5 !important;
    }

    .icon-orange i {
        color: #ea580c !important;
        -webkit-text-fill-color: #ea580c !important;
    }

    .icon-cyan {
        background: #ecfeff !important;
        border-color: #cffafe !important;
    }

    .icon-cyan i {
        color: #0891b2 !important;
        -webkit-text-fill-color: #0891b2 !important;
    }

    .icon-rose {
        background: #fff1f2 !important;
        border-color: #ffe4e6 !important;
    }

    .icon-rose i {
        color: #e11d48 !important;
        -webkit-text-fill-color: #e11d48 !important;
    }

    .icon-logout {
        background: #fef2f2 !important;
        border-color: #fee2e2 !important;
    }

    .icon-logout i {
        color: #dc2626 !important;
        -webkit-text-fill-color: #dc2626 !important;
    }

    .icon-logout+strong {
        color: #dc2626 !important;
    }



    .dashboard-link-card strong {
        font-size: 0.75rem;
        font-weight: 700;
        display: block;
        line-height: 1.2;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        transition: color 0.3s ease;
    }

    .dashboard-link-card:hover strong {
        color: var(--primary-dark);
    }

    .giit-badge {
        background: #4f46e5;
        color: white;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.4);
        border: 2px solid #fff;
    }

    /* Alerts and Notifications */
    .alert-compact {
        background: white !important;
        border-radius: var(--radius-md) !important;
        border: 1px solid var(--glass-border) !important;
        box-shadow: var(--shadow-md) !important;
        color: var(--text-main) !important;
        padding: 12px 20px !important;
        margin-bottom: 20px !important;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .alert-info {
        border-left: 4px solid var(--primary) !important;
    }

    .alert-warning {
        border-left: 4px solid #f59e0b !important;
    }

    .no-data {
        text-align: center;
        padding: 40px;
        background: var(--card-bg);
        border-radius: var(--radius-md);
        color: var(--text-muted);
    }

    .alert-link {
        background: #4f46e5;
        color: white !important;
        padding: 4px 12px;
        border-radius: 20px;
        text-decoration: none !important;
        font-weight: 600;
        font-size: 0.8rem;
        margin-left: auto;
        transition: opacity 0.2s;
    }

    .alert-link:hover {
        opacity: 0.9;
    }

    /* Custom Toast */
    .custom-toast {
        position: fixed;
        top: 24px;
        right: 24px;
        width: 350px;
        background: rgba(255, 255, 255, 0.97);
        backdrop-filter: blur(16px);
        border-radius: var(--radius-md);
        box-shadow: 0 20px 40px rgba(49, 46, 129, 0.18);
        z-index: 11000;
        padding: 20px;
        border: 1px solid rgba(79, 70, 229, 0.22);
        transform: translateX(120%);
        transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .custom-toast.show {
        transform: translateX(0);
    }

    /* Modal Styling */
    .w3-modal-content {
        border-radius: var(--radius-lg) !important;
        overflow: hidden;
        border: 1px solid var(--glass-border) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
    }

    .w3-purple {
        background: linear-gradient(135deg, #1e1b6e 0%, #4f46e5 100%) !important;
    }

    .w3-button.w3-green {
        background-color: #4f46e5 !important;
        border-radius: var(--radius-md) !important;
        font-weight: 600 !important;
    }

    .w3-button.w3-red {
        background-color: #312e81 !important;
        border-radius: var(--radius-md) !important;
    }

    /* Grid layout fixes */
    .row-compact {
        margin-left: -10px;
        margin-right: -10px;
    }

    .col-compact {
        padding-left: 10px;
        padding-right: 10px;
    }

    /* Entrance Animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dashboard-link-card {
        animation: fadeInUp 0.5s ease-out forwards;
        opacity: 0;
    }

    .dashboard-link-card:nth-child(1) {
        animation-delay: 0.1s;
    }

    .dashboard-link-card:nth-child(2) {
        animation-delay: 0.15s;
    }

    .dashboard-link-card:nth-child(3) {
        animation-delay: 0.2s;
    }

    /* ... and so on, but for simplicity I'll apply it to all with a base delay */

    @media (max-width: 768px) {
        .welcome-header {
            flex-direction: row;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 8px;
            padding: 16px 18px !important;
            min-height: 90px;
        }

        .welcome-text {
            font-size: 1.05rem;
            flex: 1 1 auto;
            min-width: 0;
        }

        .welcome-text span {
            font-size: 0.75rem;
            margin-top: 3px;
        }

        /* Pill floats to the right, vertically centred */
        .course-info {
            font-size: 0.7rem;
            padding: 6px 12px 6px 10px;
            gap: 5px;
            flex-shrink: 0;
            align-self: flex-start;
            margin-top: 2px;
            animation: coursePulse 3s ease-in-out infinite, badgeFadeIn 0.6s ease-out both;
            letter-spacing: 0.01em;
        }

        .course-info .ci-icon {
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            color: #fff !important;
            border: 1.2px solid transparent;
            background-image: linear-gradient(rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.1)), linear-gradient(135deg, #f97316, #fbbf24);
            background-origin: border-box;
            background-clip: content-box, border-box;
        }

        .course-info .ci-uni {
            display: inline-block;
            font-size: 0.65rem;
            margin-left: 4px;
            opacity: 1;
            color: #fff;
            font-weight: 700;
        }

        .course-info .ci-uni:not(:empty)::before {
            content: '(';
        }

        .course-info .ci-uni:not(:empty)::after {
            content: ')';
        }

        .course-info .ci-uni:empty {
            display: none;
        }

        .dashboard-link-card {
            height: 95px !important;
            padding: 15px 5px;
        }

        .dashboard-link-card i {
            font-size: 24px;
        }

        .dashboard-link-card strong {
            font-size: 0.62rem;
        }
    }

    /* ============================================================
       PHONE ONLY (≤ 480px) — native app feel, no side gaps
    ============================================================ */
    @media (max-width: 480px) {

        /* 1. Kill all Bootstrap side padding so content is edge-to-edge */
        .container,
        .col-sm-12.col-md-12,
        .content-panel.col-xs-12 {
            padding-left: 0 !important;
            padding-right: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
        }

        #mainContainer {
            margin-top: 5px !important;
        }

        /* 2. Full-bleed header with rounded bottom — app chrome look */
        .welcome-header {
            border-radius: 0 0 28px 28px !important;
            padding: 22px 18px 20px !important;
            margin-bottom: 16px !important;
            min-height: unset;
            align-items: center;
        }

        /* 3. Inner grid area gets symmetrical padding */
        .col-md-12 {
            padding-left: 12px !important;
            padding-right: 12px !important;
        }

        /* 4. App icon cards — iOS pill style */
        .dashboard-link-card {
            border-radius: 20px !important;
            height: 90px !important;
            padding: 12px 4px 10px !important;
            -webkit-tap-highlight-color: transparent;
            transition: transform 0.12s ease !important;
        }

        .dashboard-link-card:active {
            transform: scale(0.93) !important;
        }

        .dashboard-link-card:hover {
            transform: none !important;
        }

        /* 5. Icon: color schemes handled by classes above */
        .dashboard-link-card i {
            font-size: 20px !important;
            margin-bottom: 6px !important;
            width: 42px !important;
            height: 42px !important;
            border-radius: 13px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: none !important;
        }

        /* 6. Label text */
        .dashboard-link-card strong {
            font-size: 0.57rem !important;
            color: #374151 !important;
            letter-spacing: 0.03em;
            line-height: 1.3;
            white-space: normal !important;
            word-break: break-word;
        }

        /* 7. Toast slides up from bottom */
        .custom-toast {
            top: auto !important;
            bottom: 16px !important;
            right: 12px !important;
            left: 12px !important;
            width: auto !important;
            transform: translateY(130%) !important;
        }

        .custom-toast.show {
            transform: translateY(0) !important;
        }

        .college-branding {
            margin-bottom: 0px !important;
            padding: 10px !important;
            border-radius: 20px 20px 0 0 !important;
        }

        .college-name {
            font-size: 1.15rem !important;
            letter-spacing: -0.01em !important;
        }

        .college-affiliation {
            font-size: 0.65rem !important;
            white-space: normal !important;
        }
    }
</style>
<div class="container">
    <div id="mainContainer" class="clearfix">
        <div class="college-branding">
            <span class="college-name">GIIT PROFESSIONAL COLLEGE</span>
            <span class="college-affiliation">Affiliated to KOLHAN UNIVERSITY | Approved by HRD, Govt. of
                Jharkhand</span>
        </div>
        <div class="col-sm-12 col-md-12 pull-right">
            <div class="content-panel col-xs-12">
                <h4 class="welcome-header">
                    <div class="welcome-text">
                        Welcome, <?php echo htmlspecialchars($student['s_name'] ?? 'Student'); ?>
                        <?php if ($elective_group_name): ?>
                            <span class="giit-badge"
                                style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); margin-left: 10px;">
                                <i class="fa fa-tags"></i> <?php echo htmlspecialchars($elective_group_name); ?>
                            </span>
                        <?php endif; ?>
                        <span>Glad to have you back!</span>
                    </div>
                    <div class="course-info">
                        <span class="ci-icon"><i class="fa fa-graduation-cap"></i></span>
                        <span>
                            <?php echo htmlspecialchars($_SESSION['course']['course_name'] ?? ''); ?>
                            <span
                                class="ci-uni"><?php echo htmlspecialchars($_SESSION['course']['university_short_name'] ?? ''); ?></span>
                        </span>
                    </div>
                </h4>
                <div class="col-md-12">
                    <div class="celarfix">
                        <div id="elective_notification_area"></div>
                        <?php if ($due_fee != 0): ?>
                            <div class='alert alert-info alert-compact' role='alert'>
                                <i class="fa fa-credit-card" style="color: var(--primary); font-size: 1.2rem;"></i>
                                <div>
                                    Pending Fee: <b>Rs.<?php echo number_format($due_fee); ?></b>
                                    <small style="color: var(--text-muted); margin-left: 5px;">(Overdue by
                                        <?php echo $due_days; ?> days)</small>
                                </div>
                                <a href='/fee' target='_blank' class='alert-link'>Pay Now</a>
                            </div>
                        <?php endif; ?>
                        <div class="row row-compact">
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="student-profile.php" class="dashboard-link-card"
                                    style="animation-delay: 0.1s;">
                                    <div class="icon-box icon-blue">
                                        <i class="fa fa-user-circle"></i>
                                    </div>
                                    <strong>Profile</strong>
                                </a>
                            </div>
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="student_subject.php" class="dashboard-link-card"
                                    style="animation-delay: 0.15s;">
                                    <div class="icon-box icon-emerald">
                                        <i class="fa fa-book"></i>
                                    </div>
                                    <strong>Subjects</strong>
                                </a>
                            </div>
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="student-assignment.php" class="dashboard-link-card"
                                    style="animation-delay: 0.2s;">
                                    <div class="icon-box icon-violet">
                                        <i class="fa fa-tasks"></i>
                                    </div>
                                    <strong>Assignment</strong>
                                </a>
                            </div>
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="student-questionbank.php" class="dashboard-link-card"
                                    style="animation-delay: 0.25s;">
                                    <div class="icon-box icon-amber">
                                        <i class="fa fa-database"></i>
                                    </div>
                                    <strong>Question Bank</strong>
                                </a>
                            </div>
                        </div>
                        <div class="row row-compact">
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="student-university-questions.php" class="dashboard-link-card"
                                    style="animation-delay: 0.3s;">
                                    <div class="icon-box icon-orange">
                                        <i class="fa fa-university"></i>
                                    </div>
                                    <strong>University Ques</strong>
                                </a>
                            </div>
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="stud-class-summary.php" class="dashboard-link-card"
                                    style="animation-delay: 0.35s;">
                                    <div class="icon-box icon-cyan">
                                        <i class="fa fa-bar-chart"></i>
                                    </div>
                                    <strong>Class Summary</strong>
                                </a>
                            </div>
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="le/index.php" class="dashboard-link-card" style="animation-delay: 0.4s;">
                                    <div class="icon-box icon-rose">
                                        <i class="fa fa-language"></i>
                                    </div>
                                    <strong>Learn English</strong>
                                </a>
                            </div>
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="student-syllabus.php?university_id=<?php echo urlencode($university_id); ?>&course_id=<?php echo urlencode($course_id); ?>"
                                    class="dashboard-link-card" style="animation-delay: 0.45s;">
                                    <div class="icon-box icon-blue">
                                        <i class="fa fa-list-alt"></i>
                                    </div>
                                    <strong>Syllabus</strong>
                                </a>
                            </div>
                        </div>
                        <div class="row row-compact">
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="student_course.php?university_id=<?php echo urlencode($university_id); ?>&course_id=<?php echo urlencode($course_id); ?>"
                                    class="dashboard-link-card" style="animation-delay: 0.5s;">
                                    <div class="icon-box icon-emerald">
                                        <i class="fa fa-cubes"></i>
                                    </div>
                                    <strong>Course</strong>
                                </a>
                            </div>
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="student-fee.php" class="dashboard-link-card" style="animation-delay: 0.55s;">
                                    <div class="icon-box icon-violet">
                                        <i class="fa fa-credit-card-alt"></i>
                                    </div>
                                    <strong>My Fee</strong>
                                </a>
                            </div>
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="student_issue_doc.php" class="dashboard-link-card"
                                    style="animation-delay: 0.6s;">
                                    <div class="icon-box icon-amber">
                                        <i class="fa fa-file-pdf-o"></i>
                                    </div>
                                    <strong>Std. Docs</strong>
                                </a>
                            </div>
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="<?php echo (strpos($_SERVER['SCRIPT_NAME'], '/admission/') !== false) ? '../' : ''; ?>student_message.php" class="dashboard-link-card"
                                    style="position: relative; animation-delay: 0.65s;">
                                    <span class="giit-badge" id="global-chat-badge"
                                        style="top: 10px; right: 10px; position: absolute;">0</span>
                                    <div class="icon-box icon-orange">
                                        <i class="fa fa-comments-o"></i>
                                    </div>
                                    <strong>My Chat</strong>
                                </a>
                            </div>
                        </div>
                        <div class="row row-compact">
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="digital_locker.php" class="dashboard-link-card" style="animation-delay: 0.7s;">
                                    <div class="icon-box icon-cyan">
                                        <i class="fa fa-shield"></i>
                                    </div>
                                    <strong>Digi Locker</strong>
                                </a>
                            </div>
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="student-job-profile.php" class="dashboard-link-card"
                                    style="animation-delay: 0.75s;">
                                    <div class="icon-box icon-rose">
                                        <i class="fa fa-rocket"></i>
                                    </div>
                                    <strong>Jobs</strong>
                                </a>
                            </div>
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="student-online-classes.php" class="dashboard-link-card"
                                    style="animation-delay: 0.8s;">
                                    <div class="icon-box icon-blue">
                                        <i class="fa fa-calendar-check-o"></i>
                                    </div>
                                    <strong>Class Schedule</strong>
                                </a>
                            </div>
                            <div class="text-center col-xs-3 col-sm-3 col-compact">
                                <a href="logmeout.php" class="dashboard-link-card"
                                    style="border-color: rgba(244, 63, 94, 0.2); animation-delay: 0.85s;">
                                    <div class="icon-box icon-logout">
                                        <i class="fa fa-sign-out"></i>
                                    </div>
                                    <strong>Logout</strong>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- ==================== DOCUMENT REQUEST MODAL ==================== -->
<div id="docRequestModal" class="w3-modal" style="z-index:9999; display:none;">
    <div class="w3-modal-content w3-round-xlarge" style="max-width:480px;">
        <div class="w3-container w3-purple w3-padding-16">
            <span onclick="document.getElementById('docRequestModal').style.display='none'"
                class="w3-button w3-xlarge w3-display-topright">×</span>
            <h2 style="margin:0">Document Request</h2>
        </div>
        <div class="w3-container w3-padding-32 w3-center">
            <p style="font-size:1.3em; line-height:1.4">
                Do you want to receive your <strong id="docTypeName">document</strong>?
            </p>
            <div class="w3-margin-top">
                <button class="w3-button w3-xlarge w3-green w3-round-large" onclick="respondDocument(1)">
                    Self
                </button>

                <button class="w3-button w3-xlarge w3-red w3-round-large" onclick="respondDocument(0)">
                    Others
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Details Modal for filling form -->
<div id="docDetailsModal" class="w3-modal" style="z-index:10000; display:none;">
    <div class="w3-modal-content w3-round-xlarge" style="max-width:600px;">
        <div class="w3-container w3-purple w3-padding-16">
            <span onclick="document.getElementById('docDetailsModal').style.display='none'"
                class="w3-button w3-xlarge w3-display-topright">×</span>
            <h2 style="margin:0">Enter Document Details</h2>
        </div>
        <div class="w3-container w3-padding-16">
            <form id="detailsForm">
                <input type="hidden" name="issue_id" id="issue_id_hidden">
                <label>Relationship:</label>
                <select name="relationship_id" class="w3-select w3-border">
                    <option value="">Select Relationship</option>
                    <!-- Loaded dynamically -->
                </select><br><br>
                <label>Relationship Phone:</label>
                <input type="text" name="relationship_phone" class="w3-input w3-border"><br>
                <label>Relationship Aadhar:</label>
                <input type="text" name="relationship_aadhar" class="w3-input w3-border"><br>
                <label>Faculty ID:</label>
                <input type="number" name="faculty_id" class="w3-input w3-border"><br>
                <label>Is Active:</label>
                <input type="checkbox" name="is_active" checked><br><br>
                <button type="submit" class="w3-button w3-green w3-round-large">Save Details</button>
            </form>
        </div>
    </div>
</div>
<script>
    let currentIssueId = null;
    let videoStream = null;
    function checkDocumentRequest() {
        fetch('student-dashboard.php?action=check_doc_request&v=' + Date.now())
            .then(r => {
                if (!r.ok) throw new Error('Network response was not ok');
                return r.json();
            })
            .then(data => {
                if (data.has_request && data.issue_id) {
                    document.getElementById('docTypeName').textContent = data.document_type || 'document';
                    currentIssueId = data.issue_id;
                    document.getElementById('docRequestModal').style.display = 'block';
                }
            })
            .catch(err => {
                console.error('Document check failed:', err);
                // Optional: alert("Could not check document requests. Please try again later.");
            });
    }
    function respondDocument(answer) { // 1 = Self, 0 = Others
        if (!currentIssueId) return;
        const formData = new FormData();
        formData.append('issue_id', currentIssueId);
        formData.append('response', answer);
        fetch('student-dashboard.php?action=respond_doc', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(res => {
                document.getElementById('docRequestModal').style.display = 'none';
                if (res.success) {
                    if (answer === 0) { // Only open details modal for "Others"
                        document.getElementById('issue_id_hidden').value = currentIssueId;
                        loadRelationships();
                        document.getElementById('docDetailsModal').style.display = 'block';
                    } else {
                        alert("Response saved successfully!"); // Optional feedback for "Self"
                    }
                } else {
                    alert("Failed to save response.");
                }
                currentIssueId = null;
            })
            .catch(() => alert("Connection error"));
    }
    function loadRelationships() {
        fetch('student-dashboard.php?ajax=relationships')
            .then(r => r.json())
            .then(data => {
                let select = document.querySelector('select[name="relationship_id"]');
                select.innerHTML = '<option value="">Select Relationship</option>';
                data.forEach(r => {
                    let option = document.createElement('option');
                    option.value = r.id;
                    option.textContent = r.shortname ? r.shortname + ' - ' + r.relationship_name : r.relationship_name;
                    select.appendChild(option);
                });
            })
            .catch(err => {
                console.error('Failed to load relationships:', err);
                alert('Failed to load relationship options. Please try again.');
            });
    }
    document.getElementById('detailsForm').onsubmit = function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('student-dashboard.php?ajax=update_details', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert("Details saved successfully!");
                    document.getElementById('docDetailsModal').style.display = 'none';
                } else {
                    alert("Failed to save details.");
                }
            });
    };
    function checkElectiveForm() {
        fetch('student-dashboard.php?action=check_elective_form&v=' + Date.now())
            .then(r => r.json())
            .then(data => {
                if (data.has_form) {
                    const html = `
                    <div class="alert alert-warning alert-compact" role="alert">
                        <i class="fa fa-star" style="color: #f59e0b; font-size: 1.2rem;"></i>
                        <div style="flex-grow: 1;">
                            <div style="font-weight: 700;">Elective Choice Form Active</div>
                            <small style="color: var(--text-muted);">Deadline: ${data.deadline}</small>
                        </div>
                        ${data.submitted ?
                            `<span class="giit-badge" style="background: #10b981; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);">Submitted</span>` :
                            `<button onclick="openElectiveModal()" class="alert-link" style="border:none; cursor:pointer;">Fill Now</button>`
                        }
                    </div>
                `;
                    document.getElementById('elective_notification_area').innerHTML = html;
                }
            });
    }

    setInterval(checkDocumentRequest, 45000);
    setInterval(checkElectiveForm, 45000);

    // Polling handled by initChatNotifications() at bottom of file


    // Initial check on page load

    window.addEventListener('load', () => {
        checkDocumentRequest();
        checkElectiveForm();
    });

    let electiveData = null;
    function openElectiveModal() {
        $('#electiveSelectionModal').show();
        $('#elective_form_body').html('<div class="w3-center w3-padding-32"><i class="fa fa-spinner fa-spin w3-xxlarge"></i><p>Loading Options...</p></div>');

        fetch('student-dashboard.php?action=get_elective_form_data')
            .then(r => r.json())
            .then(data => {
                electiveData = data;
                renderElectiveForm();
            });
    }

    function renderElectiveForm() {
        let isSub = !!electiveData.submission;
        let html = `<h5>1. Choose Specialization Group</h5>${isSub ? '<p class="w3-text-green"><i class="fa fa-check-circle"></i> You have already submitted your choices.</p>' : ''}<div class="row">`;

        electiveData.groups.forEach(g => {
            let isSelected = isSub && electiveData.submission.group_id == g.id;
            html += `
            <div class="col-sm-6" style="margin-bottom:10px;">
                <div class="w3-card w3-padding w3-round-large group-card" style="cursor:pointer; border:1px solid ${isSelected ? '#8e24aa' : '#ddd'}; background:${isSelected ? '#fdf2ff' : '#fff'};" 
                     ${isSub ? '' : `onclick="selectSpecialization(${g.id}, this)"`}>
                    <input type="radio" name="group_id" value="${g.id}" style="display:none;" required ${isSelected ? 'checked' : ''}>
                    <b>${g.group_name}</b>
                </div>
            </div>
        `;
        });
        html += `</div><div id="subject_selection_area" style="${isSub ? 'display:block' : 'display:none'}; margin-top:20px;"><h5>2. Select Your Subjects</h5><div id="subjects_list"></div></div>`;
        $('#elective_form_body').html(html);

        if (isSub) {
            selectSpecialization(electiveData.submission.group_id, null, true);
            $('#submit_btn_area').hide();
        } else {
            $('#submit_btn_area').show();
        }
    }

    function selectSpecialization(gid, el, isPreview = false) {
        if (!isPreview) {
            $('.group-card').css({ 'border-color': '#ddd', 'background': '#fff' });
            $(el).css({ 'border-color': '#8e24aa', 'background': '#fdf2ff' });
            $(el).find('input').prop('checked', true);
        }

        $('#subject_selection_area').show();
        let subjects = electiveData.subjects.filter(s => s.group_id == gid);
        let electives = {};
        subjects.forEach(s => {
            if (!electives[s.elective_no]) electives[s.elective_no] = [];
            electives[s.elective_no].push(s);
        });

        let subChoices = isPreview ? JSON.parse(electiveData.submission.subject_choices) : {};

        let html = '';
        for (let eNo in electives) {
            let options = electives[eNo];
            let isComp = options.length === 1;
            html += `
            <div style="padding:10px; border:1px solid #eee; border-left:4px solid ${isComp ? '#4caf50' : '#ff9800'}; margin-bottom:10px; background:#fcfcfc;">
                <div class="row">
                    <div class="col-xs-8"><b>${eNo}</b> ${isComp ? '<span class="w3-tag w3-green w3-tiny w3-round">COMPULSORY</span>' : '<span class="w3-tag w3-orange w3-tiny w3-round">SELECT OPTION</span>'}</div>
                </div>
                <div style="margin-top:5px;">
        `;
            options.forEach(o => {
                let isSelected = isPreview && Object.values(subChoices).includes(o.id.toString());
                let checked = (isComp || isSelected) ? 'checked' : '';
                let disabled = isPreview ? 'disabled' : (isComp ? 'onclick="return false;"' : '');
                html += `
                <div style="margin-bottom:5px;">
                    <input type="radio" name="choices[${eNo}]" value="${o.id}" ${checked} ${disabled} required> ${o.subject_name} <small class="w3-text-grey">(${o.short_name})</small>
                </div>
            `;
            });
            html += `</div></div>`;
        }
        $('#subjects_list').html(html);
    }

    $(document).on('submit', '#electiveFormSubmit', function (e) {
        e.preventDefault();
        let btn = $(this).find('button[type="submit"]');
        btn.html('<i class="fa fa-spinner fa-spin"></i> Submitting...').prop('disabled', true);

        let formData = $(this).serialize();
        console.log("Submitting data:", formData); // Debugging

        $.post('student-dashboard.php?action=submit_elective_choice', formData, function (res) {
            if (res.success) {
                alert("Your choices have been submitted successfully!");
                location.reload();
            } else {
                alert("Error: " + res.error);
                btn.html('<i class="fa fa-save"></i> Submit My Choices').prop('disabled', false);
            }
        }, 'json').fail(function (xhr) {
            alert("Server Error: " + xhr.responseText);
            btn.html('<i class="fa fa-save"></i> Submit My Choices').prop('disabled', false);
        });
    });
</script>

<!-- Elective Selection Modal -->
<div id="electiveSelectionModal" class="w3-modal" style="z-index:11000;">
    <div class="w3-modal-content w3-round-xlarge w3-card-4 w3-animate-top" style="max-width:700px;">
        <header class="w3-container w3-padding-16"
            style="background: linear-gradient(135deg, #6a1b9a, #8e24aa); color: white; border-radius: 15px 15px 0 0;">
            <span onclick="$('#electiveSelectionModal').hide()"
                class="w3-button w3-xlarge w3-display-topright">&times;</span>
            <h3 style="margin:0"><i class="fa fa-book"></i> Elective Selection Form</h3>
        </header>
        <div class="w3-container w3-padding-24">
            <form id="electiveFormSubmit">
                <div id="elective_form_body"></div>
                <div id="submit_btn_area" class="w3-center w3-padding-16">
                    <hr>
                    <button type="submit" class="w3-button w3-purple w3-round-large w3-large">
                        <i class="fa fa-save"></i> Submit My Choices
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    let lastChatId = null; // Changed to null to allow initialization from localStorage

    function initChatNotifications() {
        const pathPrefix = window.location.pathname.includes('/admission/') ? '../' : '';
        // Use student ID for personalized storage
        const studentId = <?= $_SESSION['s_id'] ?>;
        const storageKey = 'giitchat_last_read_id_' + studentId;

        // Try to get last read ID from localStorage, else fetch from server
        const savedId = localStorage.getItem(storageKey);

        if (savedId) {
            lastChatId = parseInt(savedId);
            setInterval(checkNewChatMessages, 5000);
            checkNewChatMessages();
        } else {
            fetch(pathPrefix + 'student_message.php?ajax=get_latest_id')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        lastChatId = data.max_id;
                        setInterval(checkNewChatMessages, 5000);
                    }
                });
        }
    }

    function showToast(title, body) {
        const pathPrefix = window.location.pathname.includes('/admission/') ? '../' : '';
        const toast = document.createElement('div');
        toast.className = 'custom-toast';
        toast.onclick = () => {
            window.location.href = pathPrefix + 'student_message.php';
        };

        toast.innerHTML = `
            <div class="toast-header">
                <span style="font-weight: 800; color: #6366f1; font-size: 0.85rem;"><i class="fa fa-bell"></i> NEW MESSAGE</span>
                <button type="button" class="toast-close" onclick="event.stopPropagation(); this.parentElement.parentElement.remove()">&times;</button>
            </div>
            <div style="font-weight: 700; color: #1e293b; font-size: 0.95rem; margin-bottom: 4px;">${title}</div>
            <div class="toast-body">${body}</div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 100);

        setTimeout(() => {
            if (toast.parentElement) {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }
        }, 6000);
    }

    function checkNewChatMessages() {
        if (lastChatId === null) return;
        const pathPrefix = window.location.pathname.includes('/admission/') ? '../' : '';

        fetch(pathPrefix + `student_message.php?ajax=check_updates&last_id=${lastChatId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.new_message) {
                    const msg = data.new_message;

                    // Prevent duplicate notifications if multiple poll cycles happen fast
                    if (msg.id <= lastChatId) return;

                    lastChatId = msg.id;

                    // 1. Show UI Toast (In-app popup)
                    showToast(msg.source_name, msg.content);

                    // 2. Show Browser Notification and Play Sound
                    if (typeof GIITNotification !== 'undefined') {
                        GIITNotification.show(
                            "New Message from " + msg.source_name,
                            msg.content,
                            'images/message.png',
                            () => { window.location.href = pathPrefix + 'student_message.php'; }
                        );
                    }

                    // 3. Update Badge
                    if (typeof updateUINotificationBadge === 'function') {
                        updateUINotificationBadge('#global-chat-badge', 'NEW');
                    }
                }
            });
    }

    $(document).ready(function () {
        initChatNotifications();
        if (typeof checkDocumentRequest === 'function') checkDocumentRequest();
        if (typeof checkElectiveForm === 'function') checkElectiveForm();
    });
</script>