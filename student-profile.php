<?php
session_start();
if (!isset($_SESSION['s_id'])) {
    header('location:student-login.php');
    exit;
}
?>
<?php include("header.php"); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
    rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style type="text/css">
    * {
        box-sizing: border-box !important;
    }

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
        font-weight: 900;
        font-size: 1.85rem;
        color: #ce1126;
        text-transform: uppercase;
        display: block;
        line-height: 1.05;
        letter-spacing: 0.02em;
    }

    .college-affiliation {
        font-size: 0.75rem;
        color: #000;
        font-weight: 700;
        text-transform: uppercase;
        display: block;
        margin-top: 4px;
        letter-spacing: 0.01em;
    }

    /* ── Welcome / Profile Header ── */
    @keyframes headerShimmer {
        0% {
            transform: translateX(-100%) skewX(-15deg);
        }

        100% {
            transform: translateX(400%) skewX(-15deg);
        }
    }

    @keyframes avatarGlow {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.3), 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        50% {
            box-shadow: 0 0 0 8px rgba(255, 255, 255, 0.08), 0 8px 25px rgba(0, 0, 0, 0.25);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(18px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes badgePulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.3), 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(255, 255, 255, 0.08), 0 4px 20px rgba(0, 0, 0, 0.2);
        }
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

    .profile-header::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 230px;
        height: 230px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, transparent 65%);
        border-radius: 50%;
        pointer-events: none;
    }

    .profile-header::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 70px;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.18), transparent);
        animation: headerShimmer 3.5s ease-in-out infinite;
        pointer-events: none;
    }

    .profile-avatar-wrapper {
        display: flex;
        align-items: center;
        gap: 18px;
        z-index: 1;
    }

    .profile-avatar {
        width: 64px;
        height: 64px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.55);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: #fff;
        animation: avatarGlow 3s ease-in-out infinite;
        flex-shrink: 0;
    }

    .profile-title {
        font-weight: 800;
        font-size: 1.45rem;
        letter-spacing: -0.025em;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        line-height: 1.2;
        margin: 0;
    }

    .profile-subtitle {
        font-size: 0.9rem;
        font-weight: 500;
        opacity: 0.9;
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 7px;
        letter-spacing: 0.01em;
    }

    .course-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(12px);
        padding: 8px 18px 8px 14px;
        border-radius: 50px;
        color: #fff;
        border: 1.5px solid rgba(255, 255, 255, 0.4);
        box-shadow: 0 4px 18px rgba(49, 46, 129, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        animation: badgePulse 3s ease-in-out infinite;
        white-space: nowrap;
        z-index: 1;
        flex-shrink: 0;
        text-transform: uppercase;
    }

    .course-badge i {
        font-size: 0.9rem;
    }

    .btn-back {
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(10px);
        border: 1.5px solid rgba(255, 255, 255, 0.4);
        color: #fff;
        padding: 9px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.88rem;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        z-index: 1;
    }

    .btn-back:hover {
        background: rgba(255, 255, 255, 0.28);
        transform: translateY(-2px);
        color: #fff;
    }

    /* ── Stats Bar ── */
    .stats-bar {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin-bottom: 12px;
        animation: fadeInUp 0.5s ease-out both;
        animation-delay: 0.1s;
    }

    .stat-card {
        background: #fff;
        border-radius: var(--radius-md);
        padding: 11px 14px;
        display: flex;
        align-items: center;
        gap: 11px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-md);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-lg);
    }

    .stat-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .stat-icon.indigo {
        background: rgba(79, 70, 229, 0.1);
        color: #4f46e5;
    }

    .stat-icon.teal {
        background: rgba(20, 184, 166, 0.1);
        color: #14b8a6;
    }

    .stat-icon.amber {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
    }

    .stat-icon.rose {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }

    .stat-label {
        font-size: 0.68rem;
        font-weight: 600;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .stat-value {
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--text);
        margin-top: 1px;
    }

    /* ── Info Cards Grid ── */
    .profile-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }

    .info-card {
        background: var(--card);
        backdrop-filter: blur(16px);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        animation: fadeInUp 0.5s ease-out both;
    }

    .info-card:nth-child(1) {
        animation-delay: 0.15s;
    }

    .info-card:nth-child(2) {
        animation-delay: 0.22s;
    }

    .info-card:nth-child(3) {
        animation-delay: 0.29s;
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
        border-color: rgba(99, 102, 241, 0.3);
    }

    .info-card-header {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 10px;
        position: relative;
        overflow: hidden;
    }

    .info-card-header.academic {
        background: rgba(79, 70, 229, 0.04);
    }

    .info-card-header.personal {
        background: rgba(20, 184, 166, 0.04);
    }

    .info-card-header.contact {
        background: rgba(245, 158, 11, 0.04);
    }

    .combined-card {
        padding: 0;
        margin-bottom: 20px;
    }

    .profile-details-wrapper {
        display: flex;
        flex-direction: column;
    }

    .details-section {
        border-right: none;
        border-bottom: 1px solid var(--border);
    }

    .details-section:last-child {
        border-bottom: none;
    }

    .card-header-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }

    .card-header-icon.indigo {
        background: rgba(79, 70, 229, 0.12);
        color: #4f46e5;
    }

    .card-header-icon.teal {
        background: rgba(20, 184, 166, 0.12);
        color: #14b8a6;
    }

    .card-header-icon.amber {
        background: rgba(245, 158, 11, 0.12);
        color: #d97706;
    }

    .info-card-title {
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--primary-dark);
        margin: 0;
    }

    .info-card-subtitle {
        font-size: 0.7rem;
        color: var(--muted);
        margin-top: 1px;
    }

    .info-list { 
        padding: 12px 20px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px 25px;
    }

    .info-item {
        padding: 0;
        border-bottom: none;
        display: flex; 
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
    }

    .info-label {
        font-size:0.68rem; font-weight:600; color:var(--muted);
        text-transform:uppercase; letter-spacing:0.04em;
    }

    .info-value {
        font-size:0.88rem; font-weight:600; color:var(--text);
        text-align:left; word-break:break-word;
    }

    .info-value.highlight {
        color: var(--primary);
        font-weight: 700;
    }

    /* ── Status Badges ── */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .status-badge.paid {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.25);
    }

    .status-badge.pending {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
        border: 1px solid rgba(245, 158, 11, 0.25);
    }

    .status-badge.unpaid {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.25);
    }

    .status-badge::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    /* ── Error State ── */
    .error-card {
        background: #fff;
        border-radius: var(--radius-lg);
        border: 2px dashed #e2e8f0;
        padding: 60px 30px;
        text-align: center;
    }

    .error-card i {
        font-size: 3rem;
        color: #ef4444;
        margin-bottom: 15px;
        display: block;
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .profile-header {
            padding: 16px 18px;
            border-radius: 0 0 20px 20px;
        }

        .profile-title {
            font-size: 1.2rem;
        }

        .stats-bar {
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .stat-card {
            padding: 8px 10px;
            gap: 8px;
        }

        .stat-value {
            font-size: 0.8rem;
        }

        .stat-icon {
            width: 30px;
            height: 30px;
            font-size: 0.85rem;
        }
    }

    @media (max-width: 480px) {
        #mainContainer  { padding:0 6px 30px; }

        .college-name {
            font-size: 1.15rem;
        }

        .college-affiliation {
            font-size: 0.6rem;
        }

        .profile-header {
            padding: 12px 14px;
            gap: 8px;
        }

        .profile-avatar {
            width: 48px;
            height: 48px;
            font-size: 22px;
        }

        .profile-title {
            font-size: 1rem;
        }

        .profile-subtitle {
            font-size: 0.75rem;
        }

        .btn-back {
            padding: 6px 14px;
            font-size: 0.75rem;
            width: auto;
        }

        .stats-bar {
            grid-template-columns: repeat(4, 1fr);
            gap: 5px;
        }

        .stat-card {
            padding: 6px 8px;
            flex-direction: column;
            text-align: center;
            gap: 4px;
        }

        .stat-icon {
            width: 28px;
            height: 28px;
            font-size: 0.8rem;
        }

        .stat-label {
            font-size: 0.55rem;
        }

        .stat-value {
            font-size: 0.7rem;
        }

        .profile-grid {
            grid-template-columns: 1fr;
        }

        .profile-details-wrapper {
            grid-template-columns: 1fr;
        }

        .details-section {
            border-right: none;
            border-bottom: 1px solid var(--border);
        }

        .details-section:last-child {
            border-bottom: none;
        }

        .info-card-header {
            padding: 8px 10px;
            gap: 6px;
        }

        .card-header-icon {
            width: 26px;
            height: 26px;
            font-size: 0.8rem;
        }

        .info-card-title {
            font-size: 0.75rem;
        }

        .info-card-subtitle {
            display: none;
        }

        .info-list { grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 10px 12px; }
        .info-label { font-size: 0.6rem; }
        .info-value { font-size: 0.75rem; }

        .status-badge {
            padding: 2px 6px;
            font-size: 0.6rem;
        }

        .status-badge::before {
            width: 4px;
            height: 4px;
        }
    }
</style>

<div id="mainContainer">
    <?php
    if (isset($_SESSION['s_id'])) {
        $session = $_SESSION['s_id'];
        $query = "SELECT `student`.*, `session_master`.*, eg.group_name, eg.semester as group_sem, sem.elective_group_id
    FROM `student`
    INNER JOIN `session_master` ON `session_master`.`session_master_id`=`student`.`s_session_id`
    LEFT JOIN `student_elective_master` sem ON sem.student_id = `student`.s_id AND sem.course_id = `student`.s_course_id
    LEFT JOIN `elective_groups` eg ON eg.id = sem.elective_group_id
    WHERE `student`.`s_id` = '$session'";
        $res = mysqli_query($con, $query);
        if ($res && mysqli_num_rows($res) > 0) {
            $student = mysqli_fetch_array($res);
        } else {
            $student = null;
        }
    }
    ?>

    <?php if ($student): ?>

        <!-- College Branding -->
        <div class="college-branding">
            <span class="college-name">GIIT Professional College</span>
            <span class="college-affiliation">Affiliated to Kolhan University &nbsp;|&nbsp; Approved by HRD, Govt. of
                Jharkhand</span>
        </div>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar-wrapper">
                <div class="profile-avatar">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                    <h1 class="profile-title"><?php echo htmlspecialchars($student['s_name']); ?></h1>
                    <div class="profile-subtitle">
                        <i class="fas fa-id-badge"></i>
                        Roll: <strong><?php echo htmlspecialchars($student['s_roll_no']); ?></strong>
                        &nbsp;&bull;&nbsp; Sem <?php echo htmlspecialchars($student['s_cur_sem']); ?>
                    </div>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:12px;z-index:1;">
                <?php $cname = isset($_SESSION['course']['course_name']) ? htmlspecialchars($_SESSION['course']['course_name']) : ''; ?>
                <?php if ($cname): ?>
                    <div class="course-badge">
                        <i class="fas fa-graduation-cap"></i>
                        <?php echo $cname; ?>
                    </div>
                <?php endif; ?>
                <a href="student-dashboard_sunam.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Stats Bar -->
        <?php
        $fee_status = strtolower($student['s_fee_status'] ?? '');
        $badge_class = 'pending';
        if (in_array($fee_status, ['paid', 'fpd']))
            $badge_class = 'paid';
        if (in_array($fee_status, ['unpaid', 'due']))
            $badge_class = 'unpaid';
        $dob = $student['s_dob'];
        $dob_display = (!empty($dob) && $dob != '0000-00-00') ? date('d M Y', strtotime($dob)) : 'N/A';
        ?>
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-icon indigo"><i class="fas fa-hashtag"></i></div>
                <div>
                    <div class="stat-label">Roll Number</div>
                    <div class="stat-value"><?php echo htmlspecialchars($student['s_roll_no']); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon teal"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="stat-label">Current Sem</div>
                    <div class="stat-value">Semester <?php echo htmlspecialchars($student['s_cur_sem']); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon amber"><i class="fas fa-calendar-alt"></i></div>
                <div>
                    <div class="stat-label">Session</div>
                    <div class="stat-value"><?php echo htmlspecialchars($student['session_name']); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon rose"><i class="fas fa-wallet"></i></div>
                <div>
                    <div class="stat-label">Fee Status</div>
                    <div class="stat-value">
                        <span class="status-badge <?php echo $badge_class; ?>">
                            <?php echo htmlspecialchars(ucfirst($student['s_fee_status'])); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unified Info Card -->
        <div class="info-card combined-card">
            <div class="profile-details-wrapper">
                <!-- Academic Details -->
                <div class="details-section">
                    <div class="info-card-header academic">
                        <div class="card-header-icon indigo"><i class="fas fa-book-open"></i></div>
                        <div>
                            <div class="info-card-title">Academic Details</div>
                        </div>
                    </div>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Roll Number</span>
                            <span class="info-value highlight"><?php echo htmlspecialchars($student['s_roll_no']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Kolhan Roll No.</span>
                            <span
                                class="info-value"><?php echo !empty($student['s_ku_roll_no']) ? htmlspecialchars($student['s_ku_roll_no']) : 'N/A'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Registration No.</span>
                            <span
                                class="info-value"><?php echo !empty($student['s_registration_no']) ? htmlspecialchars($student['s_registration_no']) : 'N/A'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Session</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['session_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Current Semester</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['s_cur_sem']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Admitted Semester</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['s_admitted_sem']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Specialization</span>
                            <span
                                class="info-value"><?php echo !empty($student['group_name']) ? htmlspecialchars($student['group_name']) : 'Not Assigned'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Fee Status</span>
                            <span class="info-value">
                                <span class="status-badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars(ucfirst($student['s_fee_status'])); ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Personal Details -->
                <div class="details-section">
                    <div class="info-card-header personal">
                        <div class="card-header-icon teal"><i class="fas fa-id-card"></i></div>
                        <div>
                            <div class="info-card-title">Personal Details</div>
                        </div>
                    </div>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Full Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['s_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Gender</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['s_gender']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date of Birth</span>
                            <span class="info-value"><?php echo $dob_display; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Father's Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['s_father_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Mother's Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['s_mother_name']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Contact Details -->
                <div class="details-section">
                    <div class="info-card-header contact">
                        <div class="card-header-icon amber"><i class="fas fa-address-book"></i></div>
                        <div>
                            <div class="info-card-title">Contact Details</div>
                        </div>
                    </div>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Email Address</span>
                            <span
                                class="info-value"><?php echo !empty($student['s_email']) ? htmlspecialchars($student['s_email']) : 'N/A'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Personal Phone</span>
                            <span
                                class="info-value"><?php echo !empty($student['s_phone_self']) ? htmlspecialchars($student['s_phone_self']) : 'N/A'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Father's Phone</span>
                            <span
                                class="info-value"><?php echo !empty($student['s_phone_father']) ? htmlspecialchars($student['s_phone_father']) : 'N/A'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Mother's Phone</span>
                            <span
                                class="info-value"><?php echo !empty($student['s_phone_mother']) ? htmlspecialchars($student['s_phone_mother']) : 'N/A'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Alternative Phone</span>
                            <span
                                class="info-value"><?php echo !empty($student['s_phone_other']) ? htmlspecialchars($student['s_phone_other']) : 'N/A'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Alt. Relation</span>
                            <span
                                class="info-value"><?php echo !empty($student['s_phone_relation']) ? htmlspecialchars($student['s_phone_relation']) : 'N/A'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="error-card">
            <i class="fas fa-exclamation-triangle"></i>
            <h2 style="margin:0 0 10px;color:var(--text);">Profile Not Found</h2>
            <p style="color:var(--muted);margin:0 0 20px;">We couldn't locate your student profile. Please try logging in
                again.</p>
            <a href="student-login.php" class="btn-back"
                style="background:var(--primary);border-color:var(--primary);display:inline-flex;">
                <i class="fas fa-sign-in-alt"></i> Login Again
            </a>
        </div>
    <?php endif; ?>
</div>