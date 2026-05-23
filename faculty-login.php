<?php
session_start();
ob_start();

// Redirect if faculty is already logged in
if (isset($_SESSION['facultyLogin'])) {
    header('Location: /student-management/faculty-dashboard_sunam.php');
    ob_end_flush();
    exit;
}

// Include database configuration
require_once 'includes/configNew.php';

// Validate database connection
if (!$con) {
    $msg = '<div class="alert alert-danger">Database connection failed. Please contact administrator.</div>';
}

include 'header.php';

// Process login form submission
if (isset($_POST['facultyLogin'])) {
    $faculty_userid_r = trim($_POST['faculty_id']);
    $faculty_password_r = trim($_POST['faculty_password']);

    // Prepare and execute login query using prepared statements for security
    $stmt = $con->prepare("SELECT * FROM faculty_master WHERE faculty_id = ? AND faculty_password = ? AND faculty_status != 'inactive'");
    if ($stmt === false) {
        $msg = '<div class="alert alert-danger">An error occurred. Please try again later.</div>';
    } else {
        $stmt->bind_param("ss", $faculty_userid_r, $faculty_password_r);
        if (!$stmt->execute()) {
            $msg = '<div class="alert alert-danger">An error occurred. Please try again later.</div>';
        } else {
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                
                $faculty_master_id = $row['faculty_master_id'];
                $faculty_id = $row['faculty_id'];
                $faculty_name = $row['faculty_name'];
                $faculty_short_name = $row['faculty_short_name'];
                $faculty_department_id = $row['faculty_department_id'];

                // Core Session Variables
                $_SESSION['facultyLogin'] = $faculty_master_id;
                $_SESSION['faculty_id'] = $faculty_id;
                $_SESSION['loggedin_id'] = $faculty_id;
                $_SESSION['faculty_name'] = $faculty_name;
                $_SESSION['faculty_short_name'] = $faculty_short_name;
                
                // Fetch full faculty data for the session
                $_SESSION['faculty'] = $row;
                
                // Advanced Session Variables from User's Snippet
                $_SESSION['ONLINE_CLASSES'] = true;
                $_SESSION['csrf_logged_in'] = true;
                $_SESSION["giit_admin_id"] = $faculty_master_id;
                $_SESSION["giit_admin_login_id"] = $faculty_id;
                $_SESSION["giit_admin_name"] = $faculty_name;
                $_SESSION["giit_admin_department"] = $faculty_department_id;
                $_SESSION["giit_admin_status"] = md5("true");

                if ($faculty_department_id == 99) {
                    $_SESSION['adminSession'] = $faculty_id;
                    if ($faculty_id == 'F060') {
                        $_SESSION['questionBankSession'] = $faculty_id;
                    }
                }

                header('Location: /student-management/faculty-dashboard_sunam.php');
                ob_end_flush();
                exit;
            } else {
                $msg = '<div class="alert alert-danger">Invalid Login Userid or Password</div>';
            }
        }
        $stmt->close();
    }
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<style type="text/css">
    :root {
        --primary: #1e1b6e;
        --primary-light: #4f46e5;
        --primary-dark: #16144b;
        --accent: #1e1b6e;
        --bg-main: #f0f2f9;
        --text-main: #0f172a;
        --text-muted: #64748b;
        --glass-bg: rgba(255, 255, 255, 0.9);
        --glass-border: rgba(30, 27, 110, 0.12);
        --shadow-xl: 0 25px 50px -12px rgba(30, 27, 110, 0.15);
        --radius-2xl: 24px;
        --radius-xl: 16px;
    }

    body {
        background-color: var(--bg-main);
        background-image: 
            radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.08) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(30, 27, 110, 0.08) 0px, transparent 50%),
            url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%231e1b6e' fill-opacity='0.02'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        font-family: 'Outfit', sans-serif !important;
        color: var(--text-main);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* Hide specific site header elements, showing header1 only */
    .header2, .header3, #mu-menu {
        display: none !important;
    }

    body {
        padding-top: 38px !important;
    }

    .login-wrapper {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        position: relative;
        z-index: 1;
    }

    @keyframes cardFadeUp {
        from { opacity: 0; transform: translateY(30px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes shimmer {
        0% { transform: translateX(-100%) skewX(-15deg); }
        100% { transform: translateX(400%) skewX(-15deg); }
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
        filter: drop-shadow(0 4px 6px rgba(0,0,0,0.05));
    }

    .login-title {
        font-weight: 800;
        font-size: 1.5rem;
        color: var(--text-main);
        letter-spacing: -0.02em;
        margin-bottom: 6px;
    }

    .login-subtitle {
        color: var(--text-muted);
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 24px;
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
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding-left: 2px;
    }

    .form-control {
        height: 52px;
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 18px;
        font-size: 1rem;
        font-weight: 500;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        width: 100%;
        color: var(--text-main);
    }

    .form-control:focus {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1), 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        outline: none;
        transform: translateY(-1px);
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
        box-shadow: 0 10px 20px -5px rgba(30, 27, 110, 0.3);
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        margin-top: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    .btn-login::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 60px;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        animation: shimmer 2.5s infinite linear;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 30px -8px rgba(30, 27, 110, 0.4);
        color: white;
    }

    .btn-login:active {
        transform: scale(0.98);
    }

    .alert-danger {
        background: rgba(30, 27, 110, 0.08);
        border: 1px solid rgba(30, 27, 110, 0.2);
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

    @media (max-width: 480px) {
        .login-wrapper { padding: 20px 10px !important; align-items: flex-start; }
        .login-card { border-radius: var(--radius-xl); max-width: 100%; margin-top: 10px; }
        .login-content { padding: 20px 20px 25px; }
        .login-title { font-size: 1.35rem; }
        .login-subtitle { font-size: 0.85rem; margin-bottom: 20px; }
        .form-control { height: 48px; font-size: 0.95rem; }
        .btn-login { height: 48px; font-size: 0.95rem; }
    }
</style>

<div class="login-wrapper">
    <div class="login-card">
        <div class="college-branding">
            <span class="college-name">GIIT PROFESSIONAL COLLEGE</span>
            <span class="college-affiliation">Faculty Administration Portal</span>
        </div>

        <div class="login-content">
            <div class="login-logo">
                <img src="assets/img/logo.jpg" alt="GIIT Logo">
                <div class="login-title">Faculty Login</div>
                <div class="login-subtitle">Secure access for staff & administration</div>
            </div>

            <?php if (isset($msg)) echo $msg; ?>

            <form id="faculty-form" action="" method="post" autocomplete="off">
                <div class="form-group">
                    <label for="faculty_id">Faculty User ID</label>
                    <input type="text" class="form-control" id="faculty_id" name="faculty_id" 
                           placeholder="Enter your ID (e.g. F001)" required />
                </div>

                <div class="form-group">
                    <label for="faculty_password">Security Password</label>
                    <input type="password" class="form-control" id="faculty_password" name="faculty_password" 
                           placeholder="••••••••" required />
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <button type="submit" name="facultyLogin" class="btn btn-login">
                        Sign In to Portal <i class="fa fa-arrow-right" style="margin-left: 8px;"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/validate.min.js" type="text/javascript"></script>
<script type="text/javascript">
    $(function () {
        $("#faculty-form").validate({
            rules: {
                faculty_id: { required: true },
                faculty_password: { required: true }
            },
            messages: {
                faculty_id: 'Please enter your User ID',
                faculty_password: 'Please enter your Password'
            },
            errorPlacement: function (error, element) {
                error.css({
                    'color': '#f43f5e',
                    'font-size': '0.75rem',
                    'font-weight': '600',
                    'margin-top': '5px',
                    'display': 'block'
                });
                error.insertAfter(element);
            }
        });
    });
</script>
<?php include("footer.php"); ?>