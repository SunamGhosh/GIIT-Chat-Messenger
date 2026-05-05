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
<style type="text/css">
    table {
        width: 100%;
    }

    [scope="row"] {
        text-align: center;
    }

    .content-panel.col-xs-12 p {
        margin: 0;
    }

    .press-coverage {
        background-color: #e4e4e4;
        margin-top: 20px;
        padding-bottom: 15px;
    }

    .press-coverage img {
        background-color: #fff;
        border-radius: 5px;
        box-shadow: 0 0 5px #ccc;
        display: block;
        margin: 0 auto;
        max-width: 100%;
        padding: 10px;
    }

    .media-title {
        background-color: #006699;
        color: rgb(255, 255, 255);
        font-size: 16px;
        font-weight: bold;
        padding: 12px 10px 11px;
    }

    ul {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .press-coverage li {
        padding: 15px 15px 0;
    }

    .page-title:after {
        display: none;
    }

    .row>div {
        height: 100px;
    }

    #st {
        font-size: 19px;
        font-family: serif;
        color: red;
        text-align: center;
        animation: animate 1.5s linear infinite;
    }

    @keyframes animate {
        0% {
            opacity: 0;
        }

        50% {
            opacity: 0.7;
        }

        100% {
            opacity: 0;
        }
    }

    .dashboard-icon {
        width: 35px;
        height: 45px;
        object-fit: contain;
        display: block;
        margin: 0 auto -30px auto;
        margin-top: -4px;
    }

    /* New styles for modals */
    #docDetailsModal .w3-modal-content {
        max-width: 600px;
    }

    #video,
    #canvas {
        width: 320px;
        height: 240px;
    }

    #photo {
        max-width: 320px;
        max-height: 240px;
        display: block;
        margin-top: 10px;
    }

    .response-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
        padding: 20px;
        border-radius: 8px;
    }

    .response-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        padding: 20px;
        border-radius: 8px;
    }

    /* Notification Toast Styles */
    .custom-toast {
        position: fixed;
        bottom: 20px;
        right: -400px;
        width: 320px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        padding: 15px;
        border-left: 5px solid #006699;
        display: flex;
        flex-direction: column;
        gap: 5px;
        cursor: pointer;
    }

    .custom-toast.show {
        right: 20px;
    }

    .toast-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
        color: #006699;
        font-size: 14px;
    }

    .toast-close {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #999;
        line-height: 1;
    }

    .toast-body {
        font-size: 13px;
        color: #444;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .toast-icon {
        margin-right: 8px;
    }
</style>
<div class="container">
    <div id="mainContainer" class="clearfix">
        <div class="col-sm-12 col-md-12 pull-right">
            <div class="content-panel col-xs-12">
                <h2 class="clearfix">
                    <span class="page-title col-xs-12">
                        Welcome <?php echo htmlspecialchars($student['s_name'] ?? 'Student'); ?>
                        <?php if ($elective_group_name): ?>
                            <span
                                style="font-size: 14px; background: #8e24aa; color: #fff; padding: 2px 10px; border-radius: 15px; margin-left: 10px; vertical-align: middle; display: inline-block;">
                                Elective: <?php echo htmlspecialchars($elective_group_name); ?>
                            </span>
                        <?php endif; ?>
                        <span class="pull-right">
                            <?php echo htmlspecialchars($_SESSION['course']['course_name'] ?? ''); ?>
                            (<?php echo htmlspecialchars($_SESSION['course']['university_short_name'] ?? ''); ?>)
                        </span>
                    </span>
                </h2>
                <div class="col-md-12">
                    <div class="celarfix">
                        <div id="elective_notification_area"></div>
                        <?php if ($due_fee != 0): ?>
                            <div class='alert alert-info alert-dismissible' role='alert'>
                                <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                    <span aria-hidden='true'>&times;</span>
                                </button>
                                Dear <b><?php echo htmlspecialchars($student['s_name'] ?? 'Student'); ?></b>,
                                your due fee of <b>Rs.<?php echo number_format($due_fee); ?></b>
                                is outstanding for <b><?php echo $due_days; ?> days</b>.
                                You can pay online at
                                <a href='/fee' target='_blank' class='alert-link'>mygiit.com/fee</a>.
                            </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="text-center col-xs-6 col-sm-3">
                                <a href="student-profile.php"><img src="images/my-profile.png" border="0" /></a>
                                <br /><strong>My Profile</strong>
                            </div>
                            <div class="text-center col-xs-6 col-sm-3">
                                <a href="student_subject.php"><img src="images/Courses.png" border="0" /></a>
                                <br /><strong>My Subjects & Chapters</strong>
                            </div>
                            <div class="text-center col-xs-6 col-sm-3">
                                <a href="student-assignment.php">
                                    <img src="images/download.png" border="0" />
                                </a>
                                <br /><strong>Assignments</strong>
                            </div>
                            <div class="text-center col-xs-6 col-sm-3">
                                <a href="student-questionbank.php">
                                    <img src="images/question.png" class="dashboard-icon" alt="Question Bank" />
                                </a>
                                <br /><strong>Question Bank</strong>
                            </div>
                        </div>
                        <div class="row">
                            <div class="text-center col-xs-6 col-sm-3">
                                <a href="student-university-questions.php">
                                    <img src="images/graduation.png" class="dashboard-icon" />
                                </a>
                                <br /><strong>University Questions</strong>
                            </div>
                            <div class="text-center col-xs-6 col-sm-3">
                                <a href="stud-class-summary.php"><img src="images/class.png" height="32" width="32"
                                        border="0" /></a>
                                <br /><strong id="st">Class Summary</strong>
                            </div>
                            <div class="text-center col-xs-6 col-sm-3">
                                <a href="le/index.php"><img src="images/learnEnglish.png" border="0" /></a>
                                <br /><strong>Learn English</strong>
                            </div>
                            <div class="text-center col-xs-6 col-sm-3">
                                <a
                                    href="student-syllabus.php?university_id=<?php echo urlencode($university_id); ?>&course_id=<?php echo urlencode($course_id); ?>">
                                    <img src="images/gnome-applications.png" border="0" />
                                </a>
                                <br /><strong>Syllabus</strong>
                            </div>
                        </div>
                        <div class="row">
                            <div class="text-center col-xs-6 col-sm-3">
                                <a
                                    href="student_course.php?university_id=<?php echo urlencode($university_id); ?>&course_id=<?php echo urlencode($course_id); ?>">
                                    <img src="images/gnome-applications.png" border="0" />
                                </a>
                                <br /><strong>Course Structure</strong>
                            </div>
                            <div class="text-center col-xs-6 col-sm-3">
                                <a href="student-fee.php"><img src="images/id-card.png" border="0" /></a>
                                <br /><strong>My Fee</strong>
                            </div>
                            <!-- <div class="text-center col-xs-6 col-sm-3">
                    <a href="notice.php"><img src="images/agt_announcements.png" border="0" /></a>
                    <br /><strong>Notices &amp; Announcements</strong>
                </div> -->
                            <div class="text-center col-xs-6 col-sm-3">
                                <a href="student_issue_doc.php"><img src="images/shield.png" border="0"
                                        class="dashboard-icon" /></a>
                                <br /><strong>My Issued Document</strong>
                            </div>
                            <div class="text-center col-xs-6 col-sm-3">
                                <a href="student_message.php"
                                    style="position: relative; display: inline-block;margin-bottom:12px">
                                    <span class="giit-badge" id="global-chat-badge">0</span>
                                    <img src="images/message.png" border="0" class="dashboard-icon" />
                                </a>
                                <br /><strong>My Chat</strong>
                            </div>

                        </div>
                        <div class="row">
                            <div class="text-center col-xs-6 col-sm-3">
                                <a href="digital_locker.php"><img src="images/digilocker.png" width="60px"
                                        border="0" /></a>
                                <br /><strong>My Digital Locker</strong>
                            </div>
                            <div class="text-center col-xs-6 col-sm-3">
                                <a href="student-job-profile.php"><img src="images/jobs-icon.jpg" border="0" /></a>
                                <br /><strong>Job Profile</strong>
                            </div>
                            <div class="text-center col-xs-6 col-sm-3">
                                <a href="student-online-classes.php"><img src="images/Time.png" border="0" /></a>
                                <br /><strong>Class Timetable</strong>
                            </div>
                            <div class="text-center col-xs-6 col-sm-3">
                                <a href="logmeout.php"><img src="images/gnome-logout.png" border="0" /></a>
                                <br /><strong>Logout</strong>
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
                    <div class="alert alert-warning alert-dismissible" role="alert" style="border-left: 5px solid #8e24aa; background: #fdf2ff;">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <div class="row">
                            <div class="col-sm-9">
                                <h4 style="margin-top:0; color:#8e24aa;"><i class="fa fa-book"></i> Elective Choice Form is Active!</h4>
                                <p>Please select your specialization and elective subjects for the current semester. <br>
                                <strong>Deadline: ${data.deadline}</strong></p>
                            </div>
                            <div class="col-sm-3 text-right">
                                ${data.submitted ?
                            `<button disabled class="btn btn-success" style="margin-top:10px;">
                                        <i class="fa fa-check-circle"></i> FORM SUBMITTED
                                     </button>` :
                            `<button onclick="openElectiveModal()" class="btn btn-primary" style="background:#8e24aa; border:none; margin-top:10px;">
                                        <i class="fa fa-pencil"></i> FILL FORM NOW
                                     </button>`
                        }
                            </div>
                        </div>
                    </div>
                `;
                    document.getElementById('elective_notification_area').innerHTML = html;
                }
            });
    }

    setInterval(checkDocumentRequest, 45000);
    setInterval(checkElectiveForm, 45000);

    // Background Chat Check
    let studentId = <?= $_SESSION['s_id'] ?>;
    let globalLastMessageId = null;
    let unreadCount = 0;
    let lastReadId = parseInt(localStorage.getItem('giitchat_last_read_id_' + studentId)) || 0;

    function checkChatUpdates() {
        const lastIdParam = globalLastMessageId !== null ? globalLastMessageId : 0;
        fetch(`student_message.php?ajax=check_updates&last_id=${lastIdParam}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.new_message) {
                    const msg = data.new_message;

                    // On first run, we anchor the real-time checker
                    if (globalLastMessageId === null) {
                        globalLastMessageId = msg.id;

                        // BUT, if this message is newer than what we last READ, show badge
                        if (msg.id > lastReadId) {
                            unreadCount = 1; // At least one
                            updateUINotificationBadge('#global-chat-badge', 'New');
                        }
                        return;
                    }

                    globalLastMessageId = msg.id;
                    unreadCount++;
                    updateUINotificationBadge('#global-chat-badge', unreadCount);

                    GIITNotification.show(
                        `New Message: ${msg.source_name}`,
                        msg.content,
                        'images/agt_announcements.png',
                        () => { window.location.href = 'student_message.php'; }
                    );
                } else if (data.success && globalLastMessageId === null) {
                    globalLastMessageId = 0;

                    // If no "new" messages found in last_id check, still check if overall latest > lastRead
                    checkInitialUnread();
                }
            });
    }

    function checkInitialUnread() {
        fetch(`student_message.php?ajax=get_latest_id`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.max_id > lastReadId) {
                    updateUINotificationBadge('#global-chat-badge', 'New', true);
                }
            });
    }


    setInterval(checkChatUpdates, 5000);
    setTimeout(checkChatUpdates, 1000);


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
        // Use student ID for personalized storage
        const studentId = <?= $_SESSION['s_id'] ?>;
        const storageKey = 'giitchat_last_read_id_' + studentId;
        
        // Try to get last read ID from localStorage, else fetch from server
        const savedId = localStorage.getItem(storageKey);
        
        if (savedId) {
            lastChatId = parseInt(savedId);
            // Start Polling immediately if we have a baseline
            setInterval(checkNewChatMessages, 5000);
            // Check once immediately to catch messages sent since last logout
            checkNewChatMessages();
        } else {
            // First time or cleared storage: Get latest from server
            fetch('student_message.php?ajax=get_latest_id')
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
        const toast = document.createElement('div');
        toast.className = 'custom-toast';
        toast.onclick = () => {
            window.location.href = 'student_message.php';
        };

        toast.innerHTML = `
            <div class="toast-header">
                <span><i class="fa fa-bell toast-icon"></i> New Message</span>
                <button type="button" class="toast-close" onclick="event.stopPropagation(); this.parentElement.parentElement.remove()">&times;</button>
            </div>
            <div style="font-size: 11px; color: #006699; font-weight: 600;">${title}</div>
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

        fetch(`student_message.php?ajax=check_updates&last_id=${lastChatId}`)
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
                            () => { window.location.href = 'student_message.php'; }
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
<?php include("footer.php"); ?>