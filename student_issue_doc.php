<?php
// Debug to check session status and variables
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// Database connection
require_once 'includes/configNew.php';
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($con, "utf8mb4");
if (!isset($_SESSION['s_id'])) {
    header('location:student-login.php');
    exit;
}
include("header.php");
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style type="text/css">
    * { box-sizing: border-box !important; }
    :root {
        --primary: #4f46e5;
        --primary-light: #818cf8;
        --primary-dark: #312e81;
        --teal: #14b8a6;
        --bg: #f5f5ff;
        --card: rgba(255, 255, 255, 0.92);
        --text: #0f172a;
        --muted: #64748b;
        --border: rgba(99, 102, 241, 0.15);
        --shadow-md: 0 4px 6px -1px rgb(79 70 229/0.12), 0 2px 4px -2px rgb(79 70 229/0.08);
        --shadow-lg: 0 10px 15px -3px rgb(79 70 229/0.15), 0 4px 6px -4px rgb(79 70 229/0.1);
        --radius-md: 16px;
        --radius-lg: 22px;
    }

    body {
        background: var(--bg);
        background-image:
            radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.08) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(20, 184, 166, 0.05) 0px, transparent 50%);
        font-family: 'Outfit', sans-serif !important;
        color: var(--text);
        -webkit-font-smoothing: antialiased;
    }

    /* Hide default headers if they exist */
    .header1, .header2, .header3, #mu-menu { display: none !important; }
    body { padding-top: 0 !important; }

    #mainContainer {
        max-width: 1100px;
        margin: 10px auto 30px;
        padding: 0 15px;
    }

    /* ── College Branding ── */
    .college-branding {
        text-align: center;
        background: #fff;
        padding: 8px 15px;
        border-radius: var(--radius-md) var(--radius-md) 0 0;
        border: 1px solid #e2e8f0;
        border-bottom: none;
    }
    .college-name {
        font-weight: 900; font-size: 1.85rem; color: #ce1126;
        text-transform: uppercase; display: block; line-height: 1.05; letter-spacing: 0.02em;
    }
    .college-affiliation {
        font-size: 0.75rem; color: #000; font-weight: 700;
        text-transform: uppercase; display: block; margin-top: 4px; letter-spacing: 0.01em;
    }

    /* ── Profile Header ── */
    @keyframes headerShimmer {
        0% { transform: translateX(-100%) skewX(-15deg); }
        100% { transform: translateX(400%) skewX(-15deg); }
    }
    .profile-header {
        background: linear-gradient(135deg, #1e1b6e 0%, #312e81 35%, #4f46e5 70%, #818cf8 100%);
        padding: 16px 22px;
        border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        color: #fff;
        margin-bottom: 16px;
        box-shadow: 0 20px 40px -8px rgba(49, 46, 129, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.08) inset;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
        flex-wrap: wrap;
        gap: 12px;
    }
    .profile-header::after {
        content: ''; position: absolute; top: 0; left: 0; width: 70px; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.18), transparent);
        animation: headerShimmer 3.5s ease-in-out infinite; pointer-events: none;
    }
    .profile-title-wrapper { display: flex; align-items: center; gap: 15px; z-index: 1; }
    .page-icon {
        width: 50px; height: 50px; background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px); border: 2px solid rgba(255, 255, 255, 0.55);
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        font-size: 22px; color: #fff;
    }
    .profile-title { font-weight: 800; font-size: 1.45rem; margin: 0; text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15); }
    .profile-subtitle { font-size: 0.85rem; opacity: 0.9; margin-top: 4px; font-weight: 500; }

    .btn-back {
        background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(10px);
        border: 1.5px solid rgba(255, 255, 255, 0.4); color: #fff;
        padding: 8px 18px; border-radius: 50px; font-weight: 600; font-size: 0.85rem;
        text-decoration: none; transition: all 0.3s ease; display: inline-flex;
        align-items: center; gap: 8px; z-index: 1;
    }
    .btn-back:hover { background: rgba(255, 255, 255, 0.28); transform: translateY(-2px); color: #fff; }

    /* ── Stats Bar ── */
    .stats-bar {
        background: #fff; border-radius: var(--radius-md); padding: 15px 25px;
        display: flex; align-items: center; justify-content: space-around;
        border: 1px solid var(--border); box-shadow: var(--shadow-md);
        margin-bottom: 25px; animation: fadeInUp 0.5s ease-out both;
    }
    .stat-item { display: flex; align-items: center; gap: 15px; }
    .stat-icon {
        width: 42px; height: 42px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
    }
    .stat-icon.indigo { background: rgba(79, 70, 229, 0.1); color: #4f46e5; }
    .stat-icon.teal { background: rgba(20, 184, 166, 0.1); color: #14b8a6; }
    .stat-label { font-size: 0.7rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .stat-value { font-size: 1.1rem; font-weight: 800; color: var(--text); }
    .stat-divider { width: 1px; height: 30px; background: var(--border); }

    /* ── Document Cards ── */
    .doc-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
    .doc-card {
        background: var(--card); backdrop-filter: blur(16px);
        border-radius: var(--radius-lg); border: 1px solid var(--border);
        box-shadow: var(--shadow-md); overflow: hidden; transition: all 0.3s ease;
        animation: fadeInUp 0.5s ease-out both;
    }
    .doc-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: rgba(99, 102, 241, 0.3); }
    
    .doc-header {
        padding: 14px 20px; border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
        background: rgba(79, 70, 229, 0.03);
    }
    .doc-type { font-weight: 800; font-size: 1.1rem; color: var(--primary-dark); display: flex; align-items: center; gap: 10px; }
    .doc-date { font-size: 0.75rem; color: var(--muted); font-weight: 600; }

    .doc-body { padding: 15px 20px; display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; align-items: center; }
    .info-item { display: flex; flex-direction: column; gap: 2px; }
    .info-label { font-size: 0.65rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
    .info-value { font-size: 0.88rem; font-weight: 600; color: var(--text); }

    .doc-footer {
        padding: 12px 20px; background: rgba(0,0,0,0.02); border-top: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
    }

    /* ── Status Badges ── */
    .status-badge {
        display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px;
        border-radius: 50px; font-size: 0.78rem; font-weight: 700;
    }
    .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .status-badge.pending { background: rgba(245, 158, 11, 0.1); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.25); }
    .status-badge.accepted { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.25); }
    .status-badge.rejected { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.25); }

    /* ── Additional Details ── */
    .details-box {
        margin: 15px 20px; padding: 15px; background: rgba(255,255,255,0.5);
        border: 1px dashed var(--border); border-radius: 12px;
        display: grid; grid-template-columns: 1fr 200px; gap: 20px;
    }
    .details-info { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .photo-wrapper { text-align: center; }
    .photo-img {
        max-width: 100%; max-height: 120px; border-radius: 8px;
        border: 2px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        cursor: zoom-in; transition: transform 0.2s;
    }
    .photo-img:hover { transform: scale(1.05); }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ── No Data State ── */
    .empty-state {
        text-align: center; padding: 60px 20px; background: var(--card);
        border-radius: var(--radius-lg); border: 2px dashed var(--border);
    }
    .empty-state i { font-size: 3.5rem; color: var(--muted); opacity: 0.3; margin-bottom: 15px; }
    .empty-state h3 { font-weight: 700; color: var(--text); margin-bottom: 5px; }
    .empty-state p { color: var(--muted); }

    /* ── Responsive ── */
    @media (max-width: 992px) {
        .doc-body { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .stats-bar { grid-template-columns: repeat(2, 1fr); }
        .details-box { grid-template-columns: 1fr; }
        .photo-wrapper { order: -1; }
        .profile-title { font-size: 1.2rem; }
    }
    @media (max-width: 480px) {
        .doc-body { grid-template-columns: 1fr; }
        .profile-header { padding: 12px 15px; }
        .stat-card { flex-direction: column; text-align: center; gap: 5px; }
        .stat-icon { width: 32px; height: 32px; font-size: 0.9rem; }
    }
</style>

<div id="mainContainer">
    <!-- College Branding -->
    <div class="college-branding">
        <span class="college-name">GIIT Professional College</span>
        <span class="college-affiliation">Affiliated to Kolhan University &nbsp;|&nbsp; Approved by HRD, Govt. of Jharkhand</span>
    </div>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-title-wrapper">
            <div class="page-icon">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div>
                <h1 class="profile-title">My Issued Documents</h1>
                <div class="profile-subtitle">
                    <i class="fas fa-graduation-cap"></i>
                    <?php echo htmlspecialchars($_SESSION['course']['course_name'] ?? 'Course Not Set'); ?>
                    &nbsp;&bull;&nbsp;
                    <?php echo htmlspecialchars($_SESSION['course']['university_short_name'] ?? ''); ?>
                </div>
            </div>
        </div>
        <a href="student-dashboard_sunam.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php
    if (empty($_SESSION['s_course_id'])) {
        echo '<div class="empty-state"><i class="fas fa-user-lock"></i><h3>Session Expired</h3><p>Please log in again to view your documents.</p></div>';
        include("footer.php");
        exit;
    }

    // Fetch issued documents
    $issued_docs = [];
    $doc_query = "
        SELECT di.*, dt.type_name AS document_type, u.university_name, sm.session_name, cm.course_name, rm.relationship_name
        FROM student_doc_issue di
        JOIN student_document_type_master dt ON di.document_type_id = dt.type_id
        JOIN university u ON di.university_id = u.id
        JOIN session_master sm ON di.session_id = sm.session_master_id
        JOIN course_master cm ON di.course_id = cm.course_master_id
        LEFT JOIN relationship_master rm ON di.relationship_id = rm.id
        WHERE di.student_id = ? AND di.send_notification = 1 AND di.is_active = 1
        ORDER BY di.created_at DESC
    ";
    
    $stmt = $con->prepare($doc_query);
    $stmt->bind_param("i", $_SESSION['s_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $total_docs = 0;
    $pending_count = 0;
    $accepted_count = 0;
    $rejected_count = 0;

    while ($row = $result->fetch_assoc()) {
        $total_docs++;
        if ($row['student_response'] === null) {
            $pending_count++;
            continue; // Skip pending
        } elseif ($row['student_response'] == 1) {
            $row['display_status'] = 'Accepted';
            $row['status_class'] = 'accepted';
            $accepted_count++;
        } else {
            $rejected_count++;
            continue; // Skip rejected
        }
        $row['notified_time'] = date('d M Y, h:i A', strtotime($row['created_at']));
        $row['response_time'] = $row['response_timestamp'] ? date('d M Y, h:i A', strtotime($row['response_timestamp'])) : 'N/A';
        $issued_docs[] = $row;
    }
    $stmt->close();
    ?>

    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-icon indigo"><i class="fas fa-layer-group"></i></div>
            <div>
                <div class="stat-label">Total Issued</div>
                <div class="stat-value"><?php echo $total_docs; ?></div>
            </div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="stat-icon teal"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="stat-label">Accepted</div>
                <div class="stat-value"><?php echo $accepted_count; ?></div>
            </div>
        </div>
    </div>

    <!-- Documents List -->
    <div class="doc-grid">
        <?php if (empty($issued_docs)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h3>No Documents Found</h3>
                <p>You haven't been issued any documents yet. They will appear here once available.</p>
            </div>
        <?php else: ?>
            <?php foreach ($issued_docs as $index => $doc): ?>
                <div class="doc-card" style="animation-delay: <?php echo ($index * 0.1); ?>s">
                    <div class="doc-header">
                        <div class="doc-type">
                            <i class="fas fa-file-alt"></i>
                            <?php echo htmlspecialchars($doc['document_type']); ?>
                        </div>
                        <div class="doc-date">
                            Notified: <?php echo $doc['notified_time']; ?>
                        </div>
                    </div>
                    
                    <div class="doc-body">
                        <div class="info-item">
                            <span class="info-label">University</span>
                            <span class="info-value"><?php echo htmlspecialchars($doc['university_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Session</span>
                            <span class="info-value"><?php echo htmlspecialchars($doc['session_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Course</span>
                            <span class="info-value"><?php echo htmlspecialchars($doc['course_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Semester</span>
                            <span class="info-value">Semester <?php echo htmlspecialchars($doc['semester_id']); ?></span>
                        </div>
                        <div class="info-item" style="align-items: flex-start;">
                            <span class="info-label">Status & Response</span>
                            <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                <span class="status-badge <?php echo $doc['status_class']; ?>">
                                    <?php echo $doc['display_status']; ?>
                                </span>
                                <div style="font-size: 0.72rem; color: var(--muted); font-weight: 600; white-space: nowrap;">
                                    <i class="far fa-clock" style="font-size: 0.65rem; margin-right: 3px;"></i>
                                    <?php echo $doc['response_time']; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($doc['student_response'] == 1 && ($doc['relationship_id'] || $doc['photo_file'])): ?>
                        <div class="details-box">
                            <div class="details-info">
                                <?php if ($doc['relationship_name']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Relationship</span>
                                        <span class="info-value"><?php echo htmlspecialchars($doc['relationship_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($doc['relationship_phone']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Phone</span>
                                        <span class="info-value"><?php echo htmlspecialchars($doc['relationship_phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($doc['relationship_aadhar']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Aadhar No.</span>
                                        <span class="info-value"><?php echo htmlspecialchars($doc['relationship_aadhar']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($doc['photo_file']): ?>
                                <div class="photo-wrapper">
                                    <span class="info-label" style="display:block;margin-bottom:5px;">Document Photo</span>
                                    <a href="<?php echo htmlspecialchars($doc['photo_file']); ?>" target="_blank">
                                        <img src="<?php echo htmlspecialchars($doc['photo_file']); ?>" alt="Photo" class="photo-img">
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include("footer.php"); ?>