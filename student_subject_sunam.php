<?php
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

// AJAX handler for fetching chapters
if (isset($_GET['ajax']) && $_GET['ajax'] == 'chapters') {
    header('Content-Type: application/json');
    $subject_id = intval($_GET['subject_id']);
    $query = "SELECT chapter_id, chapter_no, chapter_name, chapter_status FROM chapter_master WHERE chapter_subject_id = ? AND chapter_status = 'active' ORDER BY chapter_no ASC";
    $stmt = $con->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Prepare failed']);
        exit;
    }
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $chapters = [];
    while ($chapter = $result->fetch_assoc()) {
        $chapters[] = $chapter;
    }
    echo json_encode($chapters);
    $stmt->close();
    exit;
}
include("header.php");
?>
<meta name="author" content="Sunam Ghosh --@SunamGhosh">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
        margin: 20px auto;
        padding-bottom: 40px;
        max-width: 1000px;
        /* Centered layout like 2nd image */
    }

    @keyframes headerShimmer {
        0% {
            transform: translateX(-100%) skewX(-15deg);
        }

        100% {
            transform: translateX(400%) skewX(-15deg);
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
        animation: fadeIn 0.8s ease-out;
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
        padding: 15px 20px !important;
        border-radius: 0 0 var(--radius-md) var(--radius-md);
        color: white !important;
        margin-top: -1px;
        margin-bottom: 22px !important;
        box-shadow: 0 10px 25px -5px rgba(49, 46, 129, 0.3);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: none !important;
        position: relative;
        overflow: hidden;
    }

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
        font-size: 1.15rem;
        letter-spacing: -0.025em;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        line-height: 1.15;
        z-index: 2;
    }

    .welcome-text span {
        display: block;
        font-size: 0.82rem;
        font-weight: 500;
        opacity: 0.85;
        margin-top: 4px;
        letter-spacing: 0.01em;
    }

    /* ── Sem/Year selector row ─────────────────────── */
    .sem-selector {
        display:flex; flex-wrap:wrap; gap:8px;
        justify-content:center; margin-bottom:18px;
    }
    .sem-btn {
        background: var(--card-bg);
        color: var(--muted);
        border: 1.5px solid var(--border);
        border-radius: 10px;
        padding: 9px 20px;
        font-family:'Outfit',sans-serif;
        font-size:.84rem; font-weight:600;
        cursor:pointer; transition:all .25s ease;
        outline:none;
    }
    .sem-btn:hover { border-color:var(--primary-lt); color:var(--primary); background:#fff; }
    .sem-btn.active {
        background:var(--primary); color:#fff !important;
        border-color:var(--primary);
        box-shadow:0 6px 18px rgba(79,70,229,.3);
    }

    /* ── Tab panels ────────────────────────────────── */
    .tab-panel { display:none; }
    .tab-panel.active { display:block; animation: fadeUp .35s ease-out; }

    /* ── Table frame ───────────────────────────────── */
    .qb-table-frame {
        background: #a855f7;
        padding: 8px;
        border-radius: 20px;
        box-shadow: 0 20px 40px -12px rgba(168,85,247,.22);
        animation: fadeUp .4s ease-out both;
        margin-bottom: 20px;
    }
    .qb-table-wrap {
        background: #fff;
        border-radius: 12px;
        overflow-y: auto;
        max-height: 480px;
        border: 1px solid rgba(0,0,0,.05);
    }
    .qb-table {
        width: 100%;
        border-collapse: collapse;
    }
    /* sticky thead */
    .qb-table thead th {
        position: sticky; top: 0; z-index: 10;
        background: #14b8a6; /* VIBRANT TEAL */
        color: #fff !important;
        font-weight: 700;
        font-size: .82rem;
        padding: 12px 14px;
        border: 1px solid rgba(255,255,255,.1);
        text-transform: capitalize;
        white-space: nowrap;
        letter-spacing: .02em;
    }

    /* data rows */
    .qb-table tbody tr.qb-row {
        transition: background .18s;
    }
    .qb-table tbody tr.qb-row:nth-child(even) { background: rgba(245,245,255,.6); }
    .qb-table tbody tr.qb-row:hover { background: rgba(79,70,229,.06); }
    .qb-table tbody td {
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        font-size: .82rem;
        color: var(--text);
        vertical-align: middle;
        word-break: break-word;
    }
    /* column widths */
    .qb-table .col-no   { width: 8%;  text-align: center; font-weight: 600; color: var(--muted); }
    .qb-table .col-name { width: 78%; font-weight: 600; }
    .qb-table .col-act  { width: 14%; text-align: right; }

    /* action btn */
    .action-btn {
        display: inline-flex; align-items: center; gap: 5px;
        background: var(--primary); color: #fff !important;
        padding: 6px 14px; border-radius: 50px;
        font-size: .73rem; font-weight: 700;
        text-decoration: none !important; white-space: nowrap;
        box-shadow: 0 4px 12px rgba(79,70,229,.28);
        transition: all .22s ease;
        border: none;
        cursor: pointer;
    }
    .action-btn:hover { background: var(--primary-dk); transform: scale(1.04); box-shadow: 0 6px 18px rgba(79,70,229,.38); }
    .action-btn i { font-size: .68rem; }

    /* ── Empty state ───────────────────────────────── */
    .empty-state {
        text-align: center; padding: 50px 20px;
        background: var(--card-bg); border-radius: var(--radius-md);
        border: 1.5px dashed var(--border);
        animation: fadeUp .4s ease-out;
    }
    .empty-state i {
        font-size: 2.8rem; display: block; margin-bottom: 14px;
        background: linear-gradient(135deg,#312e81,#818cf8);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .empty-state p { color: var(--muted); font-size: .9rem; margin: 0; font-weight: 500; }

    @media (max-width: 480px) {
        #subjectPage { margin: 5px auto; padding: 0 0 60px; }
        .college-branding { padding: 10px !important; border-radius: 16px 16px 0 0 !important; }
        .welcome-header { border-radius: 0 0 24px 24px !important; margin-bottom: 14px !important; }
        .sem-btn { padding: 8px 14px; font-size: .78rem; }
        .qb-table thead th, .qb-table tbody td { padding: 8px 8px; font-size: .72rem; }
        .action-btn { padding: 5px 10px; font-size: .68rem; }
        #subjectPage > .sem-selector { padding: 0 10px; }
        .tab-panel { padding: 0 10px; }
    }

    .nav-pills>li>a {
        background: var(--card-bg);
        color: var(--text-muted) !important;
        border-radius: 8px !important;
        padding: 10px 20px !important;
        font-weight: 600;
        font-size: 0.85rem;
        border: 1px solid var(--glass-border);
        transition: all 0.3s ease;
    }

    .nav-pills>li.active>a,
    .nav-pills>li.active>a:hover {
        background: var(--primary) !important;
        color: white !important;
        box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);
        border-color: var(--primary);
    }

    /* Subject Table Frame (The magenta outer border) */
    .subject-portal-frame {
        background: #a855f7;
        /* Vibrant Purple/Magenta frame from image */
        padding: 8px;
        border-radius: var(--radius-md);
        box-shadow: 0 20px 40px -12px rgba(168, 85, 247, 0.25);
        margin-top: 5px;
    }

    .subject-table-container {
        background: #ffffff;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .subject-table {
        width: 100%;
        border-collapse: collapse;
    }

    .subject-table th,
    .subject-table td {
        padding: 10px 18px;
        border: 1px solid #e2e8f0;
        /* Balanced visible grid */
        vertical-align: middle;
    }

    .subject-table th {
        background: #006699;
        /* Precise teal from image */
        color: white;
        text-align: left;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
        border: none !important;
    }

    .subject-table tr:last-child td {
        border-bottom: none;
    }

    .subject-table tr:hover {
        background: rgba(79, 70, 229, 0.02);
    }

    .subject-info-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .subject-code {
        font-weight: 800;
        color: #6366f1;
        /* Indigo color from image */
        font-size: 0.75rem;
        min-width: 65px;
        display: inline-block;
    }

    .subject-name {
        font-weight: 600;
        font-size: 0.85rem;
        color: #1e293b;
        line-height: 1.3;
    }

    .category-cell {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .btn-view-chapters {
        background: var(--primary);
        color: white !important;
        padding: 4px 12px;
        border-radius: 50px;
        text-decoration: none !important;
        font-weight: 600;
        font-size: 0.7rem;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        white-space: nowrap;
        border: none;
    }

    .btn-view-chapters:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(79, 70, 229, 0.2);
    }

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex !important;
        justify-content: center;
        align-items: center;
        z-index: 999999;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
        padding: 20px;
    }

    .modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background: white;
        width: 100%;
        max-width: 650px;
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        overflow: hidden;
        transform: scale(0.95);
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        display: flex;
        flex-direction: column;
        max-height: 85vh;
        margin: 0 !important;
    }

    .modal-overlay.show .modal-content {
        transform: scale(1);
    }

    .modal-header {
        background: linear-gradient(135deg, #1e1b6e 0%, #4f46e5 100%);
        padding: 18px 24px;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-weight: 700;
        font-size: 1.1rem;
    }

    .btn-close-modal {
        background: rgba(255, 255, 255, 0.1);
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-close-modal:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .modal-body {
        padding: 24px;
        max-height: 70vh;
        overflow-y: auto;
    }

    .chapter-container {
        animation: fadeIn 0.4s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .chapter-table {
        width: 100%;
        border-collapse: collapse;
    }

    .chapter-table th {
        background: rgba(49, 46, 129, 0.05);
        color: var(--primary-dark);
        text-align: left;
        padding: 10px 12px;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .chapter-table td {
        padding: 10px 12px;
        border-top: 1px solid var(--glass-border);
        font-size: 0.9rem;
    }

    .elective-badge {
        font-size: 0.7rem;
        background: #f59e0b;
        color: white;
        padding: 2px 8px;
        border-radius: 20px;
        margin-left: 10px;
        font-weight: 700;
        vertical-align: middle;
    }

    .no-data {
        text-align: center;
        padding: 40px;
        background: var(--card-bg);
        border-radius: var(--radius-md);
        color: var(--text-muted);
    }

    @media (max-width: 768px) {
        #mainContainer {
            margin: 5px auto;
            padding: 0 8px 40px;
        }

        .college-branding {
            margin-bottom: 0px !important;
            padding: 10px !important;
            border-radius: 15px 15px 0 0 !important;
        }

        .college-name {
            font-size: 1.15rem !important;
            letter-spacing: -0.01em !important;
        }

        .college-affiliation {
            font-size: 0.65rem !important;
            white-space: normal !important;
        }

        .nav-pills>li>a {
            padding: 8px 14px !important;
            font-size: 0.72rem !important;
            border-radius: 12px !important;
        }

        .nav-pills {
            gap: 6px !important;
            margin-bottom: 15px !important;
        }

        .welcome-header {
            padding: 10px 15px !important;
            margin-bottom: 12px !important;
        }

        .welcome-text {
            font-size: 0.95rem;
        }

        .course-info {
            display: none;
        }

        .subject-portal-frame {
            padding: 4px;
            border-radius: 12px;
        }

        .subject-table-container {
            border-radius: 8px;
            border: none;
        }

        .subject-table th,
        .subject-table td {
            padding: 8px 10px;
            font-size: 0.72rem;
            border: 1px solid #e2e8f0 !important;
        }

        .subject-info-cell {
            gap: 8px;
        }

        .subject-name {
            font-size: 0.75rem;
        }

        .subject-code {
            min-width: 45px;
            font-size: 0.6rem;
        }

        .btn-view-chapters {
            padding: 4px 10px;
            font-size: 0.65rem;
        }
    }
</style>

<div class="container" id="mainContainer">
    <div class="college-branding">
        <span class="college-name">GIIT PROFESSIONAL COLLEGE</span>
        <span class="college-affiliation">Affiliated to KOLHAN UNIVERSITY | Approved by HRD, Govt. of Jharkhand</span>
    </div>
    <?php
    if (empty($_SESSION['s_course_id']) || empty($_SESSION['course'])) {
        echo '<div class="no-data">Session data missing. Please log in again.</div>';
        exit;
    }
    $course_id = $_SESSION['s_course_id'] ?? 0;
    $current_sem = $_SESSION['s_sem'] ?? 1;
    $student_id = $_SESSION['s_id'];

    // Fetch elective info
    $elective_group_id = 0;
    $elective_group_name = '';
    $elective_query = "SELECT sem.elective_group_id, eg.group_name 
                      FROM student_elective_master sem 
                      JOIN elective_groups eg ON eg.id = sem.elective_group_id
                      WHERE sem.student_id = ? AND sem.course_id = ?";
    $e_stmt = $con->prepare($elective_query);
    $e_stmt->bind_param("ii", $student_id, $course_id);
    $e_stmt->execute();
    $e_res = $e_stmt->get_result();
    if ($e_row = $e_res->fetch_assoc()) {
        $elective_group_id = $e_row['elective_group_id'];
        $elective_group_name = $e_row['group_name'];
    }
    $e_stmt->close();

    // Fetch approved elective choices
    $approved_choices = [];
    if ($elective_group_id > 0) {
        $sub_stmt = $con->prepare("SELECT subject_choices FROM student_elective_submissions WHERE student_id = ? AND group_id = ? AND status = 'approved'");
        $sub_stmt->bind_param("ii", $student_id, $elective_group_id);
        $sub_stmt->execute();
        $sub_res = $sub_stmt->get_result();
        if ($sub_row = $sub_res->fetch_assoc()) {
            $approved_choices = json_decode($sub_row['subject_choices'], true);
        }
        $sub_stmt->close();
    }
    ?>

    <div class="welcome-header">
        <div class="welcome-text">
            Subjects &amp; Curriculum
            <span>Track your course progress and chapters</span>
        </div>
        <div class="course-info">
            <span class="ci-icon"><i class="fa fa-book"></i></span>
            <span>
                <?php echo htmlspecialchars($_SESSION['course']['course_name'] ?? ''); ?>
                <span style="opacity: 0.7; font-size: 0.8em; margin-left: 4px;">(<?php echo htmlspecialchars($_SESSION['course']['university_short_name'] ?? ''); ?>)</span>
            </span>
        </div>
    </div>

    <?php
    $qMode = "SELECT course_mode FROM course_master WHERE course_master_id='$course_id'";
    $rMode = mysqli_query($con, $qMode);
    $rowM = mysqli_fetch_array($rMode);
    $cMode = $rowM['course_mode'] ?? 'N';
    $max = ($cMode == 's' || $cMode == 'S') ? 6 : 3;
    $typeLabel = ($cMode == 's' || $cMode == 'S') ? "Semester" : "Year";
    ?>

    <!-- ── Semester / Year selector ── -->
    <div class="sem-selector" id="semSelector">
        <?php for ($i = 1; $i <= $max; $i++): ?>
            <button 
                class="sem-btn <?= $i == $current_sem ? 'active' : '' ?>" 
                data-tab="tab<?= $i ?>"
                id="semBtn<?= $i ?>">
                <?= $typeLabel ?> <?= $i ?>
            </button>
        <?php endfor; ?>
    </div>

    <!-- ── Tab Panels ── -->
    <?php for ($sem = 1; $sem <= $max; $sem++): ?>
    <div class="tab-panel <?= $sem == $current_sem ? 'active' : '' ?>" id="tab<?= $sem ?>">
        <div class="qb-table-frame">
            <div class="qb-table-wrap">
                <table class="qb-table">
                    <thead>
                        <tr>
                            <th class="col-no">#</th>
                            <th class="col-name">Subject Name</th>
                            <th class="col-act">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch Regular Subjects
                        $query = "SELECT * FROM `subject_master` WHERE `subject_course_id` = ? AND `subject_sem_id` = ? AND `subject_status` = 'active' ORDER BY subject_university_code ASC";
                        $stmt = $con->prepare($query);
                        $stmt->bind_param("ii", $course_id, $sem);
                        $stmt->execute();
                        $results = $stmt->get_result();

                        // Fetch Elective Subjects
                        $elective_subjects = [];
                        if ($elective_group_id > 0) {
                            $es_query = "SELECT * FROM elective_subjects WHERE group_id = ? AND semester = ? AND status = 1 ORDER BY elective_no ASC";
                            $es_stmt = $con->prepare($es_query);
                            $es_stmt->bind_param("is", $elective_group_id, $sem);
                            $es_stmt->execute();
                            $es_res = $es_stmt->get_result();
                            while ($es_row = $es_res->fetch_assoc()) {
                                $elective_subjects[] = $es_row;
                            }
                            $es_stmt->close();
                        }

                        $serial = 0;
                        if ($results->num_rows > 0 || !empty($elective_subjects)):
                            // 1. Electives
                            foreach ($elective_subjects as $es):
                                $eNo = $es['elective_no'];
                                $options_count = 0;
                                foreach ($elective_subjects as $tmp) if ($tmp['elective_no'] == $eNo) $options_count++;
                                if ($options_count > 1) {
                                    if (!isset($approved_choices[$eNo]) || $approved_choices[$eNo] != $es['id']) continue;
                                }
                                $serial++;
                        ?>
                            <tr class="qb-row">
                                <td class="col-no"><?= $serial ?></td>
                                <td class="col-name">
                                    <?= htmlspecialchars($es['subject_name']) ?>
                                    <span class="elective-badge"><?= htmlspecialchars($elective_group_name) ?></span>
                                </td>
                                <td class="col-act">
                                    <button onclick="showChapters(<?= $es['id'] ?>, '<?= htmlspecialchars($es['subject_name']) ?>')" class="action-btn">
                                        <i class="fa fa-list"></i> Chapters
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php 
                            // 2. Regular Subjects
                            while ($subject = $results->fetch_assoc()): 
                                $serial++;
                        ?>
                            <tr class="qb-row">
                                <td class="col-no"><?= $serial ?></td>
                                <td class="col-name"><?= htmlspecialchars($subject['subject_name']) ?></td>
                                <td class="col-act">
                                    <button onclick="showChapters(<?= $subject['subject_master_id'] ?>, '<?= htmlspecialchars($subject['subject_name']) ?>')" class="action-btn">
                                        <i class="fa fa-list"></i> Chapters
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 40px; color: #64748b;">
                                    No subjects found for this <?= strtolower($typeLabel) ?>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endfor; ?>
</div>

<!-- Chapter Modal -->
<div id="chapterModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title" id="modalSubjectName">Chapters</div>
            <button class="btn-close-modal" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Loaded via AJAX -->
        </div>
    </div>
</div>

<script type="text/javascript">
    document.querySelectorAll('.sem-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.sem-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(btn.getAttribute('data-tab')).classList.add('active');
        });
    });

    function showChapters(subjectId, subjectName) {
        const modal = document.getElementById('chapterModal');
        const modalBody = document.getElementById('modalBody');
        const modalTitle = document.getElementById('modalSubjectName');

        // Move to body BEFORE showing to avoid parent transform/z-index issues
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        modalTitle.innerText = subjectName;
        modalBody.innerHTML = '<div style="padding: 40px; text-align: center;"><i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary);"></i><p style="margin-top:15px; color: var(--text-muted);">Loading curriculum...</p></div>';

        modal.classList.add('show');
        document.body.style.overflow = 'hidden'; 
        
        fetch(`?ajax=chapters&subject_id=${subjectId}`)
            .then(res => res.json())
            .then(chapters => {
                if (!chapters || chapters.length === 0) {
                    modalBody.innerHTML = '<div style="padding: 30px; text-align: center; color: var(--text-muted); font-style: italic;"><i class="fas fa-info-circle fa-2x" style="margin-bottom:10px; opacity:0.5;"></i><p>No chapters available for this subject yet.</p></div>';
                } else {
                    let html = `
                        <div class="qb-table-frame" style="padding:4px; box-shadow:none;">
                            <div class="qb-table-wrap" style="max-height: 400px;">
                                <table class="qb-table">
                                    <thead>
                                        <tr>
                                            <th class="col-no">#</th>
                                            <th class="col-name">Chapter Name</th>
                                            <th class="col-act">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                    
                    chapters.forEach(c => {
                        html += `
                            <tr class="qb-row">
                                <td class="col-no">${c.chapter_no}</td>
                                <td class="col-name">${c.chapter_name}</td>
                                <td class="col-act">
                                    <span style="font-size: 0.75rem; color: #10b981; font-weight: 700;">
                                        <i class="fas fa-check-circle"></i> ${c.chapter_status}
                                    </span>
                                </td>
                            </tr>`;
                    });
                    
                    html += '</tbody></table></div></div>';
                    modalBody.innerHTML = html;
                }
            })
            .catch(err => {
                modalBody.innerHTML = '<div style="padding: 20px; color: #ef4444; text-align: center;">Error loading curriculum data.</div>';
            });
    }

    function closeModal() {
        document.getElementById('chapterModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('chapterModal');
        if (event.target == modal) closeModal();
    }
</script>