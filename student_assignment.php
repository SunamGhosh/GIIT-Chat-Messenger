<?php
session_start();
if (!isset($_SESSION['s_id'])) {
    header('location:student-login.php');
    exit;
}
require_once("configNew.php");
include("header.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="author" content="Sunam Ghosh --@SunamGhosh">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style type="text/css">
        * { box-sizing: border-box !important; }
        :root {
            --primary: #4f46e5;
            --primary-lt: #818cf8;
            --primary-dk: #312e81;
            --teal: #14b8a6;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
            --radius-md: 16px;
            --radius-lg: 24px;
        }

        body {
            background-color: var(--bg);
            background-image: radial-gradient(at 0% 0%, rgba(79,70,229,0.08) 0px, transparent 50%), 
                              radial-gradient(at 100% 100%, rgba(20,184,166,0.05) 0px, transparent 50%);
            font-family: 'Outfit', sans-serif !important;
            color: var(--text);
            margin: 0; padding: 0;
            overflow-x: hidden;
        }

        .header1, .header2, .header3, #mu-menu { display: none !important; }

        #mainContainer {
            margin: 20px auto;
            max-width: 1000px;
            width: 100%;
            padding: 0 15px 80px;
        }

        /* ── Header Area ───────────────────────────────── */
        .college-branding {
            text-align: center;
            background: #fff;
            padding: 15px;
            border-radius: var(--radius-md) var(--radius-md) 0 0;
            border: 1px solid var(--border);
            border-bottom: none;
        }
        .college-name {
            font-weight: 900; font-size: 1.8rem; color: #ce1126;
            text-transform: uppercase; display: block; line-height: 1;
        }
        .college-affiliation {
            font-size: 0.75rem; color: #000; font-weight: 700;
            text-transform: uppercase; display: block; margin-top: 5px;
        }

        .welcome-header {
            background: linear-gradient(135deg, #1e1b6e 0%, #4f46e5 100%);
            padding: 15px 20px;
            border-radius: 0 0 var(--radius-md) var(--radius-md);
            color: #fff;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 10px 25px -5px rgba(49,46,129,0.2);
            margin-bottom: 25px;
        }
        .welcome-text { font-weight: 800; font-size: 1.1rem; line-height: 1.2; }
        .welcome-text span { display: block; font-size: 0.78rem; font-weight: 400; opacity: 0.8; margin-top: 3px; }
        
        .course-info {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            padding: 6px 14px; border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.3);
            font-size: 0.72rem; font-weight: 700;
        }

        /* ── Custom Tab Selector ───────────────────────── */
        .sem-selector {
            display: flex; gap: 8px; overflow-x: auto;
            padding: 4px 4px 15px; margin-bottom: 5px;
            scrollbar-width: none;
        }
        .sem-selector::-webkit-scrollbar { display: none; }
        
        .sem-btn {
            background: #fff; color: var(--muted);
            border: 1px solid var(--border);
            padding: 10px 22px; border-radius: 50px;
            font-size: 0.85rem; font-weight: 600;
            cursor: pointer; white-space: nowrap;
            transition: all .25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sem-btn.active {
            background: var(--primary); color: #fff;
            border-color: var(--primary);
            box-shadow: 0 8px 20px -6px rgba(79,70,229,0.4);
            transform: translateY(-1px);
        }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; animation: fadeUp .4s ease-out; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

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
            max-height: 500px;
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
        }
        /* subject group row */
        .qb-table .row-subject {
            background: rgba(79,70,229,.07);
        }
        .qb-table .row-subject td {
            padding: 8px 12px;
            font-size: .74rem;
            font-weight: 700;
            color: var(--primary-dk);
            text-transform: uppercase;
            letter-spacing: .06em;
            border-bottom: 1px solid var(--border);
        }
        .qb-table .row-subject td i { margin-right: 6px; color: var(--teal); }
        
        /* data rows */
        .qb-table tbody tr.qb-row { transition: background .18s; }
        .qb-table tbody tr.qb-row:nth-child(even) { background: rgba(245,245,255,.6); }
        .qb-table tbody tr.qb-row:hover { background: rgba(79,70,229,.06); }
        
        .qb-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            font-size: .85rem;
            color: var(--text);
            vertical-align: middle;
        }
        
        /* column widths */
        .col-no   { width: 60px; text-align: center; font-weight: 600; color: var(--muted); }
        .col-name { font-weight: 600; }
        .col-subj { width: 30%; color: var(--muted); }
        .col-act  { width: 140px; text-align: right; }

        /* download btn */
        .dl-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--primary); color: #fff !important;
            padding: 7px 16px; border-radius: 50px;
            font-size: .75rem; font-weight: 700;
            text-decoration: none !important;
            box-shadow: 0 4px 12px rgba(79,70,229,.25);
            transition: all .2s ease;
        }
        .dl-btn:hover { background: var(--primary-dk); transform: scale(1.04); }

        .empty-state {
            text-align: center; padding: 60px 20px;
            background: #fff; border-radius: var(--radius-md);
            border: 2px dashed var(--border);
        }
        .empty-state i { font-size: 3rem; color: var(--border); margin-bottom: 15px; display: block; }
        .empty-state p { color: var(--muted); font-size: 0.9rem; margin: 0; }

        @media (max-width: 480px) {
            #mainContainer { padding: 0 10px 60px; }
            .college-name { font-size: 1.3rem; }
            .college-affiliation { font-size: 0.6rem; }
            .welcome-header { padding: 12px 15px; margin-bottom: 15px; }
            .welcome-text { font-size: 0.95rem; }
            .course-info { display: none; }
            .sem-btn { padding: 8px 16px; font-size: 0.78rem; }
            .qb-table thead th, .qb-table tbody td { padding: 10px; font-size: 0.72rem; }
            .col-subj { display: none; }
            .dl-btn { padding: 6px 12px; font-size: 0.68rem; }
        }
    </style>
</head>
<body>

<div class="container" id="mainContainer">
    <div class="college-branding">
        <span class="college-name">GIIT PROFESSIONAL COLLEGE</span>
        <span class="college-affiliation">Affiliated to KOLHAN UNIVERSITY | Approved by HRD, Govt. of Jharkhand</span>
    </div>

    <div class="welcome-header">
        <div class="welcome-text">
            Assignments
            <span>Download your course assignments and resources</span>
        </div>
        <div class="course-info">
            <i class="fa fa-book"></i>
            <?php echo htmlspecialchars($_SESSION['course']['course_name'] ?? ''); ?>
            (<?php echo htmlspecialchars($_SESSION['course']['university_short_name'] ?? ''); ?>)
        </div>
    </div>

    <?php
    $course_id = $_SESSION['s_course_id'] ?? ($_REQUEST['course'] ?? 0);
    $qMode = "SELECT course_mode FROM course_master WHERE course_master_id='$course_id'";
    $rMode = mysqli_query($con, $qMode);
    $rowM = mysqli_fetch_array($rMode);
    $cMode = $rowM['course_mode'] ?? 'N';
    $max = ($cMode == 's' || $cMode == 'S') ? 6 : 3;
    $typeLabel = ($cMode == 's' || $cMode == 'S') ? "Semester" : "Year";

    $category_id = 2; // Assignments
    $tabs = [];
    for ($i = 1; $i <= $max; $i++) {
        $label = "$typeLabel $i";
        $sql = "SELECT qb.*, sub.subject_name 
                FROM question_bank qb
                JOIN subject_master sub ON sub.subject_master_id = qb.questionbank_subject
                WHERE qb.questionbank_course_id = '$course_id' 
                  AND qb.questionbank_semester = $i 
                  AND qb.questionbank_status = 'active'
                  AND qb.questionbank_category_id = '$category_id'
                ORDER BY qb.questionbank_id DESC";
        $res = mysqli_query($con, $sql);
        $rows = [];
        while($r = mysqli_fetch_array($res)) {
            $rows[] = $r;
        }
        $tabs[] = ['label' => $label, 'rows' => $rows];
    }
    ?>

    <div class="sem-selector">
        <?php foreach ($tabs as $idx => $tab): ?>
            <button class="sem-btn <?= $idx === 0 ? 'active' : '' ?>" data-tab="tab<?= $idx ?>">
                <?= htmlspecialchars($tab['label']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <?php foreach ($tabs as $idx => $tab): 
        $rows = $tab['rows'];
        $grouped = [];
        foreach ($rows as $r) { $grouped[$r['subject_name']][] = $r; }
        $serial = 0;
    ?>
    <div class="tab-panel <?= $idx === 0 ? 'active' : '' ?>" id="tab<?= $idx ?>">
        <?php if (empty($rows)): ?>
            <div class="empty-state">
                <i class="fa fa-folder-open"></i>
                <p>No assignments available for this <?= strtolower($typeLabel) ?> yet.</p>
            </div>
        <?php else: ?>
            <div class="qb-table-frame">
                <div class="qb-table-wrap">
                    <table class="qb-table">
                        <thead>
                            <tr>
                                <th class="col-no">#</th>
                                <th class="col-name">Assignment Name</th>
                                <th class="col-subj">Subject</th>
                                <th class="col-act">Download</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($grouped as $subjectName => $items): ?>
                            <tr class="row-subject">
                                <td colspan="4">
                                    <i class="fa fa-book-open"></i>
                                    <?= htmlspecialchars($subjectName) ?>
                                </td>
                            </tr>
                            <?php foreach ($items as $r): $serial++; ?>
                            <tr class="qb-row">
                                <td class="col-no"><?= $serial ?></td>
                                <td class="col-name"><?= htmlspecialchars($r['questionbank_name']) ?></td>
                                <td class="col-subj"><?= htmlspecialchars($r['subject_name']) ?></td>
                                <td class="col-act">
                                    <a href="<?= htmlspecialchars($r['questionbank_file']) ?>" class="dl-btn" download>
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