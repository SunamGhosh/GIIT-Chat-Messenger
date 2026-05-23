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
$category_id = 1; // University Questions

$qMode = "SELECT course_mode FROM course_master WHERE course_master_id='$course_id'";
$rMode = mysqli_query($con, $qMode);
$rowM  = mysqli_fetch_array($rMode);
$cMode = $rowM['course_mode'] ?? 'N';
$max   = ($cMode == 's' || $cMode == 'S' || $cMode == 'N') ? 6 : 3;

// Build tabs+data ahead of time
$tabs = [];
for ($i = 1; $i <= $max; $i++) {
    $label = ($cMode == 's' || $cMode == 'S' || $cMode == 'N') ? "Semester $i" : "Year $i";
    $shortLabel = ($cMode == 's' || $cMode == 'S' || $cMode == 'N') ? "Sem $i" : "Year $i";
    $sql = "
        SELECT qb.questionbank_id, qb.questionbank_name, qb.questionbank_file, sub.subject_name, cat.qb_cat_name
        FROM question_bank qb
        JOIN subject_master sub ON sub.subject_master_id = qb.questionbank_subject
        JOIN question_bank_category_master cat ON cat.qb_cat_id = qb.questionbank_category_id
        WHERE qb.questionbank_course_id = '$course_id'
          AND qb.questionbank_semester  = $i
          AND qb.questionbank_status    = 'active'
          AND qb.questionbank_category_id = '$category_id'
        ORDER BY sub.subject_name ASC, qb.questionbank_id DESC
    ";
    $res  = mysqli_query($con, $sql);
    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $rows[] = $r;
    }
    $tabs[] = ['label' => $label, 'short' => $shortLabel, 'rows' => $rows];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="GIIT University Question Papers – Download previous year university exam papers.">
    <meta name="author" content="Sunam Ghosh --@SunamGhosh">
    <title>University Questions | GIIT Professional College</title>
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
            --shadow-md:    0 4px 18px -2px rgba(79,70,229,.13);
            --shadow-lg:    0 12px 32px -4px rgba(79,70,229,.2);
        }

        /* ── Base ──────────────────────────────────────── */
        body {
            background-color: var(--bg-main);
            background-image:
                radial-gradient(at 0% 0%,   rgba(79,70,229,.07) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99,102,241,.05) 0px, transparent 50%);
            font-family: 'Outfit', sans-serif !important;
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden !important;
            margin: 0; padding: 0;
        }
        .header1, .header2, .header3, #mu-menu { display: none !important; }

        /* ── Page wrapper ──────────────────────────────── */
        #qbPage {
            max-width: 960px;
            margin: 14px auto;
            padding: 0 10px 60px;
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
        @keyframes pulse {
            0%,100% { box-shadow: 0 0 0 0   rgba(255,255,255,.3), 0 4px 15px rgba(0,0,0,.15); }
            50%      { box-shadow: 0 0 0 6px rgba(255,255,255,.08),0 4px 20px rgba(0,0,0,.2);  }
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
            font-weight: 800; font-size: 2rem; color: #ce1126;
            letter-spacing: .02em; text-transform: uppercase;
            display: block; line-height: 1.1; margin-bottom: 3px;
        }
        .college-affiliation {
            font-size: .82rem; color: #000; font-weight: 700;
            display: block; white-space: nowrap;
        }

        /* ── Welcome / page header ─────────────────────── */
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
        /* decorative bubble */
        .page-header::before {
            content:''; position:absolute; top:-60px; right:-60px;
            width:200px; height:200px;
            background: radial-gradient(circle, rgba(255,255,255,.12) 0%, transparent 65%);
            border-radius:50%; pointer-events:none;
        }
        /* shimmer sweep */
        .page-header::after {
            content:''; position:absolute; top:0; left:0;
            width:60px; height:100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.18), transparent);
            animation: shimmer 3.5s ease-in-out infinite;
            pointer-events:none;
        }

        .page-title-text {
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: -.02em;
            line-height: 1.15;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 2;
        }
        .page-title-text span {
            display: block; font-size: .82rem; font-weight: 500;
            opacity: .85; margin-top: 4px; letter-spacing: .01em;
        }

        .course-pill {
            display:inline-flex; align-items:center; gap:6px;
            font-size:.68rem; font-weight:700; text-transform:uppercase;
            background: rgba(255,255,255,.18); backdrop-filter:blur(12px);
            padding:5px 12px; border-radius:50px; color:#fff;
            border:1.5px solid rgba(255,255,255,.4);
            box-shadow:0 4px 18px rgba(49,46,129,.2), inset 0 1px 0 rgba(255,255,255,.3);
            animation: pulse 3s ease-in-out infinite;
            flex-shrink:0; position:relative; z-index:1;
        }
        .course-pill .cp-icon {
            width:24px; height:24px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:.75rem;
            background-image: linear-gradient(rgba(255,255,255,.1),rgba(255,255,255,.1)),
                              linear-gradient(135deg,#f97316,#fbbf24);
            background-origin:border-box; background-clip:content-box,border-box;
            border:1.5px solid transparent; color:#fff;
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
            background: #4f46e5;
            padding: 7px;
            border-radius: var(--radius-md);
            box-shadow: 0 20px 40px -12px rgba(79,70,229,.22);
            animation: fadeUp .4s ease-out both;
        }
        .qb-table-wrap {
            background: #fff;
            border-radius: 9px;
            overflow-y: auto;
            max-height: 460px;
            border: 1px solid rgba(0,0,0,.08);
        }
        .qb-table {
            width: 100%;
            border-collapse: collapse;
        }
        /* sticky thead */
        .qb-table thead th {
            position: sticky; top: 0; z-index: 10;
            background: var(--teal);
            color: #fff !important;
            font-weight: 700;
            font-size: .82rem;
            padding: 11px 12px;
            border: 1px solid rgba(255,255,255,.15);
            text-transform: capitalize;
            white-space: nowrap;
            letter-spacing: .02em;
        }
        /* subject group row */
        .qb-table .row-subject {
            background: rgba(79,70,229,.07);
        }
        .qb-table .row-subject td {
            padding: 7px 12px;
            font-size: .74rem;
            font-weight: 700;
            color: var(--primary-dk);
            text-transform: uppercase;
            letter-spacing: .06em;
            border-bottom: 1px solid var(--border);
        }
        .qb-table .row-subject td i { margin-right: 6px; color: var(--teal); }
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
        .qb-table .col-no   { width: 6%;  text-align: center; font-weight: 600; color: var(--muted); }
        .qb-table .col-name { width: 50%; font-weight: 600; }
        .qb-table .col-subj { width: 30%; color: var(--muted); }
        .qb-table .col-act  { width: 14%; text-align: right; }

        /* download btn */
        .dl-btn {
            display: inline-flex; align-items: center; gap: 5px;
            background: var(--primary); color: #fff !important;
            padding: 6px 14px; border-radius: 50px;
            font-size: .73rem; font-weight: 700;
            text-decoration: none !important; white-space: nowrap;
            box-shadow: 0 4px 12px rgba(79,70,229,.28);
            transition: all .22s ease;
        }
        .dl-btn:hover { background: var(--primary-dk); transform: scale(1.04); box-shadow: 0 6px 18px rgba(79,70,229,.38); }
        .dl-btn i { font-size: .68rem; }

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

        /* ── Responsive ────────────────────────────────── */
        @media (max-width: 480px) {
            #qbPage { margin: 5px auto; padding: 0 0 60px; }
            .college-branding { padding: 10px !important; border-radius: 16px 16px 0 0 !important; }
            .college-name { font-size: 1.1rem !important; }
            .college-affiliation { font-size: .63rem !important; white-space: normal !important; }
            .page-header { border-radius: 0 0 24px 24px !important; margin-bottom: 14px !important; }
            .course-pill { display: none; }
            .sem-btn { padding: 8px 14px; font-size: .78rem; }
            .qb-table thead th, .qb-table tbody td { padding: 8px 8px; font-size: .72rem; }
            .dl-btn { padding: 5px 10px; font-size: .68rem; }
            #qbPage > .sem-selector { padding: 0 10px; }
            .tab-panel { padding: 0 10px; }
            .qb-table .col-subj { display: none; }
        }
        @media (min-width: 481px) and (max-width: 768px) {
            .course-pill { display: none; }
            .qb-table thead th, .qb-table tbody td { padding: 9px 10px; font-size: .78rem; }
        }
        @media (min-width: 769px) {
            .qb-table thead th, .qb-table tbody td { padding: 12px 16px; font-size: .86rem; }
            .dl-btn { font-size: .78rem; padding: 7px 16px; }
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
            University Question Papers
            <span>Download previous year university examination papers</span>
        </div>
        <div class="course-pill">
            <span class="cp-icon"><i class="fa fa-university"></i></span>
            <span>
                <?php echo htmlspecialchars($_SESSION['course']['course_name'] ?? ''); ?>
            </span>
        </div>
    </div>

    <!-- ── Semester / Year selector ── -->
    <div class="sem-selector" id="semSelector">
        <?php foreach ($tabs as $idx => $tab): ?>
            <button
                class="sem-btn <?= $idx === 0 ? 'active' : '' ?>"
                data-tab="tab<?= $idx ?>"
                id="semBtn<?= $idx ?>">
                <?= htmlspecialchars($tab['label']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- ── Tab Panels ── -->
    <?php foreach ($tabs as $idx => $tab):
        $rows = $tab['rows'];

        // Group by subject
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['subject_name']][] = $r;
        }

        // Running serial counter
        $serial = 0;
    ?>
    <div class="tab-panel <?= $idx === 0 ? 'active' : '' ?>" id="tab<?= $idx ?>">

        <?php if (empty($rows)): ?>
            <div class="empty-state">
                <i class="fa fa-folder-open"></i>
                <p>No university question papers available for this <?= ($cMode=='s'||$cMode=='S'||$cMode=='N') ? 'semester' : 'year' ?> yet.<br>Check back later!</p>
            </div>
        <?php else: ?>

            <div class="qb-table-frame">
                <div class="qb-table-wrap">
                    <table class="qb-table">
                        <thead>
                            <tr>
                                <th class="col-no">#</th>
                                <th class="col-name">Paper Name</th>
                                <th class="col-subj">Subject</th>
                                <th class="col-act">Download</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($grouped as $subjectName => $papers): ?>
                            <!-- Subject group row -->
                            <tr class="row-subject">
                                <td colspan="4">
                                    <i class="fa fa-book-open"></i>
                                    <?= htmlspecialchars($subjectName) ?>
                                </td>
                            </tr>
                            <?php foreach ($papers as $r):
                                $serial++;
                            ?>
                            <tr class="qb-row">
                                <td class="col-no"><?= $serial ?></td>
                                <td class="col-name"><?= htmlspecialchars($r['questionbank_name']) ?></td>
                                <td class="col-subj"><?= htmlspecialchars($r['subject_name']) ?></td>
                                <td class="col-act">
                                    <a href="<?= htmlspecialchars($r['questionbank_file']) ?>"
                                       class="dl-btn" download>
                                        <i class="fa fa-download"></i> Download
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>

</div>

<script>
(function () {
    'use strict';
    var btns   = document.querySelectorAll('.sem-btn');
    var panels = document.querySelectorAll('.tab-panel');

    btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            btns.forEach(function (b) { b.classList.remove('active'); });
            panels.forEach(function (p) { p.classList.remove('active'); });
            btn.classList.add('active');
            var target = document.getElementById(btn.dataset.tab);
            if (target) { target.classList.add('active'); }
        });
    });
})();
</script>

<?php include("footer.php"); ?>
</body>
</html>