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
    .content-panel {
        margin-top: 20px;
        min-height: 500px;
    }

    .page-title:after {
        display: none;
    }

    .chat-layout {
        display: flex;
        height: 70vh; /* Changed from fixed 600px to viewport relative */
        min-height: 500px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }


    .chat-sidebar {
        width: 300px;
        border-right: 1px solid #ddd;
        display: flex;
        flex-direction: column;
        background: #f8f9fa;
    }

    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #fff;
    }

    .sidebar-header {
        padding: 15px;
        background: #006699;
        color: white;
        font-weight: bold;
    }

    .group-list {
        flex: 1;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch; /* For smooth mobile scrolling */
    }


    .group-item {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background 0.2s;
    }

    .group-item:hover {
        background: #f0f7ff;
    }

    .group-item.active {
        background: #e1f0ff;
        border-left: 4px solid #006699;
    }

    .group-name {
        font-weight: 600;
        display: block;
    }

    .group-meta {
        font-size: 11px;
        color: #777;
    }

    .chat-header {
        padding: 12px 15px;
        border-bottom: 1px solid #ddd;
        background: #fdfdfd;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .messages-container {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch; /* For smooth mobile scrolling */
        background: #f4f7f9;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }


    .message {
        max-width: 80%;
        padding: 10px 15px;
        border-radius: 8px;
        font-size: 13px;
        position: relative;
        line-height: 1.4;
    }

    .message.received {
        align-self: flex-start;
        background: #fff;
        border: 1px solid #eee;
        color: #333;
    }

    .message.sent {
        align-self: flex-end;
        background: #006699;
        color: #fff;
    }

    .message-info {
        font-size: 10px;
        margin-bottom: 4px;
        display: block;
        opacity: 0.8;
    }

    .message-time {
        font-size: 9px;
        text-align: right;
        display: block;
        margin-top: 4px;
        opacity: 0.7;
    }

    .chat-input-area {
        padding: 15px;
        border-top: 1px solid #ddd;
        background: #fff;
    }

    .input-group-btn .btn {
        background: #006699;
        color: #white;
        border: 1px solid #006699;
    }

    .input-group-btn .btn:hover {
        background: #004d73;
        color: white;
    }

    .no-messages {
        text-align: center;
        margin-top: 50px;
        color: #888;
    }

    @media (max-width: 768px) {
        .chat-layout {
            flex-direction: column;
            height: auto; /* Let it expand to content or use a safe fixed height */
            min-height: 600px;
            max-height: 90vh;
            margin-top: 10px;
            overflow: visible; /* Allow internal containers to handle overflow */
        }

        .chat-sidebar {
            width: 100%;
            height: 150px;
            min-height: 150px;
            max-height: 150px;
            border-right: none;
            border-bottom: 1px solid #ddd;
        }

        .chat-main {
            height: 450px; /* Fixed height for the chat area on mobile to force internal scroll */
        }

        .messages-container {
            height: 350px; /* Explicit height to ensure scrolling */
            -webkit-overflow-scrolling: touch;
            touch-action: pan-y;
        }

        .message {
            max-width: 95%;
        }

        .chat-input-area {
            padding: 8px;
        }
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

    .notification-badge {
        background: #ff4757;
        color: white;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        float: right;
        display: none;
        /* Hidden by default */
        margin-top: 3px;
        font-weight: 700;
    }

    .group-item.has-new .notification-badge {
        display: block;
    }
</style>

<div class="container">
    <div id="mainContainer" class="clearfix">
        <div class="col-sm-12 col-md-1">
            <br>
        </div>
        <div class="col-sm-12 col-md-11">
            <div class="content-panel col-xs-12">
                <h2 class="clearfix">
                    <span class="page-title col-xs-12">GIITChat Messenger
                        <span class="pull-right" style="font-size: 14px;">
                            <?php echo htmlspecialchars($_SESSION['course']['course_name'] ?? 'Unknown'); ?>
                        </span>
                    </span>
                </h2>

                <div class="chat-layout">
                    <!-- Sidebar -->
                    <div class="chat-sidebar">
                        <div class="sidebar-header">Channels</div>
                        <div class="group-list">
                            <!-- Academic Notices -->
                            <div class="group-item active" id="group-0"
                                onclick="selectGroup(null, 'Academic Notices', 'System generated')">
                                <span class="notification-badge" id="badge-0">0</span>
                                <span class="group-name">📢 Academic Notices</span>
                                <span class="group-meta"><?= htmlspecialchars($course) ?> |
                                    <?= htmlspecialchars($semester) ?></span>
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
                                    <span class="notification-badge" id="badge-<?= $group['id'] ?>">0</span>
                                    <span class="group-name">👥 <?= htmlspecialchars($group['group_name']) ?></span>
                                    <span class="group-meta">Joined on
                                        <?= date('d M Y', strtotime($group['created_at'])) ?></span>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Main Chat -->
                    <div class="chat-main">
                        <div class="chat-header">
                            <div>
                                <strong id="active-name">Academic Notices</strong>
                                <div id="active-meta" class="group-meta">System generated</div>
                            </div>
                            <div style="display: flex; gap: 5px; align-items: center;">
                                <button class="btn btn-xs btn-info" id="view-members-btn" style="display:none;" onclick="viewGroupMembers()"><i class="fa fa-users"></i> Members</button>
                                <button class="btn btn-xs btn-default" onclick="fetchMessages()"><i class="fa fa-refresh"></i> Refresh</button>
                            </div>
                        </div>

                        <div class="messages-container" id="messages-list">
                            <div class="no-messages">Loading messages...</div>
                        </div>

                        <div class="chat-input-area" id="input-container" style="display:none;">
                            <form id="message-form">
                                <div class="input-group">
                                    <input type="text" id="message-input" class="form-control"
                                        placeholder="Type your message here...">
                                    <span class="input-group-btn">
                                        <button class="btn btn-primary" type="submit">Send</button>
                                    </span>
                                </div>
                            </form>
                        </div>
                        <div id="notice-only"
                            style="padding: 15px; text-align: center; color: #777; font-size: 12px; background: #f9f9f9; border-top: 1px solid #ddd;">
                            <i class="fa fa-lock"></i> Only faculty and admins can post here.
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
            <div class="toast-header">
                <span><i class="fa fa-bell toast-icon"></i> New Message</span>
                <button type="button" class="toast-close" onclick="event.stopPropagation(); this.parentElement.parentElement.remove()">&times;</button>
            </div>
            <div style="font-size: 11px; color: #006699; font-weight: 600;">${title}</div>
            <div class="toast-body">${body}</div>
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
                <button type="button" class="close" data-dismiss="modal" onclick="$('#groupMembersModal').modal('hide')">&times;</button>
                <h4 class="modal-title"><i class="fa fa-users"></i> Group Members: <span id="gm-title"></span></h4>
            </div>
            <div class="modal-body" id="group_members_body" style="max-height: 60vh; overflow-y: auto;">
                <!-- Members will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" onclick="$('#groupMembersModal').modal('hide')">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>