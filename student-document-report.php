<?php
require_once('script/Config.php');

// Handle Excel Export
if (isset($_POST['export_excel'])) {
    // 1. Fetch all document types to create columns
    $docTypesQuery = "SELECT type_id, type_name FROM student_document_type_master WHERE type_status = 'active' ORDER BY type_id";
    $docTypesRes = mysqli_query($con, $docTypesQuery);
    
    if (!$docTypesRes || mysqli_num_rows($docTypesRes) == 0) {
        $docTypesQuery = "SELECT type_id, type_name FROM document_type_master WHERE type_status = 'active' ORDER BY type_id";
        $docTypesRes = mysqli_query($con, $docTypesQuery);
    }

    $docTypes = [];
    $headers = ['Sl', 'Session', 'Course', 'Sem', 'Student Name', 'Roll No', 'KU Roll No'];
    if ($docTypesRes) {
        while ($dt = mysqli_fetch_assoc($docTypesRes)) {
            $docTypes[$dt['type_id']] = $dt['type_name'];
            $headers[] = $dt['type_name'];
        }
    }

    // 2. Fetch all students with session and course names
    $query = "SELECT s.s_id, s.s_roll_no, s.s_ku_roll_no, s.s_name, sm.session_name, cm.course_name, s.s_cur_sem
              FROM student s
              LEFT JOIN session_master sm ON s.s_session_id = sm.session_master_id
              LEFT JOIN course_master cm ON s.s_course_id = cm.course_master_id
              ORDER BY sm.session_name DESC, cm.course_name ASC, s.s_cur_sem ASC, s.s_roll_no ASC";
    
    $res = mysqli_query($con, $query);
    
    if (!$res) {
        die("Query failed: " . mysqli_error($con));
    }

    // Pre-fetch all document details to avoid N+1 queries
    $studentDocsMap = [];
    $sdQuery = "SELECT student_roll, document_type FROM student_document_details";
    $sdRes = mysqli_query($con, $sdQuery);
    if ($sdRes) {
        while ($sd = mysqli_fetch_assoc($sdRes)) {
            $studentDocsMap[$sd['student_roll']][] = $sd['document_type'];
        }
    }

    // Switch to Excel HTML format for colors and formatting
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=Comprehensive_Document_Report_' . date('Y-m-d') . '.xls');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><style>
            .header { background-color: #007bff; color: #ffffff; font-weight: bold; border: 1px solid #000; }
            .uploaded { background-color: #c3e6cb; color: #155724; text-align: center; border: 1px solid #000; }
            .not-uploaded { background-color: #f5c6cb; color: #721c24; text-align: center; border: 1px solid #000; }
            td, th { border: 1px solid #dee2e6; padding: 5px; }
          </style></head><body>';
    echo '<table>';
    
    // Header Row
    echo '<tr>';
    foreach ($headers as $header) {
        echo '<th class="header">' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr>';
    
    $index = 1;
    while ($row = mysqli_fetch_assoc($res)) {
        $roll = $row['s_roll_no'];
        echo '<tr>';
        echo '<td>' . $index++ . '</td>';
        echo '<td>' . htmlspecialchars($row['session_name'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($row['course_name'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($row['s_cur_sem'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($row['s_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['s_roll_no']) . '</td>';
        echo '<td>' . htmlspecialchars($row['s_ku_roll_no'] ?? 'N/A') . '</td>';

        $uploadedDocs = $studentDocsMap[$roll] ?? [];

        foreach ($docTypes as $tid => $tname) {
            $isUploaded = in_array($tid, $uploadedDocs);
            $class = $isUploaded ? 'uploaded' : 'not-uploaded';
            $text = $isUploaded ? 'Uploaded' : 'NOT Uploaded';
            echo '<td class="' . $class . '">' . $text . '</td>';
        }
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

if ($giit->check_admin_access()) {

	define("TITLE", "Student Documents Report");
	include_once('pages/header.php');
?>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
        }
        .report-page-wrapper {
            min-height: calc(100vh - 120px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            font-family: 'Outfit', sans-serif;
        }
        .report-card {
            background: white;
            padding: 60px 40px;
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            text-align: center;
            max-width: 800px;
            width: 100%;
            border: 1px solid rgba(226, 232, 240, 0.8);
            position: relative;
            overflow: hidden;
        }
        .report-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, #10b981, #3b82f6);
        }
        .icon-box {
            width: 100px;
            height: 100px;
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 30px;
            transform: rotate(-5deg);
            transition: all 0.5s ease;
        }
        .report-card:hover .icon-box {
            transform: rotate(0deg) scale(1.1);
        }
        .page-heading {
            font-size: 2.8rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 15px;
            letter-spacing: -0.02em;
        }
        .page-subheading {
            font-size: 1.15rem;
            color: #64748b;
            margin-bottom: 45px;
            line-height: 1.6;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .download-btn-primary {
            background: #10b981;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 20px 50px;
            font-size: 1.3rem;
            font-weight: 700;
            border-radius: 100px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: inline-flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 15px 30px rgba(16, 185, 129, 0.35);
            text-decoration: none;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        .download-btn-primary:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.45);
            color: white;
            text-decoration: none;
        }
        .download-btn-primary:active {
            transform: translateY(0) scale(0.98);
        }
        .report-meta {
            margin-top: 50px;
            padding-top: 40px;
            border-top: 2px solid #f1f5f9;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .meta-box {
            text-align: center;
        }
        .meta-label {
            display: block;
            font-size: 0.75rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .meta-value {
            font-size: 1rem;
            font-weight: 600;
            color: #334155;
        }
        @media (max-width: 640px) {
            .page-heading { font-size: 2rem; }
            .report-meta { grid-template-columns: 1fr; gap: 30px; }
            .report-card { padding: 40px 20px; }
        }
    </style>

    <div class="report-page-wrapper">
        <div class="report-card">
            <div class="icon-box">
                <i class="fas fa-file-invoice"></i>
            </div>
            <h1 class="page-heading">Comprehensive Document Report</h1>
            <p class="page-subheading">
                Instant access to document verification status for the entire student body. Includes detailed records of sessions, courses, semesters, and individual document uploads.
            </p>
            
            <form method="POST">
                <button type="submit" name="export_excel" class="download-btn-primary">
                    <i class="fas fa-cloud-download-alt"></i> Export Master Report
                </button>
            </form>

            <div class="report-meta">
                <div class="meta-box">
                    <span class="meta-label">Output Format</span>
                    <span class="meta-value">Microsoft Excel (.xls)</span>
                </div>
                <div class="meta-box">
                    <span class="meta-label">Report Type</span>
                    <span class="meta-value">Master Status</span>
                </div>
                <div class="meta-box">
                    <span class="meta-label">Generation Date</span>
                    <span class="meta-value"><?php echo date('d M, Y'); ?></span>
                </div>
            </div>
        </div>
    </div>

	<?php include_once('pages/footer.php'); ?>
<?php } ?>

