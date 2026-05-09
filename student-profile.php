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
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

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
        margin-top: 20px;
        padding-bottom: 40px;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
        padding-left: 15px;
        padding-right: 15px;
    }

    @keyframes headerShimmer {
        0% { transform: translateX(-100%) skewX(-15deg); }
        100% { transform: translateX(400%) skewX(-15deg); }
    }

    .profile-header {
        background: linear-gradient(135deg, #1e1b6e 0%, #312e81 35%, #4f46e5 70%, #818cf8 100%);
        padding: 30px !important;
        border-radius: var(--radius-lg);
        color: white !important;
        margin-bottom: 25px !important;
        box-shadow: 0 20px 40px -8px rgba(49, 46, 129, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.08) inset;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
        flex-wrap: wrap;
        gap: 20px;
    }

    .profile-header::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 250px;
        height: 250px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, transparent 65%);
        border-radius: 50%;
        pointer-events: none;
    }

    .profile-header::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 80px;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.18), transparent);
        animation: headerShimmer 3.5s ease-in-out infinite;
        pointer-events: none;
    }

    .profile-avatar-wrapper {
        display: flex;
        align-items: center;
        gap: 20px;
        z-index: 1;
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.5);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        color: white;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }

    .profile-title {
        font-weight: 800;
        font-size: 1.8rem;
        letter-spacing: -0.025em;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        line-height: 1.2;
        margin: 0;
    }

    .profile-subtitle {
        font-size: 1rem;
        font-weight: 500;
        opacity: 0.9;
        margin-top: 5px;
        letter-spacing: 0.01em;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .profile-actions {
        z-index: 1;
    }

    .btn-back {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        color: white;
        padding: 10px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-back:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
        color: white;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 25px;
        margin-top: 25px;
    }

    .info-card {
        background: var(--card-bg);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-radius: var(--radius-lg);
        border: 1px solid var(--glass-border);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
        border-color: rgba(99, 102, 241, 0.3);
    }

    .info-card-header {
        background: rgba(79, 70, 229, 0.05);
        padding: 18px 24px;
        border-bottom: 1px solid var(--glass-border);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .info-card-header i {
        font-size: 1.4rem;
        background: linear-gradient(135deg, #312e81 0%, #818cf8 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .info-card-title {
        font-weight: 700;
        font-size: 1.15rem;
        color: var(--primary-dark);
        margin: 0;
    }

    .info-list {
        padding: 10px 24px 24px;
    }

    .info-item {
        padding: 12px 0;
        border-bottom: 1px dashed rgba(0,0,0,0.08);
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .info-value {
        font-size: 1.05rem;
        font-weight: 600;
        color: var(--text-main);
        word-break: break-word;
    }

    .info-value.highlight {
        color: var(--primary);
        font-weight: 700;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 700;
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .status-badge.pending {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .status-badge.unpaid {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            align-items: flex-start;
            padding: 24px !important;
        }
        
        .profile-actions {
            width: 100%;
        }
        
        .btn-back {
            width: 100%;
            justify-content: center;
        }

        .profile-title {
            font-size: 1.5rem;
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
        <div class="profile-header">
            <div class="profile-avatar-wrapper">
                <div class="profile-avatar">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                    <h1 class="profile-title"><?php echo htmlspecialchars($student['s_name']); ?></h1>
                    <div class="profile-subtitle">
                        <i class="fas fa-graduation-cap"></i>
                        <?php echo isset($_SESSION['course']['course_name']) ? htmlspecialchars($_SESSION['course']['course_name']) : ''; ?>
                    </div>
                </div>
            </div>
            <div class="profile-actions">
                <a href="student-dashboard_sunam.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="profile-grid">
            <!-- Academic Information -->
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-book-open"></i>
                    <h3 class="info-card-title">Academic Details</h3>
                </div>
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">Roll Number</span>
                        <span class="info-value highlight"><?php echo htmlspecialchars($student['s_roll_no']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Kolhan Roll Number</span>
                        <span class="info-value"><?php echo !empty($student['s_ku_roll_no']) ? htmlspecialchars($student['s_ku_roll_no']) : 'N/A'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Registration Number</span>
                        <span class="info-value"><?php echo !empty($student['s_registration_no']) ? htmlspecialchars($student['s_registration_no']) : 'N/A'; ?></span>
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
                        <span class="info-label">Specialization</span>
                        <span class="info-value"><?php echo !empty($student['group_name']) ? htmlspecialchars($student['group_name']) : 'Not Assigned'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Admitted Semester</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['s_admitted_sem']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Fee Status</span>
                        <span class="info-value">
                            <?php 
                                $fee_status = strtolower($student['s_fee_status']);
                                $badge_class = 'pending';
                                if(in_array($fee_status, ['paid', 'fpd'])) $badge_class = '';
                                if(in_array($fee_status, ['unpaid', 'due'])) $badge_class = 'unpaid';
                            ?>
                            <span class="status-badge <?php echo $badge_class; ?>">
                                <?php echo htmlspecialchars(ucfirst($student['s_fee_status'])); ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-id-card"></i>
                    <h3 class="info-card-title">Personal Details</h3>
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
                        <span class="info-value">
                            <?php 
                                $dob = $student['s_dob'];
                                echo (!empty($dob) && $dob != '0000-00-00') ? date('d M Y', strtotime($dob)) : 'N/A'; 
                            ?>
                        </span>
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

            <!-- Contact Information -->
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-address-book"></i>
                    <h3 class="info-card-title">Contact Details</h3>
                </div>
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">Email Address</span>
                        <span class="info-value"><?php echo !empty($student['s_email']) ? htmlspecialchars($student['s_email']) : 'N/A'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Personal Phone</span>
                        <span class="info-value"><?php echo !empty($student['s_phone_self']) ? htmlspecialchars($student['s_phone_self']) : 'N/A'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Father's Phone</span>
                        <span class="info-value"><?php echo !empty($student['s_phone_father']) ? htmlspecialchars($student['s_phone_father']) : 'N/A'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Mother's Phone</span>
                        <span class="info-value"><?php echo !empty($student['s_phone_mother']) ? htmlspecialchars($student['s_phone_mother']) : 'N/A'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Alternative Phone</span>
                        <span class="info-value"><?php echo !empty($student['s_phone_other']) ? htmlspecialchars($student['s_phone_other']) : 'N/A'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Alternative Relation</span>
                        <span class="info-value"><?php echo !empty($student['s_phone_relation']) ? htmlspecialchars($student['s_phone_relation']) : 'N/A'; ?></span>
                    </div>
                </div>
            </div>

        </div>

    <?php else: ?>
        <div class="info-card" style="text-align: center; padding: 40px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ef4444; margin-bottom: 20px;"></i>
            <h2 style="margin:0 0 10px; color: var(--text-main);">Profile Not Found</h2>
            <p style="color: var(--text-muted); margin: 0 0 20px;">We couldn't locate your student profile. Please try logging in again.</p>
            <a href="student-login.php" class="btn-back" style="background: var(--primary); color: white; border: none;">
                Login Again
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include("footer.php"); ?>