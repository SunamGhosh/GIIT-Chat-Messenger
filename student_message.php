<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// Database connection
require_once 'configNew.php';
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($con, "utf8mb4");

if (!isset($_SESSION['s_id'])) {
    header('location:student-login.php');
    exit;
}

$student_id = $_SESSION['s_id'];
$university = $_SESSION['course']['university_name'] ?? '';
$course = $_SESSION['course']['course_name'] ?? '';
$session_id = $_SESSION['s_session_id'] ?? '';
$semester = "Sem" . ($_SESSION['s_sem'] ?? 1);

// AJAX handlers
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    if ($_GET['ajax'] == 'get_messages') {
        $groupId = isset($_GET['groupId']) && $_GET['groupId'] !== 'null' ? intval($_GET['groupId']) : null;

        $sql = "SELECT m.*, 
                CASE 
                    WHEN m.sender_id = ? THEN 'You' 
                    ELSE 'Faculty/Admin' 
                END as sender_name 
                FROM messages m WHERE 1=1";

        if ($groupId) {
            $sql .= " AND m.groupId = $groupId";
        } else {
            // Academic Notices logic
            $sql .= " AND (m.groupId IS NULL OR m.groupId = '0' OR m.groupId = '' OR m.groupId LIKE 'BCT-%')";

            // Logic: Show if (targeted to me) OR (broadcast AND matches my filters)
            $sql .= " AND (m.receiver_id = $student_id OR (
                (m.receiver_id IS NULL OR m.receiver_id = 0)";

            if ($university)
                $sql .= " AND (m.university = '" . mysqli_real_escape_string($con, $university) . "' OR m.university = '')";
            if ($course)
                $sql .= " AND (m.course = '" . mysqli_real_escape_string($con, $course) . "' OR m.course = '')";
            if ($session_id)
                $sql .= " AND (m.session = '" . mysqli_real_escape_string($con, $session_id) . "' OR m.session = '')";
            if ($semester)
                $sql .= " AND (m.semester = '" . mysqli_real_escape_string($con, $semester) . "' OR m.semester = '')";

            $sql .= "))";
        }
        $sql .= " ORDER BY m.createdAt ASC";

        // Mark messages as read
        $updateSql = "UPDATE messages SET is_read = 1, read_at = NOW() 
                     WHERE (receiver_id = ? OR (receiver_id IS NULL OR receiver_id = 0)) 
                     AND is_read = 0 AND sender_id != ?";
        $updStmt = $con->prepare($updateSql);
        $updStmt->bind_param("ii", $student_id, $student_id);
        $updStmt->execute();

        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        // Debug log
        error_log("GIITChat Debug: Student ID $student_id, Query: $sql, Found: " . count($messages));

        echo json_encode(['success' => true, 'messages' => $messages]);
        exit;
    }

    if ($_GET['ajax'] == 'send_message' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = mysqli_real_escape_string($con, $_POST['content']);
        $groupId = isset($_POST['groupId']) && $_POST['groupId'] !== 'null' ? intval($_POST['groupId']) : 0;

        if (empty($content)) {
            echo json_encode(['success' => false, 'error' => 'Message is empty']);
            exit;
        }

        // Students can only send to custom groups, not Academic Notices (usually)
        if ($groupId == 0) {
            echo json_encode(['success' => false, 'error' => 'Cannot send to Academic Notices']);
            exit;
        }

        $insert = "INSERT INTO messages (sender_id, content, groupId, createdAt) VALUES (?, ?, ?, NOW())";
        $stmt = $con->prepare($insert);
        $stmt->bind_param("isi", $student_id, $content, $groupId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        exit;
    }

    if ($_GET['ajax'] == 'check_updates') {
        $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

        $sql = "SELECT m.id, m.content, m.sender_id, m.groupId, 
                CASE 
                    WHEN m.groupId IS NULL OR m.groupId = '0' OR m.groupId = '' OR m.groupId LIKE 'BCT-%' THEN 'Academic Notice' 
                    ELSE g.group_name 
                END as source_name
                FROM messages m
                LEFT JOIN chat_groups g ON m.groupId = g.id
                WHERE m.id > ?
                AND (
                    ((m.groupId IS NULL OR m.groupId = '0' OR m.groupId = '' OR m.groupId LIKE 'BCT-%') AND (m.receiver_id = ? OR m.receiver_id IS NULL OR m.receiver_id = 0))
                    OR m.groupId IN (SELECT group_id FROM group_members WHERE user_id = ? AND user_role = 'student')
                )
                AND m.sender_id != ?
                ORDER BY m.id DESC LIMIT 1";

        $stmt = $con->prepare($sql);
        $stmt->bind_param("iiii", $last_id, $student_id, $student_id, $student_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            echo json_encode(['success' => true, 'new_message' => $row]);
        } else {
            echo json_encode(['success' => true, 'new_message' => null]);
        }
        exit;
    }

    if ($_GET['ajax'] == 'get_latest_id') {
        $sql = "SELECT MAX(m.id) as max_id FROM messages m
                WHERE ((m.groupId IS NULL OR m.groupId = '0' OR m.groupId = '' OR m.groupId LIKE 'BCT-%') AND (m.receiver_id = ? OR m.receiver_id IS NULL OR m.receiver_id = 0))
                OR m.groupId IN (SELECT group_id FROM group_members WHERE user_id = ? AND user_role = 'student')";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ii", $student_id, $student_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        echo json_encode(['success' => true, 'max_id' => $row['max_id'] ?: 0]);
        exit;
    }

    if ($_GET['ajax'] == 'save_fcm_token' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $token = mysqli_real_escape_string($con, $_POST['token']);
        if (!empty($token)) {
            $stmt = $con->prepare("UPDATE student SET fcm_token = ? WHERE s_id = ?");
            $stmt->bind_param("si", $token, $student_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Token is empty']);
        }
        exit;
    }
    if ($_GET['ajax'] == 'get_group_members') {
        $g_id = intval($_GET['groupId'] ?? 0);
        if ($g_id > 0) {
            $sql = "SELECT gm.user_role, s.s_name, s.s_roll_no 
                    FROM group_members gm 
                    LEFT JOIN student s ON gm.user_id = s.s_id AND gm.user_role = 'student'
                    WHERE gm.group_id = $g_id";
            $res = $con->query($sql);
            $members = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    if ($row['user_role'] === 'student') {
                        $members[] = ['name' => $row['s_name'], 'role' => 'Student', 'info' => $row['s_roll_no']];
                    } else {
                        $members[] = ['name' => 'Faculty Admin', 'role' => ucfirst($row['user_role']), 'info' => '-'];
                    }
                }
            }
            echo json_encode(['success' => true, 'members' => $members]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid Group']);
        }
        exit;
    }
}


include("header.php");
?>
<script src="notification_helper.js"></script>
<meta name="author" content="Sunam Ghosh --@SunamGhosh">

<style type="text/css">
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

    /* Hide Site Headers */
    .header1, .header2, .header3, #mu-menu {
        display: none !important;
    }

    body {
        background-color: #f1f5f9;
        font-family: 'Inter', sans-serif;
        padding-top: 0 !important;
    }

    .chat-wrapper {
        padding: 20px 0;
    }

    .chat-container {
        display: flex;
        height: calc(100vh - 140px);
        min-height: 600px;
        background: var(--bg-main);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border-medium);
        position: relative;
    }

    /* Decorative top bar */
    .chat-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--grad-primary);
        z-index: 100;
    }

    .chat-sidebar {
        width: 320px;
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
        position: relative;
    }

    .sidebar-header {
        padding: 24px 20px;
        background: var(--bg-main);
        border-bottom: 1px solid var(--border-medium);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .sidebar-header h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--primary);
        letter-spacing: -0.02em;
    }

    .group-list {
        flex: 1;
        overflow-y: auto;
        padding: 15px 12px;
    }

    .group-list::-webkit-scrollbar {
        width: 5px;
    }

    .group-list::-webkit-scrollbar-thumb {
        background: var(--border-medium);
        border-radius: 10px;
    }

    .group-item {
        padding: 14px 16px;
        border-radius: 14px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 10px;
        background: var(--bg-main);
        border: 1px solid transparent;
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
        box-shadow: var(--shadow-sm);
    }

    .group-item:hover {
        background: #ffffff;
        border-color: var(--primary-light);
        transform: translateX(4px);
        box-shadow: var(--shadow-md);
    }

    .group-item.active {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(168, 85, 247, 0.04) 100%);
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-left: 4px solid var(--primary);
    }

    .group-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--primary-light);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .group-item.active .group-icon {
        background: var(--primary);
        color: white;
    }

    .group-info {
        flex: 1;
        overflow: hidden;
    }

    .group-name {
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--text-main);
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .group-meta {
        font-size: 0.75rem;
        color: var(--text-muted);
        display: block;
    }

    .chat-header {
        padding: 20px 25px;
        border-bottom: 1px solid var(--border-medium);
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: space-between;
        z-index: 10;
    }

    .chat-header h4 {
        margin: 0;
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--text-main);
    }

    .messages-container {
        flex: 1;
        padding: 25px;
        overflow-y: auto;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .messages-container::-webkit-scrollbar {
        width: 6px;
    }

    .messages-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .message {
        max-width: 75%;
        padding: 12px 16px;
        border-radius: 16px;
        font-size: 0.9rem;
        line-height: 1.5;
        position: relative;
        animation: messageFadeIn 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    @keyframes messageFadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .message.received {
        align-self: flex-start;
        background: white;
        color: var(--text-main);
        border-bottom-left-radius: 4px;
        border: 1px solid var(--border-soft);
    }

    .message.sent {
        align-self: flex-end;
        background: var(--grad-primary);
        color: white;
        border-bottom-right-radius: 4px;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
    }

    .message-info {
        font-size: 0.7rem;
        font-weight: 600;
        margin-bottom: 4px;
        display: block;
        opacity: 0.7;
    }

    .message.sent .message-info {
        text-align: right;
    }

    .message-time {
        font-size: 0.65rem;
        display: block;
        margin-top: 6px;
        opacity: 0.6;
        text-align: right;
    }

    .chat-input-area {
        padding: 20px 25px;
        background: white;
        border-top: 1px solid var(--border-medium);
    }

    .input-wrapper {
        background: #f1f5f9;
        border-radius: 15px;
        padding: 5px 5px 5px 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid transparent;
        transition: all 0.2s;
    }

    .input-wrapper:focus-within {
        background: white;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .chat-input {
        flex: 1;
        background: transparent;
        border: none;
        outline: none;
        padding: 10px 0;
        font-size: 0.9rem;
        color: var(--text-main);
    }

    .send-btn {
        background: var(--grad-primary);
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 8px rgba(99, 102, 241, 0.2);
    }

    .send-btn:hover {
        transform: scale(1.05) translateY(-2px);
        box-shadow: 0 6px 12px rgba(99, 102, 241, 0.3);
    }

    .no-messages {
        text-align: center;
        padding: 40px;
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .notice-lock {
        padding: 12px;
        text-align: center;
        background: #fff7ed;
        color: #9a3412;
        font-size: 0.75rem;
        font-weight: 600;
        border-top: 1px solid #ffedd5;
    }

    /* Notification Badge */
    .notification-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: var(--danger);
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
        min-width: 18px;
        height: 18px;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--bg-sidebar);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
        70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    .group-item.has-new .notification-badge {
        display: flex;
    }

    /* Custom Toast */
    .custom-toast {
        position: fixed;
        top: 20px;
        right: 20px;
        width: 320px;
        background: white;
        border-radius: 16px;
        box-shadow: var(--shadow-lg);
        z-index: 10000;
        padding: 16px;
        border: 1px solid var(--border-medium);
        transform: translateY(-150%);
        transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .custom-toast.show {
        transform: translateY(0);
    }

    /* Responsive */
    @media (max-width: 991px) {
        .chat-container {
            height: calc(100vh - 40px);
            border-radius: 0;
        }
    }

    @media (max-width: 767px) {
        .chat-container {
            flex-direction: column;
            height: 100vh;
            border-radius: 0;
            border: none;
        }

        .chat-sidebar {
            width: 100%;
            height: auto;
            border-right: none;
            border-bottom: 1px solid var(--border-medium);
            background: white;
        }

        .sidebar-header {
            padding: 10px 15px;
            border-bottom: none;
        }

        .sidebar-header h3 {
            font-size: 0.9rem;
        }

        .group-list {
            display: flex;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 0 15px 10px 15px;
            gap: 10px;
            -webkit-overflow-scrolling: touch;
        }

        .group-list::-webkit-scrollbar {
            height: 0; /* Hide scrollbar but keep functionality */
        }

        .group-item {
            flex: 0 0 auto;
            width: auto;
            min-width: 120px;
            max-width: 180px;
            padding: 8px 12px;
            margin-bottom: 0;
            flex-direction: row;
            gap: 8px;
            box-shadow: none;
            border: 1px solid var(--border-medium);
        }

        .group-item.active {
            border-left: 1px solid rgba(99, 102, 241, 0.2);
            border-bottom: 4px solid var(--primary);
            background: var(--primary-light);
        }

        .group-item:hover {
            transform: none;
        }

        .group-icon {
            width: 28px;
            height: 28px;
            font-size: 0.8rem;
            border-radius: 8px;
        }

        .group-name {
            font-size: 0.75rem;
        }

        .group-meta {
            display: none; /* Hide extra info on mobile row to save space */
        }

        .chat-main {
            flex: 1;
            height: 0; /* Important for flex child with overflow */
        }

        .chat-header {
            padding: 12px 15px;
        }

        .chat-header h4 {
            font-size: 0.95rem;
        }

        .messages-container {
            padding: 15px;
        }

        .message {
            max-width: 92%;
            font-size: 0.85rem;
        }

        .chat-input-area {
            padding: 10px 15px;
        }

        .notification-badge {
            top: -2px;
            right: -2px;
            border-width: 1px;
        }
    }
</style>

<div class="chat-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="chat-container">
                    <!-- Sidebar -->
                    <div class="chat-sidebar">
                        <div class="sidebar-header">
                            <h3><i class="fa fa-comments"></i> GIITChat</h3>
                        </div>
                        <div class="group-list">
                            <!-- Academic Notices -->
                            <div class="group-item active" id="group-0"
                                onclick="selectGroup(null, 'Academic Notices', 'System generated')">
                                <div class="group-icon">
                                    <i class="fa fa-bullhorn"></i>
                                </div>
                                <div class="group-info">
                                    <span class="group-name">Academic Notices</span>
                                    <span class="group-meta"><?= htmlspecialchars($course) ?> | <?= htmlspecialchars($semester) ?></span>
                                </div>
                                <span class="notification-badge" id="badge-0">0</span>
                            </div>

                            <!-- User Groups -->
                            <?php
                            $g_query = "SELECT g.* FROM chat_groups g 
                                       JOIN group_members m ON g.id = m.group_id 
                                       WHERE m.user_id = ? AND m.user_role = 'student'";
                            $g_stmt = $con->prepare($g_query);
                            $g_stmt->bind_param("i", $student_id);
                            $g_stmt->execute();
                            $g_res = $g_stmt->get_result();
                            while ($group = $g_res->fetch_assoc()):
                                ?>
                                    <div class="group-item" id="group-<?= $group['id'] ?>"
                                        onclick="selectGroup(<?= $group['id'] ?>, '<?= addslashes($group['group_name']) ?>', 'Group Chat')">
                                        <div class="group-icon">
                                            <i class="fa fa-users"></i>
                                        </div>
                                        <div class="group-info">
                                            <span class="group-name"><?= htmlspecialchars($group['group_name']) ?></span>
                                            <span class="group-meta">Joined on <?= date('d M Y', strtotime($group['created_at'])) ?></span>
                                        </div>
                                        <span class="notification-badge" id="badge-<?= $group['id'] ?>">0</span>
                                    </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Main Chat Area -->
                    <div class="chat-main">
                        <div class="chat-header">
                            <div>
                                <h4 id="active-name">Academic Notices</h4>
                                <div id="active-meta" class="group-meta">System generated</div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn btn-sm btn-default" id="view-members-btn" style="display:none; border-radius: 10px;"
                                    onclick="viewGroupMembers()"><i class="fa fa-users"></i> Members</button>
                                <button class="btn btn-sm btn-primary" style="border-radius: 10px; background: var(--grad-primary); border: none;" onclick="fetchMessages()"><i
                                        class="fa fa-refresh"></i> Refresh</button>
                            </div>
                        </div>

                        <div class="messages-container" id="messages-list">
                            <div class="no-messages">
                                <i class="fa fa-spinner fa-spin"></i> Loading conversation...
                            </div>
                        </div>

                        <!-- Input Area -->
                        <div class="chat-input-area" id="input-container" style="display:none;">
                            <form id="message-form">
                                <div class="input-wrapper">
                                    <input type="text" id="message-input" class="chat-input"
                                        placeholder="Type your message here...">
                                    <button class="send-btn" type="submit">
                                        <i class="fa fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div id="notice-only" class="notice-lock">
                            <i class="fa fa-lock"></i> Only faculty and admins can post in Academic Notices.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    let currentGroupId = null;
    let globalLastMessageId = null;

    function selectGroup(id, name, meta) {
        currentGroupId = id;
        document.getElementById('active-name').innerText = name;
        document.getElementById('active-meta').innerText = meta;

        // UI highlight
        document.querySelectorAll('.group-item').forEach(el => el.classList.remove('active'));
        const activeEl = document.getElementById(id ? `group-${id}` : 'group-0');
        if (activeEl) activeEl.classList.add('active');

        // Clear badge for this group
        const badge = document.getElementById(id ? `badge-${id}` : 'badge-0');
        if (badge) {
            badge.innerText = '0';
            activeEl.classList.remove('has-new');
        }

        // Toggle members button
        const membersBtn = document.getElementById('view-members-btn');
        if (id) {
            membersBtn.style.display = 'inline-block';
        } else {
            membersBtn.style.display = 'none';
        }

        // Toggle input area
        const inputArea = document.getElementById('input-container');
        const noticeOnly = document.getElementById('notice-only');
        if (id) {
            inputArea.style.display = 'block';
            noticeOnly.style.display = 'none';
        } else {
            inputArea.style.display = 'none';
            noticeOnly.style.display = 'block';
        }

        fetchMessages();
    }

    function updateBadge(groupId) {
        const id = groupId || 0;
        const badge = document.getElementById(`badge-${id}`);
        const groupEl = document.getElementById(`group-${id}`);
        if (badge && groupEl) {
            let count = parseInt(badge.innerText) || 0;
            badge.innerText = count + 1;
            groupEl.classList.add('has-new');
        }
    }

    function showNotification(title, body, groupId = null) {
        // Update badge regardless
        updateBadge(groupId);

        // Don't show toast/native if we are already looking at that group AND the window is focused
        const isCurrentGroup = (groupId == currentGroupId || (groupId == 0 && currentGroupId == null));
        if (isCurrentGroup && document.hasFocus()) {
            fetchMessages();
            return;
        }

        // 1. Show UI Toast (In-app)
        const toast = document.createElement('div');
        toast.className = 'custom-toast';
        toast.style.cursor = 'pointer';
        toast.onclick = () => {
            selectGroup(groupId, title, groupId ? 'Group Chat' : 'System generated');
            toast.remove();
        };

        toast.innerHTML = `
            <div class="toast-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-weight: 800; color: var(--primary); font-size: 0.85rem;"><i class="fa fa-bell"></i> NEW MESSAGE</span>
                <button type="button" class="toast-close" style="background:none; border:none; color: var(--text-muted); cursor:pointer; font-size: 1.2rem;">&times;</button>
            </div>
            <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem; margin-bottom: 4px;">${title}</div>
            <div class="toast-body" style="font-size: 0.85rem; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${body}</div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 100);

        // 2. Show Native Browser Notification & Play Sound
        GIITNotification.show(
            `New Message: ${title}`,
            body,
            'images/agt_announcements.png',
            () => {
                selectGroup(groupId, title, groupId ? 'Group Chat' : 'System generated');
            }
        );

        setTimeout(() => {
            if (toast.parentElement) {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }
        }, 6000);
    }


    function checkUpdates() {
        // If null, we might need to initialize it from the server
        const lastIdParam = globalLastMessageId !== null ? globalLastMessageId : 0;

        fetch(`student_message.php?ajax=check_updates&last_id=${lastIdParam}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.new_message) {
                    const msg = data.new_message;

                    // If this was the first run (globalLastMessageId was null), 
                    // just initialize it without showing notification for old messages
                    if (globalLastMessageId === null) {
                        globalLastMessageId = msg.id;
                        return;
                    }

                    globalLastMessageId = msg.id;
                    showNotification(msg.source_name, msg.content, msg.groupId);
                } else if (data.success && globalLastMessageId === null) {
                    // No messages at all yet, set to 0 to stop re-initializing
                    globalLastMessageId = 0;
                }
            });
    }

    function fetchMessages() {
        const container = document.getElementById('messages-list');

        fetch(`student_message.php?ajax=get_messages&groupId=${currentGroupId || 'null'}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update globalLastMessageId if it's the latest
                    if (data.messages.length > 0) {
                        const latestId = data.messages[data.messages.length - 1].id;
                        if (globalLastMessageId === null || latestId > globalLastMessageId) {
                            globalLastMessageId = latestId;
                        }
                    }

                    if (data.messages.length === 0) {
                        container.innerHTML = '<div class="no-messages">No messages yet.</div>';
                        return;
                    }

                    container.innerHTML = '';
                    data.messages.forEach(msg => {
                        const isSent = msg.sender_id == <?= $student_id ?>;
                        const div = document.createElement('div');
                        div.className = `message ${isSent ? 'sent' : 'received'}`;

                        const time = new Date(msg.createdAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        const sender = isSent ? 'You' : (msg.sender_name || 'Admin');

                        div.innerHTML = `
                            <span class="message-info">${sender}</span>
                            ${msg.content}
                            <span class="message-time">${time}</span>
                        `;
                        container.appendChild(div);
                    });
                    container.scrollTop = container.scrollHeight;

                    // Persistence: Update last read ID in localStorage
                    if (data.messages.length > 0) {
                        const latestId = data.messages[data.messages.length - 1].id;
                        localStorage.setItem('giitchat_last_read_id_<?= $student_id ?>', latestId);
                    }
                }
            });

    }

    document.getElementById('message-form').onsubmit = function (e) {
        e.preventDefault();
        const input = document.getElementById('message-input');
        const content = input.value.trim();

        if (!content || !currentGroupId) return;

        const formData = new FormData();
        formData.append('content', content);
        formData.append('groupId', currentGroupId);

        fetch('student_message.php?ajax=send_message', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    fetchMessages();
                } else {
                    alert(data.error || 'Failed to send message');
                }
            });
    };

    // Initial load
    fetchMessages();

    // Auto-refresh active chat every 10 seconds
    setInterval(fetchMessages, 10000);

    // Background check for updates in other groups every 5 seconds (slightly faster for better UX)
    setInterval(checkUpdates, 5000);

    // Initial update check to set globalLastMessageId
    setTimeout(checkUpdates, 1000);

    function viewGroupMembers() {
        if (!currentGroupId) return;
        const groupName = document.getElementById('active-name').innerText;
        $('#gm-title').text(groupName);
        $('#group_members_body').html('<div class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin"></i> Loading members...</div>');
        $('#groupMembersModal').modal('show');

        fetch(`student_message.php?ajax=get_group_members&groupId=${currentGroupId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let html = '<table class="table table-condensed"><thead><tr><th>Name</th><th>Role</th><th>Info</th></tr></thead><tbody>';
                    data.members.forEach(m => {
                        html += `<tr>
                            <td style="font-weight: 600;">${m.name}</td>
                            <td><span class="label ${m.role === 'Student' ? 'label-info' : 'label-primary'}">${m.role}</span></td>
                            <td class="text-muted small">${m.info}</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    $('#group_members_body').html(html);
                } else {
                    $('#group_members_body').html(`<div class="alert alert-danger">${data.error}</div>`);
                }
            });
    }
</script>

<div class="modal fade" id="groupMembersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-users"></i> Group Members: <span id="gm-title"></span></h4>
            </div>
            <div class="modal-body" id="group_members_body" style="max-height: 60vh; overflow-y: auto;">
                <!-- Members will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>