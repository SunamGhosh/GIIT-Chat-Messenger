<?php
session_start();
if (!isset($_SESSION['s_id'])) {
    header('location:student-login.php');
    exit();
}
$sid = $_SESSION["s_id"];
$sroll = $_SESSION['s_roll_no'];
require("dn_script/connect.php");
include("header.php");

if (isset($_SESSION['s_id'])) {
    $query = "SELECT * FROM student WHERE s_id = " . $_SESSION['s_id'];
    $res = mysqli_query($con, $query);
    $student = mysqli_fetch_array($res);
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style type="text/css">
    * { box-sizing: border-box !important; }
    :root {
        --primary: #4f46e5;
        --teal: #14b8a6;
        --rose: #f43f5e;
        --bg: #f8fafc;
        --border: #e2e8f0;
        --text: #1e293b;
        --muted: #64748b;
        --radius: 12px;
    }

    body {
        background: var(--bg);
        font-family: 'Outfit', sans-serif !important;
        color: var(--text);
        margin: 0; padding: 0;
    }

    .header1, .header2, .header3, #mu-menu { display: none !important; }
    body { padding-top: 0 !important; }

    #mainContainer { max-width: 1000px; margin: 0 auto; padding: 15px; }

    /* ── Branding ── */
    .college-branding {
        text-align: center; background: #fff; padding: 12px;
        border-radius: var(--radius) var(--radius) 0 0; border: 1px solid var(--border);
    }
    .college-name { font-weight: 900; font-size: 1.8rem; color: #ce1126; text-transform: uppercase; line-height: 1; }
    .college-sub { font-size: 0.75rem; color: #334155; font-weight: 700; text-transform: uppercase; margin-top: 5px; letter-spacing: 0.5px; }

    /* ── Profile Header ── */
    .profile-header {
        background: linear-gradient(135deg, #1e1b6e 0%, #4f46e5 100%);
        padding: 18px 25px; border-radius: 0 0 var(--radius) var(--radius);
        color: #fff; display: flex; justify-content: space-between; align-items: center;
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.2); margin-bottom: 25px;
    }
    .profile-info h1 { font-size: 1.4rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 10px; }
    .profile-info p { font-size: 0.85rem; opacity: 0.85; margin: 4px 0 0; font-weight: 500; }
    .btn-back {
        background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.3);
        color: #fff; padding: 7px 16px; border-radius: 50px; font-weight: 600; font-size: 0.8rem;
        text-decoration: none; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-back:hover { background: #fff; color: var(--primary); transform: translateX(-3px); }

    /* ── Auth Card (Nice Login) ── */
    .auth-section { display: flex; justify-content: center; padding: 50px 0; }
    .auth-card {
        background: #fff; border-radius: 20px; padding: 40px; width: 100%; max-width: 460px;
        box-shadow: 0 20px 50px -10px rgba(0,0,0,0.08); border: 1px solid var(--border);
        text-align: center; animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes slideUp { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: translateY(0); } }
    
    .auth-card h2 { font-weight: 600; font-size: 1.15rem; color: #334155; margin-bottom: 25px; }
    .nice-input-group { margin-bottom: 20px; position: relative; }
    .nice-input {
        width: 100%; height: 50px; padding: 12px 18px; padding-right: 50px;
        border-radius: 10px; border: 1px solid #cbd5e1; background: #fff;
        font-family: inherit; font-size: 0.95rem; transition: all 0.2s ease;
    }
    .nice-input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
    .eye-btn {
        position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
        background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 6px; 
        color: #64748b; cursor: pointer; padding: 6px 10px; font-size: 0.9rem;
    }

    .btn-row { display: flex; gap: 10px; justify-content: center; margin-top: 5px; }
    .btn-nice {
        min-width: 130px; height: 46px; border-radius: 8px; border: none; font-weight: 700;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        cursor: pointer; transition: all 0.2s ease; font-size: 0.9rem; color: #fff; text-transform: uppercase;
    }
    .btn-login { background: #5cb85c; } /* Matching green in screenshot */
    .btn-login:hover { background: #4cae4c; transform: translateY(-1px); }
    .btn-forgot-nice { background: #d9534f; } /* Matching red in screenshot */
    .btn-forgot-nice:hover { background: #c9302c; transform: translateY(-1px); }
    .btn-setup { background: var(--primary); width: 100%; }

    /* ── Modern Table View ── */
    .table-container {
        background: #fff; border-radius: 8px; border: 1px solid var(--border);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden;
    }
    .locker-table { width: 100%; border-collapse: collapse; }
    .locker-table th {
        background: #fdf2f4; /* Light institutional tint */
        color: #334155; font-weight: 800; font-size: 0.8rem;
        text-transform: uppercase; letter-spacing: 0.05em; padding: 15px 20px;
        text-align: left; border-bottom: 1px solid var(--border);
    }
    .locker-table td { padding: 12px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; color: #475569; }
    .locker-table tr:hover { background: #fff9fa; }
    
    .sno-col { width: 100px; }
    .doc-col { font-weight: 500; }
    .action-col { width: 220px; text-align: left; }

    .btn-dl {
        background: #5cb85c; color: #fff; padding: 7px 15px; border-radius: 4px;
        font-weight: 600; font-size: 0.8rem; text-decoration: none;
        display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease;
    }
    .btn-dl:hover { background: #4cae4c; }

    #error_msg { color: var(--rose); font-weight: 600; font-size: 0.85rem; margin-top: 10px; min-height: 20px; }
</style>

<div id="mainContainer">
    <!-- Branding -->
    <div class="college-branding">
        <div class="college-name">GIIT Professional College</div>
        <div class="college-sub">Affiliated to Kolhan University | Approved by HRD, Govt. of Jharkhand</div>
    </div>

    <!-- Header -->
    <div class="profile-header">
        <div class="profile-info">
            <h1><i class="fas fa-vault"></i> Digital Locker</h1>
            <p><?php echo htmlspecialchars($student['s_name']); ?> • <?php echo htmlspecialchars($_SESSION['course']['course_name']); ?></p>
        </div>
        <a href="student-dashboard_sunam.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (!isset($_SESSION["digital_status"])): ?>
        <?php
        $query = "SELECT * from student_docs_login WHERE roll='$sroll';";
        $res = mysqli_query($con, $query);
        $has_account = mysqli_num_rows($res) > 0;
        ?>

        <div class="auth-section">
            <?php if (!$has_account): ?>
                <div class="auth-card">
                    <h2>Please Create Your Digital Locker Password</h2>
                    <div class="nice-input-group">
                        <input type="password" id="password_one" class="nice-input" placeholder="Enter New Password">
                        <button class="eye-btn" data-toggle="password" data-target="#password_one"><i class="fa fa-eye"></i></button>
                    </div>
                    <div class="nice-input-group">
                        <input type="password" id="password_two" class="nice-input" placeholder="Repeat Password">
                        <button class="eye-btn" data-toggle="password" data-target="#password_two"><i class="fa fa-eye"></i></button>
                    </div>
                    <p id="error_msg"></p>
                    <button class="btn-nice btn-setup" id="save_password">
                        SAVE PASSWORD <i class="fas fa-lock"></i>
                    </button>
                </div>
            <?php else: ?>
                <div class="auth-card">
                    <h2>Please Enter Your Digital Locker Password</h2>
                    <div class="nice-input-group">
                        <input type="password" id="password_digital" class="nice-input" placeholder="Enter Your Password">
                        <button class="eye-btn" data-toggle="password" data-target="#password_digital"><i class="fa fa-eye"></i></button>
                    </div>
                    <p id="error_msg"></p>
                    <div class="btn-row">
                        <button class="btn-nice btn-login" id="digital_login">
                            <i class="fas fa-key"></i> LOGIN
                        </button>
                        <button class="btn-nice btn-forgot-nice" id="forgot_password">
                            <i class="fas fa-lock"></i> Forgot Password
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <?php
        $query = "
            SELECT sdd.*, sdt.type_name
            FROM student_document_details sdd
            JOIN student_document_type_master sdt ON sdt.type_id = sdd.document_type
            WHERE sdd.student_roll = '$sroll'
            ORDER BY sdd.document_type
        ";
        $res = mysqli_query($con, $query);
        ?>

        <div class="table-container">
            <table class="locker-table">
                <thead>
                    <tr>
                        <th class="sno-col">SNO.</th>
                        <th class="doc-col">DOCUMENT</th>
                        <th class="action-col">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res) == 0): ?>
                        <tr>
                            <td colspan="3" style="text-align:center; padding:40px; color:var(--muted);">
                                No documents found in your locker.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $i = 0;
                        while ($row = mysqli_fetch_object($res)):
                            $i++;
                        ?>
                            <tr>
                                <td class="sno-col"><?php echo $i; ?></td>
                                <td class="doc-col"><?php echo htmlspecialchars($row->type_name); ?></td>
                                <td class="action-col">
                                    <a href="digital_locker/view_docs.php?type=<?php echo $row->document_type; ?>" target="_blank" class="btn-dl">
                                        <i class="fas fa-download"></i> View / Download
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</div>

<script src="/digital_locker/digital.js"></script>
<?php include("footer.php"); ?>