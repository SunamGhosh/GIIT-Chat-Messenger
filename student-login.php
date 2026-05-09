<?php
session_start();
ob_start();

// ================== AUTO LOGIN USING COOKIE ==================
if (!isset($_SESSION['s_id']) && isset($_COOKIE['student_login'])) {
    $_SESSION['s_id'] = $_COOKIE['student_login'];
}

// Redirect if user is already logged in
if (isset($_SESSION['s_id'])) {
    header('Location: student-dashboard_sunam.php');
    ob_end_flush();
    exit;
}

// Include database configuration
require_once 'includes/configNew.php';

// Validate database connection
if (!$con) {
    $ErrMsg = "Database connection failed. Please contact administrator.";
}

include 'header.php';

// Process login form submission
if (isset($_POST['studentLogin'])) {
    $s_roll_no_r = trim($_POST['roll_no']);
    $s_password_r = trim($_POST['password']);

    // Prepare and execute login query
    $stmt = $con->prepare("SELECT * FROM student WHERE s_roll_no = ? AND s_status != 'inactive'");
    if ($stmt === false) {
        $ErrMsg = "An error occurred. Please try again later.";
    } else {
        $stmt->bind_param("s", $s_roll_no_r);
        if (!$stmt->execute()) {
            $ErrMsg = "An error occurred. Please try again later.";
        } else {
            $result = $stmt->get_result();
            $student_data = $result->fetch_assoc();
            $stmt->close();

            if ($student_data) {
                if ($s_password_r === $student_data['s_password']) {
                    if ($student_data['is_verified'] === 0 || $student_data['is_verified'] === 2) {
                        header('Location: student-disabled_page.php');
                        ob_end_flush();
                        exit;
                    }

                    $_SESSION['s_id'] = $student_data['s_id'];
                    $_SESSION['s_course_id'] = $student_data['s_course_id'] ?? 0;
                    $_SESSION['s_university_id'] = $student_data['s_university_id'] ?? 0;
                    $_SESSION['s_session_id'] = $student_data['s_session_id'] ?? 0;
                    $_SESSION['s_roll_no'] = $student_data['s_roll_no'];
                    $_SESSION['s_name'] = $student_data['s_name'];
                    $_SESSION['s_sem'] = $student_data['s_cur_sem'] ?? 1;

                    header('Location: student-dashboard_sunam.php');
                    ob_end_flush();
                    exit;
                } else {
                    $ErrMsg = "Invalid Roll No or Password";
                }
            } else {
                $ErrMsg = "Invalid Roll No or Password";
            }
        }
    }
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
        --accent: #4f46e5;
        --bg-main: #f5f5ff;
        --text-main: #0f172a;
        --text-muted: #64748b;
        --glass-bg: rgba(255, 255, 255, 0.88);
        --glass-border: rgba(99, 102, 241, 0.15);
        --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.12);
        --radius-2xl: 24px;
        --radius-xl: 16px;
    }

    body {
        background-color: var(--bg-main);
        background-image:
            radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.07) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.05) 0px, transparent 50%);
        font-family: 'Outfit', sans-serif !important;
        color: var(--text-main);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* Show only header2 for Login */
    .header1,
    .header3,
    #mu-menu {
        display: none !important;
    }

    /* Hide Header2 Left (Phone & Email) for Login Page */
    section.header2 .header2_left {
        display: none !important;
    }

    section.header2,
    section.header2 .container-fluid,
    section.header2 .header2_left,
    section.header2 .header2_right,
    section.header2 ul {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        justify-content: center !important;
    }

    section.header2 .container-fluid {
        flex-direction: column !important;
        gap: 8px !important;
        width: 100% !important;
    }

    section.header2 .header2_right ul {
        display: grid !important;
        grid-template-columns: repeat(2, auto) !important;
        gap: 10px 15px !important;
        justify-content: center !important;
    }

    /* Order logic: Internship & Placement on Top Row, Apply & Pay Fee Below */
    section.header2 .header2_right ul li:nth-child(1) {
        order: 3;
    }

    /* Apply Online */
    section.header2 .header2_right ul li:nth-child(2) {
        order: 1;
    }

    /* Summer Internship */
    section.header2 .header2_right ul li:nth-child(3) {
        order: 2;
    }

    /* Placements */
    section.header2 .header2_right ul li:nth-child(4) {
        order: 4;
    }

    /* Pay Fee Online */

    /* Order logic: Internship & Placement first, then Apply & Pay Fee */
    section.header2 .header2_right ul li:nth-child(1) {
        order: 3;
    }

    /* Apply Online */
    section.header2 .header2_right ul li:nth-child(2) {
        order: 1;
    }

    /* Summer Internship */
    section.header2 {
        top: 0 !important;
        margin: 0 !important;
        padding: 4px 0 !important;
        position: fixed !important;
        z-index: 10000 !important;
        width: 100% !important;
        height: auto !important;
        background: linear-gradient(135deg, #fff9c4 0%, #fff59d 50%, #fff176 100%) !important;
        border-bottom: 2px solid #ffd600 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
    }

    section.header2 .container-fluid {
        width: 100% !important;
        min-width: unset !important;
        height: auto !important;
        padding: 0 15px !important;
        flex-direction: row !important;
        /* Back to row since left is hidden */
        gap: 10px !important;
    }

    section.header2 ul {
        gap: 15px !important;
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
        flex-shrink: 0 !important;
    }

    section.header2 li {
        white-space: nowrap !important;
        flex-shrink: 0 !important;
    }

    body {
        padding-top: 55px !important;
    }

    .login-wrapper {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        margin: 0 !important;
        position: relative;
        z-index: 1;
    }

    @keyframes cardFadeUp {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .login-card {
        background: var(--glass-bg);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1.5px solid var(--glass-border);
        box-shadow: var(--shadow-xl);
        border-radius: var(--radius-2xl);
        width: 100%;
        max-width: 420px;
        padding: 0;
        animation: cardFadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        overflow: hidden;
    }

    .college-branding {
        text-align: center;
        background: #ffffff;
        padding: 18px 15px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    }

    .college-name {
        font-weight: 800;
        font-size: 1.4rem;
        color: #ce1126;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        margin-bottom: 4px;
        display: block;
        line-height: 1.1;
        font-family: 'Bookman Old Style', 'Bookman', serif !important;
    }

    .college-affiliation {
        font-size: 0.7rem;
        color: #000000;
        font-weight: 700;
        letter-spacing: 0.01em;
        display: block;
    }

    .login-content {
        padding: 25px 30px 30px;
    }

    .login-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--primary-light));
        z-index: 2;
    }

    .login-logo {
        text-align: center;
        margin-bottom: 20px;
    }

    .login-logo img {
        max-height: 44px;
        margin-bottom: 8px;
    }

    .login-title {
        font-weight: 800;
        font-size: 1.5rem;
        color: var(--text-main);
        letter-spacing: -0.02em;
        text-align: center;
        margin-bottom: 6px;
    }

    .login-subtitle {
        color: var(--text-muted);
        text-align: center;
        font-size: 0.9rem;
        margin-bottom: 24px;
        font-weight: 500;
    }

    .form-group {
        margin-bottom: 20px;
        position: relative;
    }

    .form-group label {
        font-weight: 700;
        font-size: 0.8rem;
        color: var(--text-main);
        margin-bottom: 8px;
        display: block;
        margin-left: 2px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .form-control {
        height: 52px;
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 18px;
        font-size: 1rem;
        font-weight: 500;
        color: var(--text-main);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        width: 100%;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .form-control:focus {
        background: #ffffff;
        border-color: #4f46e5;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1), 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        outline: none;
        transform: translateY(-1px);
    }

    .form-control::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }

    .btn-login {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: white;
        width: 100%;
        height: 52px;
        border-radius: var(--radius-xl);
        font-weight: 700;
        font-size: 1rem;
        border: none;
        box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        margin-top: 10px;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 30px -8px rgba(79, 70, 229, 0.5);
        color: white;
    }

    .btn-login:active {
        transform: scale(0.98);
    }

    .alert-danger {
        background: rgba(79, 70, 229, 0.08);
        border: 1px solid rgba(79, 70, 229, 0.2);
        color: var(--primary);
        border-radius: var(--radius-xl);
        padding: 12px 16px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-danger i {
        font-size: 1.1rem;
    }

    .login-footer {
        margin-top: 32px;
        text-align: center;
        font-size: 0.9rem;
        color: var(--text-muted);
    }

    .login-footer a {
        color: var(--primary);
        font-weight: 700;
        text-decoration: none;
        transition: color 0.2s;
    }

    .login-footer a:hover {
        color: var(--primary-dark);
    }

    /* Animation for inputs */
    .form-group {
        animation: cardFadeUp 0.6s ease-out both;
    }

    .form-group:nth-child(1) {
        animation-delay: 0.1s;
    }

    .form-group:nth-child(2) {
        animation-delay: 0.2s;
    }

    .form-group:nth-child(3) {
        animation-delay: 0.3s;
    }

    @media (max-width: 991px) {
        .login-card {
            max-width: 440px;
        }
    }

    @media (max-width: 480px) {
        section.header2 {
            height: auto !important;
            padding: 6px 0 !important;
        }

        section.header2 .header2_right ul {
            grid-template-columns: repeat(2, 1fr) !important;
            padding: 0 10px !important;
            gap: 8px !important;
        }

        body {
            padding-top: 75px !important;
        }

        .login-wrapper {
            margin: 0 !important;
            padding: 15px 10px !important;
        }

        .login-card {
            max-width: 100%;
        }

        .login-content {
            padding: 20px 15px 25px;
        }

        .login-title {
            font-size: 1.3rem;
        }

        .login-subtitle {
            font-size: 0.8rem;
            margin-bottom: 18px;
        }

        .form-control {
            height: 44px;
            font-size: 0.9rem;
        }

        .btn-login {
            height: 44px;
            font-size: 0.9rem;
        }
    }
    }
</style>

<div class="login-wrapper">
    <div class="login-card">
        <div class="college-branding">
            <span class="college-name">GIIT PROFESSIONAL COLLEGE</span>
            <span class="college-affiliation">Affiliated to KOLHAN UNIVERSITY Approved by HRD, Govt. of Jharkhand</span>
        </div>

        <div class="login-content">
            <div class="login-logo">
                <img src="assets/img/logo.jpg" alt="GIIT Logo">
                <div class="login-title">Student Portal</div>
                <div class="login-subtitle">Sign in to your academic account</div>
            </div>

            <?php if (isset($ErrMsg)): ?>
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-circle"></i>
                    <?= htmlspecialchars($ErrMsg) ?>
                </div>
            <?php endif; ?>

            <form id="std-form" action="" method="post" autocomplete="off">
                <div class="form-group">
                    <label for="roll_no">University Roll No</label>
                    <input type="text" class="form-control" id="roll_no" name="roll_no"
                        placeholder="Enter your roll number" required />
                </div>

                <div class="form-group">
                    <label for="password">Security Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••"
                        required />
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <button type="submit" name="studentLogin" class="btn btn-login">
                        Access My Dashboard <i class="fa fa-arrow-right" style="margin-left: 8px;"></i>
                    </button>
                </div>
            </form>


        </div>
    </div>
</div>

<script src="js/validate.min.js" type="text/javascript"></script>
<script type="text/javascript">
    $(function () {
        $("#std-form").validate({
            rules: {
                roll_no: { required: true },
                password: { required: true }
            },
            messages: {
                roll_no: 'Please enter your Roll No',
                password: { required: 'Please enter your Password' }
            },
            errorPlacement: function (error, element) {
                error.css({
                    'color': '#f43f5e',
                    'font-size': '0.75rem',
                    'font-weight': '600',
                    'margin-top': '5px',
                    'margin-left': '4px',
                    'display': 'block'
                });
                error.insertAfter(element);
            }
        });
    });
</script>