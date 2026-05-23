<?php
session_start();
if (!isset($_SESSION['s_id'])) {
    header('location:student-login.php');
    exit;
}
require_once("configNew.php");
include("header.php");

// Fetch course mode
$course_id = $_SESSION['s_course_id'] ?? $_REQUEST['course'] ?? 0;

$qMode = "SELECT course_mode FROM course_master WHERE course_master_id='$course_id'";
$rMode = mysqli_query($con, $qMode);
$rowM  = mysqli_fetch_array($rMode);
$cMode = $rowM['course_mode'] ?? 'N';
$max   = ($cMode == 's' || $cMode == 'S' || $cMode == 'N') ? 6 : 3;

// Build tabs+data ahead of time
$tabs = [];
for ($i = 1; $i <= $max; $i++) {
    $label = ($cMode == 's' || $cMode == 'S' || $cMode == 'N') ? "Semester $i" : "Year $i";
    $sql = "SELECT * FROM `syllabuspdf` WHERE `syllabus_sem` = $i AND `syllabus_course` = '$course_id' AND `syllabus_status` = 'active'";
    $res = mysqli_query($con, $sql);
    $files = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $files[] = $r;
    }
    $tabs[] = ['label' => $label, 'files' => $files];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="author" content="Sunam Ghosh --@SunamGhosh">
    <title>Syllabus | GIIT Professional College</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ── Reset & tokens ────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box !important; }
        :root {
            --primary:      #4f46e5;
            --primary-lt:   #818cf8;
            --primary-dk:   #312e81;
            --bg-main:      #f5f5ff;
            --card-bg:      rgba(255,255,255,.9);
            --text:         #0f172a;
            --muted:        #64748b;
            --border:       rgba(99,102,241,.15);
            --teal:         #009688;
            --radius-lg:    20px;
            --radius-md:    14px;
            --radius-sm:    10px;
        }

        /* ── Base ──────────────────────────────────────── */
        body {
            background-color: var(--bg-main);
            background-image:
                radial-gradient(at 0% 0%,   rgba(79,70,229,.07) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99,102,241,.05) 0px, transparent 50%);
            font-family: 'Outfit', sans-serif !important;
            color: var(--text);
            margin: 0; padding: 0;
            overflow-x: hidden;
        }
        .header1, .header2, .header3, #mu-menu { display: none !important; }

        #qbPage {
            max-width: 1000px;
            margin: 14px auto;
            padding: 0 15px 60px;
        }

        /* ── Animations ────────────────────────────────── */
        @keyframes shimmer {
            0%   { transform: translateX(-100%) skewX(-15deg); }
            100% { transform: translateX(400%)  skewX(-15deg); }
        }
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(18px); }
            to   { opacity:1; transform:translateY(0);    }
        }

        /* ── College branding ──────────────────────────── */
        .college-branding {
            text-align: center;
            background: #fff;
            padding: 12px 15px;
            border-radius: var(--radius-md) var(--radius-md) 0 0;
            border: 1px solid rgba(0,0,0,.05);
            border-bottom: none;
            animation: fadeUp .6s ease-out;
        }
        .college-name {
            font-weight: 800; font-size: 1.8rem; color: #ce1126;
            text-transform: uppercase; display: block; line-height: 1.1; margin-bottom: 3px;
        }
        .college-affiliation {
            font-size: .8rem; color: #000; font-weight: 700;
            display: block; white-space: nowrap;
        }

        /* ── Page header ───────────────────────────────── */
        .page-header {
            background: linear-gradient(135deg,#1e1b6e 0%,#312e81 35%,#4f46e5 70%,#818cf8 100%);
            padding: 15px 20px !important;
            border-radius: 0 0 var(--radius-md) var(--radius-md);
            color: #fff !important;
            margin-top: -1px;
            margin-bottom: 22px !important;
            box-shadow: 0 10px 25px -5px rgba(49,46,129,0.3);
            display: flex; justify-content: space-between; align-items: center;
            position: relative; overflow: hidden;
        }
        .page-header::after {
            content:''; position:absolute; top:0; left:0; width:60px; height:100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.18), transparent);
            animation: shimmer 3.5s ease-in-out infinite;
            pointer-events:none;
        }

        .page-title-text { font-weight: 800; font-size: 1.2rem; z-index: 2; }
        .page-title-text span { display: block; font-size: .8rem; font-weight: 500; opacity: .85; margin-top: 3px; }

        .course-pill {
            background: rgba(255,255,255,.18); backdrop-filter:blur(10px);
            padding:6px 14px; border-radius:50px; color:#fff;
            font-size:.7rem; font-weight:700; text-transform:uppercase;
            border:1.5px solid rgba(255,255,255,.4);
            z-index:1;
        }

        /* ── Sem selector ──────────────────────────────── */
        .sem-selector {
            display:flex; flex-wrap:wrap; gap:8px;
            justify-content:center; margin-bottom:20px;
        }
        .sem-btn {
            background: var(--card-bg); color: var(--muted);
            border: 1.5px solid var(--border);
            border-radius: 12px; padding: 10px 22px;
            font-family:'Outfit',sans-serif; font-size:.85rem; font-weight:600;
            cursor:pointer; transition:all .25s ease;
        }
        .sem-btn.active {
            background:var(--primary); color:#fff !important; border-color:var(--primary);
            box-shadow:0 8px 20px -6px rgba(79,70,229,.4);
        }

        /* ── Content ───────────────────────────────────── */
        .tab-panel { display:none; }
        .tab-panel.active { display:block; animation: fadeUp .35s ease-out; }

        .syllabus-card {
            background: #fff;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            animation: fadeUp .4s ease-out both;
        }
        .syllabus-preview {
            width: 100%;
            height: 650px;
            border: none;
            background: #f1f5f9;
        }
        .syllabus-footer {
            padding: 15px 20px;
            background: #f8fafc;
            border-top: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
        }
        .file-name { font-weight: 700; font-size: 0.9rem; color: var(--text); }
        
        .dl-btn {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--teal); color: #fff !important;
            padding: 8px 20px; border-radius: 50px;
            font-size: .8rem; font-weight: 700;
            text-decoration: none !important;
            transition: all .2s;
            box-shadow: 0 4px 12px rgba(0,150,136,0.25);
        }
        .dl-btn:hover { background: #00796b; transform: translateY(-1px); }

        .empty-state {
            text-align: center; padding: 60px 20px;
            background: #fff; border-radius: var(--radius-md);
            border: 2px dashed var(--border);
            animation: fadeUp .4s ease-out;
        }
        .empty-state i { font-size: 3rem; color: var(--border); margin-bottom: 15px; display: block; }
        .empty-state p { color: var(--muted); font-size: 0.9rem; margin: 0; font-weight: 500; }

        @media (max-width: 480px) {
            #qbPage { margin: 5px auto; padding: 0 10px 60px; }
            .college-name { font-size: 1.2rem !important; }
            .college-affiliation { font-size: .6rem !important; white-space: normal !important; }
            .page-header { border-radius: 0 0 20px 20px !important; }
            .course-pill { display: none; }
            .sem-btn { padding: 8px 16px; font-size: .78rem; }
            .syllabus-preview { height: 450px; }
            .syllabus-footer { flex-direction: column; gap: 12px; text-align: center; }
        }
    </style>
</head>
<body>

<div id="qbPage">

    <!-- ── College Branding ── -->
    <div class="college-branding">
        <span class="college-name">GIIT PROFESSIONAL COLLEGE</span>
        <span class="college-affiliation">Affiliated to KOLHAN UNIVERSITY | Approved by HRD, Govt. of Jharkhand</span>
    </div>

    <!-- ── Page Header ── -->
    <div class="page-header">
        <div class="page-title-text">
            Syllabus
            <span>Check your course curriculum and academic structure</span>
        </div>
        <div class="course-pill">
            <i class="fa fa-graduation-cap"></i>
            <?php echo htmlspecialchars($_SESSION['course']['course_name'] ?? ''); ?>
        </div>
    </div>

    <!-- ── Semester Selector ── -->
    <div class="sem-selector">
        <?php foreach ($tabs as $idx => $tab): ?>
            <button class="sem-btn <?= $idx === 0 ? 'active' : '' ?>" data-tab="tab<?= $idx ?>">
                <?= htmlspecialchars($tab['label']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- ── Tab Panels ── -->
    <?php foreach ($tabs as $idx => $tab): 
        $files = $tab['files'];
    ?>
    <div class="tab-panel <?= $idx === 0 ? 'active' : '' ?>" id="tab<?= $idx ?>">
        <?php if (empty($files)): ?>
            <div class="empty-state">
                <i class="fa fa-clock"></i>
                <p>Syllabus for this <?= strtolower($tab['label']) ?> will be added soon.<br>Please check back later!</p>
            </div>
        <?php else: ?>
            <?php foreach ($files as $file): 
                $filename = $file['syllabus_file'];
                $isPdf = (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf');
            ?>
            <div class="syllabus-card">
                <?php if ($isPdf): ?>
                    <iframe class="syllabus-preview" src="https://docs.google.com/gview?url=http://<?php echo $_SERVER['HTTP_HOST']; ?>/<?php echo $file['syllabus_file']; ?>&amp;embedded=true"></iframe>
                <?php else: ?>
                    <div style="padding:10px; background:#fff; text-align:center;">
                        <img src="<?php echo $file['syllabus_file']; ?>" style="max-width:100%; border-radius:8px;" alt="Syllabus Image">
                    </div>
                <?php endif; ?>
                
                <div class="syllabus-footer">
                    <span class="file-name">
                        <i class="far fa-file-alt"></i> 
                        <?php echo $tab['label']; ?> Curriculum
                    </span>
                    <a href="<?php echo $file['syllabus_file']; ?>" class="dl-btn" download>
                        <i class="fa fa-download"></i> Download PDF
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

</div>

<script>
    document.querySelectorAll('.sem-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.sem-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(btn.getAttribute('data-tab')).classList.add('active');
        });
    });
</script>

<?php include("footer.php"); ?>
</body>
</html>