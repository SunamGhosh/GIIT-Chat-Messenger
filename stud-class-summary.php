<?php
session_start();
if (!isset($_SESSION['s_id'])) {
    header('location:student-login.php');
    exit();
}
require_once("configNew.php");
include("header.php");

if (isset($_SESSION['s_id'])) {
    $query = "SELECT * FROM student WHERE s_id = " . $_SESSION['s_id'];
    $row = mysqli_query($con, $query);
    $student = mysqli_fetch_array($row);
    $s_course = $student['s_course_id'];
    $s_sem = $student['s_cur_sem'];
    $s_session = $student['s_session_id'];

    $cur_date_1 = date("Y-m-d", time());
    $NewDate = Date('Y-m-d', strtotime("-30 days"));
}
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
            max-width: 1100px;
            width: 100%;
            padding: 0 15px 80px;
        }

        /* ── Header Area ───────────────────────────────── */
        .college-branding {
            text-align: center;
            background: #fff;
            padding: 12px 15px;
            border-radius: var(--radius-md) var(--radius-md) 0 0;
            border: 1px solid var(--border);
            border-bottom: none;
        }
        .college-name {
            font-weight: 900; font-size: 1.85rem; color: #ce1126;
            text-transform: uppercase; display: block; line-height: 1.05;
        }
        .college-affiliation {
            font-size: 0.75rem; color: #000; font-weight: 700;
            text-transform: uppercase; display: block; margin-top: 4px;
        }

        .welcome-header {
            background: linear-gradient(135deg, #1e1b6e 0%, #4f46e5 100%);
            padding: 15px 22px;
            border-radius: 0 0 var(--radius-md) var(--radius-md);
            color: #fff;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 10px 25px -5px rgba(49,46,129,0.25);
            margin-bottom: 25px;
            position: relative; overflow: hidden;
        }
        .welcome-header::after {
            content:''; position:absolute; top:0; left:0; width:100%; height:100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            transform: skewX(-20deg);
        }

        .welcome-text { font-weight: 800; font-size: 1.2rem; line-height: 1.2; z-index: 1; }
        .welcome-text span { display: block; font-size: 0.8rem; font-weight: 400; opacity: 0.85; margin-top: 4px; }
        
        .class-counter {
            background: rgba(255,255,255,0.18);
            backdrop-filter: blur(10px);
            padding: 8px 18px; border-radius: 50px;
            border: 1.5px solid rgba(255,255,255,0.4);
            font-size: 0.8rem; font-weight: 700;
            color: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            z-index: 1;
        }

        /* ── Filter Card ───────────────────────────────── */
        .filter-card {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-label { font-size: 0.65rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .form-control-custom {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            font-family: 'Outfit', sans-serif;
            font-size: 0.85rem;
            color: var(--text);
            transition: all 0.2s;
            background: #fff;
        }
        .form-control-custom:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }
        
        .btn-fetch {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 11px 25px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 12px rgba(79,70,229,0.25);
        }
        .btn-fetch:hover { background: var(--primary-dk); transform: translateY(-1px); box-shadow: 0 6px 18px rgba(79,70,229,0.35); }

        /* ── Table Area ────────────────────────────────── */
        .qb-table-frame {
            background: #4f46e5;
            padding: 8px;
            border-radius: 20px;
            box-shadow: 0 20px 40px -12px rgba(79,70,229,0.25);
        }
        .qb-table-wrap {
            background: #fff;
            border-radius: 12px;
            overflow-x: auto;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .qb-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        .qb-table thead th {
            background: #14b8a6;
            color: #fff !important;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 14px 16px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .qb-table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 0.85rem;
            color: var(--text);
            vertical-align: middle;
        }
        .qb-table tbody tr:nth-child(even) { background: rgba(245,245,255,0.6); }
        .qb-table tbody tr:hover { background: rgba(79,70,229,0.05); }

        .col-sl { width: 60px; text-align: center; font-weight: 700; color: var(--muted); }
        .col-date { width: 120px; font-weight: 600; color: var(--primary); }
        .col-subject { width: 200px; font-weight: 700; }
        .col-chapter { width: 250px; color: var(--muted); }
        .col-coverage { font-size: 0.82rem; line-height: 1.4; }

        @media (max-width: 768px) {
            .filter-grid { grid-template-columns: 1fr 1fr; }
            .btn-fetch { grid-column: span 2; }
        }

        @media (max-width: 480px) {
            #mainContainer { padding: 0 6px 60px; }
            .college-name { font-size: 1.1rem; }
            .college-affiliation { font-size: 0.6rem; }
            
            .welcome-header { padding: 10px 14px; margin-bottom: 15px; flex-direction: row !important; align-items: center !important; justify-content: space-between !important; }
            .welcome-text { font-size: 0.8rem; }
            .welcome-text span { font-size: 0.6rem; }
            .class-counter { padding: 4px 10px; font-size: 0.65rem; }

            .filter-card { padding: 10px 8px; margin-bottom: 15px; }
            .filter-grid { 
                grid-template-columns: repeat(5, 1fr) !important; 
                gap: 5px !important; 
                align-items: flex-end;
            }
            .filter-label { font-size: 0.5rem; }
            .form-control-custom { padding: 6px 4px; font-size: 0.65rem; border-radius: 6px; }
            .btn-fetch { 
                grid-column: span 1 !important; 
                padding: 6px 4px; 
                font-size: 0.6rem; 
                border-radius: 6px;
                white-space: nowrap;
            }
            .btn-fetch i { display: none; } /* Hide icon to save space on mobile */

            .qb-table thead th, .qb-table tbody td { padding: 8px 6px; font-size: 0.68rem; }
            .col-chapter { display: none; }
            .col-sl { width: 35px; }
            .col-date { width: 75px; }
        }
    </style>
</head>
<body>

<div id="mainContainer">
    <div class="college-branding">
        <span class="college-name">GIIT Professional College</span>
        <span class="college-affiliation">Affiliated to Kolhan University | Approved by HRD, Govt. of Jharkhand</span>
    </div>

    <div class="welcome-header">
        <div class="welcome-text">
            Class Summary
            <span>Monitor your academic progress and daily class coverage</span>
        </div>
        <div class="class-counter">
            <i class="fas fa-chalkboard-teacher"></i> 
            Total Classes: <span class="class_count">0</span>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <div class="filter-grid">
            <div class="filter-group">
                <label class="filter-label">Semester</label>
                <select id="FILTER_SEMESTER_SELECT" class="form-control-custom">
                    <option value="" disabled selected>Select Semester</option>
                    <?php
                    $query = "SELECT * FROM `semester_master` WHERE `semester_status`='active'";
                    $res = mysqli_query($con, $query);
                    while ($row = mysqli_fetch_object($res)) {
                        $selected = ($row->semester_master_id == $s_sem) ? 'selected' : '';
                        echo "<option value='$row->semester_master_id' $selected>$row->semester_name</option>";
                    }
                    echo "<option value='All'>All Semesters</option>";
                    ?>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Subject</label>
                <select id="FILTER_SUBJECT_SELECT" class="form-control-custom">
                    <option value="" disabled selected>Select Subject</option>
                    <?php
                    $query = "SELECT * FROM `subject_master` WHERE `subject_status`='ACTIVE'";
                    $res = mysqli_query($con, $query);
                    while ($row = mysqli_fetch_object($res)) {
                        echo "<option value='$row->subject_master_id'>$row->subject_name</option>";
                    }
                    echo "<option value='All'>All Subjects</option>";
                    ?>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Start Date</label>
                <input type="date" id="start_date" class="form-control-custom" value="<?php echo $NewDate; ?>">
            </div>
            <div class="filter-group">
                <label class="filter-label">End Date</label>
                <input type="date" id="end_date" class="form-control-custom" value="<?php echo $cur_date_1; ?>">
            </div>
            <button class="btn-fetch" id="filter">
                <i class="fas fa-sync-alt"></i> FETCH DATA
            </button>
        </div>
    </div>

    <!-- Data Table -->
    <div class="qb-table-frame">
        <div class="qb-table-wrap">
            <table class="qb-table">
                <thead>
                    <tr>
                        <th class="col-sl">SL</th>
                        <th class="col-date">Date</th>
                        <th class="col-subject">Subject</th>
                        <th class="col-chapter">Chapter</th>
                        <th class="col-coverage">Class Coverage</th>
                    </tr>
                </thead>
                <tbody id="admin_data">
                    <?php
                    $query = "SELECT qct.*, sm.subject_name, cm.chapter_name
                              FROM class_coverage_transaction qct
                              JOIN subject_master sm ON sm.subject_master_id = qct.subject_id
                              JOIN chapter_master cm ON cm.chapter_master_id = qct.chapter_id
                              WHERE qct.class_date BETWEEN '$NewDate' AND '$cur_date_1'
                              AND qct.session_id='$s_session'
                              AND qct.course_id='$s_course'
                              AND qct.semester_id='$s_sem'
                              ORDER BY qct.class_date DESC";
                    $res = mysqli_query($con, $query);
                    $s = 1;
                    $count = mysqli_num_rows($res);
                    if($count > 0):
                        while ($row = mysqli_fetch_array($res)): ?>
                            <tr>
                                <td class="col-sl"><?php echo $s++; ?></td>
                                <td class="col-date"><?php echo date('d M Y', strtotime($row['class_date'])); ?></td>
                                <td class="col-subject"><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                <td class="col-chapter"><?php echo htmlspecialchars($row['chapter_name']); ?></td>
                                <td class="col-coverage"><?php echo htmlspecialchars($row['course_coverage']); ?></td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 40px; color: var(--muted);">No records found for the selected criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

<script type="text/javascript">
    $(document).ready(function() {
        updateCount();

        function updateCount() {
            var rows = $("#admin_data tr").length;
            // Check if it's the "No records" row
            if ($("#admin_data tr td").length === 1) rows = 0;
            $(".class_count").text(rows);
        }

        // Filter Subject based on Semester
        $("#FILTER_SEMESTER_SELECT").on('change', function () {
            let crs_id = '<?php echo $s_course; ?>';
            let sem_id = $(this).val() || 0;
            $.ajax({
                method: "POST",
                url: "stud-class-summary-response.php",
                data: { CRS_ID: crs_id, SEM_ID: sem_id },
                success: function (data) {
                    $("#FILTER_SUBJECT_SELECT").html(data);
                }
            });
        });

        $(document).on("click", '#filter', function() {
            var course = '<?php echo $s_course; ?>';
            var start_date = $("#start_date").val();
            var end_date = $("#end_date").val();
            var semester = $("#FILTER_SEMESTER_SELECT").val();
            var subject = $("#FILTER_SUBJECT_SELECT").val();
            var session = '<?php echo $s_session; ?>';

            if (!semester || !subject) {
                swal("Wait!", "Please select both Semester and Subject.", "info");
                return;
            }

            $("#filter").html('<i class="fas fa-spinner fa-spin"></i> LOADING...');

            $.ajax({
                url: "stud-class-summary-response.php",
                dataType: "json",
                method: "POST",
                data: {
                    course: course,
                    st_dt: start_date,
                    ed_dt: end_date,
                    sem: semester,
                    sub: subject,
                    sess: session,
                },
            }).done(function (res) {
                $("#filter").html('<i class="fas fa-sync-alt"></i> FETCH DATA');
                var student = '';
                if (res.length == 0) {
                    swal("No Results", "No class summary found for the selected filters.", "error");
                    student = '<tr><td colspan="5" style="text-align:center; padding: 40px; color: var(--muted);">No records found.</td></tr>';
                } else {
                    $.each(res, function (index, value) {
                        student += '<tr>';
                        student += '<td class="col-sl">' + (index + 1) + '</td>';
                        student += '<td class="col-date">' + value.class_date + '</td>';
                        student += '<td class="col-subject">' + value.subject_name + '</td>';
                        student += '<td class="col-chapter">' + value.chapter_name + '</td>';
                        student += '<td class="col-coverage">' + value.course_coverage + '</td>';
                        student += '</tr>';
                    });
                }
                $("#admin_data").html(student);
                updateCount();
            });
        });
    });
</script>

<?php include("footer.php"); ?>
</body>
</html>