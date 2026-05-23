<?php
session_start();
if(!isset($_SESSION['s_id'])){
    header('location:student-login.php');
    exit;
}
include("header.php");
?>
<meta name="author" content="Sunam Ghosh --@SunamGhosh">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style type="text/css">
    :root {
        --primary: #6366f1;
        --indigo: #4f46e5;
        --bg-main: #fcfdff;
        --text: #1e293b;
        --muted: #64748b;
        --radius-lg: 24px;
        --radius-md: 18px;
        --shadow-soft: 0 4px 20px -4px rgba(0,0,0,0.05);
    }

    body {
        background-color: var(--bg-main) !important;
        background-image: 
            radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.05) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(20, 184, 166, 0.05) 0px, transparent 50%) !important;
        font-family: 'Outfit', sans-serif !important;
        color: var(--text);
        padding-top: 0 !important;
    }

    .header1, .header2, .header3, #mu-menu { display: none !important; }

    #mainContainer {
        max-width: 1000px;
        margin: 0 auto;
        padding: 0 10px 60px;
    }

    /* ── Sticky Header Wrap ── */
    .sticky-header {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: var(--bg-main);
        padding-top: 10px;
        margin-bottom: 20px;
    }

    /* ── Branding ── */
    .college-branding {
        text-align: center; padding: 12px; background: #fff;
        border-radius: var(--radius-md) var(--radius-md) 0 0;
        border: 1px solid rgba(0,0,0,0.03);
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .college-name { font-weight: 800; font-size: 1.3rem; color: #ce1126; text-transform: uppercase; letter-spacing: 0.5px; }
    .college-affiliation { font-size: 0.65rem; color: var(--muted); font-weight: 700; display: block; }

    .page-header {
        background: linear-gradient(135deg, #1e1b6e 0%, #312e81 100%);
        padding: 16px 24px !important;
        border-radius: 0 0 var(--radius-md) var(--radius-md);
        color: #fff !important; margin-top: -1px;
        box-shadow: 0 10px 25px -5px rgba(30, 27, 110, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    .ph-title { font-weight: 800; font-size: 1.2rem; line-height: 1.2; z-index: 2; }
    .ph-course-name { 
        font-weight: 900; font-size: 1.2rem; text-transform: uppercase; 
        letter-spacing: 1px; z-index: 2; opacity: 0.95; 
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* ── Colored Grid ── */
    .course-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        animation: fadeUp 0.6s ease-out;
    }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .sem-card {
        border-radius: var(--radius-md);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        border: 1.5px solid rgba(255,255,255,0.8);
        box-shadow: var(--shadow-soft);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #fff;
    }
    .sem-card:hover { transform: translateY(-4px); box-shadow: 0 15px 30px -10px rgba(0,0,0,0.08); }

    .sem-card-header {
        padding: 12px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(255,255,255,0.5);
        border-bottom: 1px solid rgba(0,0,0,0.04);
    }
    .sem-card-header h2 {
        margin: 0; font-size: 0.9rem; font-weight: 800;
        display: flex; align-items: center; gap: 8px;
    }
    .paper-count { font-size: 0.65rem; font-weight: 800; opacity: 0.6; }

    /* ── Dynamic Colors ── */
    .sem-c1 { background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%); } .sem-c1 h2 { color: #2563eb; }
    .sem-c2 { background: linear-gradient(180deg, #ecfdf5 0%, #ffffff 100%); } .sem-c2 h2 { color: #059669; }
    .sem-c3 { background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%); } .sem-c3 h2 { color: #d97706; }
    .sem-c4 { background: linear-gradient(180deg, #fef2f2 0%, #ffffff 100%); } .sem-c4 h2 { color: #dc2626; }
    .sem-c5 { background: linear-gradient(180deg, #f5f3ff 0%, #ffffff 100%); } .sem-c5 h2 { color: #7c3aed; }
    .sem-c6 { background: linear-gradient(180deg, #f0f9ff 0%, #ffffff 100%); } .sem-c6 h2 { color: #0284c7; }

    .paper-list { padding: 4px 0; }
    .paper-item {
        padding: 10px 14px;
        border-bottom: 1px solid rgba(0,0,0,0.03);
        display: flex;
        flex-direction: column;
        gap: 3px;
    }
    .paper-item:last-child { border-bottom: none; }
    .paper-item:hover { background: rgba(255,255,255,0.8); }

    .paper-header { display: flex; justify-content: space-between; align-items: center; }
    .paper-code { font-weight: 900; font-size: 0.6rem; letter-spacing: 0.5px; opacity: 0.7; }
    .paper-credit { font-weight: 800; font-size: 0.55rem; padding: 1px 6px; border-radius: 4px; background: rgba(0,0,0,0.04); color: var(--muted); }

    .paper-name {
        font-weight: 700; font-size: 0.8rem; color: var(--text);
        line-height: 1.3;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .paper-type { font-size: 0.62rem; color: var(--muted); font-weight: 600; display: block; }

    @media (min-width: 768px) {
        .course-grid { grid-template-columns: repeat(3, 1fr); gap: 18px; }
        .sem-card-header h2 { font-size: 1rem; }
        .paper-name { font-size: 0.85rem; }
    }
    @media (max-width: 480px) {
        .ph-title, .ph-course-name { font-size: 1rem !important; }
    }
</style>

<div class="container" id="mainContainer">

    <div class="sticky-header">
        <!-- ── College Branding ── -->
        <div class="college-branding">
            <span class="college-name">GIIT PROFESSIONAL COLLEGE</span>
            <span class="college-affiliation">Academic Curriculum | Dynamic Map</span>
        </div>

        <!-- ── Page Header ── -->
        <div class="page-header">
            <div class="ph-title">Course Structure</div>
            <div class="ph-course-name">
                <?php echo htmlspecialchars($_SESSION['course']['course_name'] ?? ''); ?>
            </div>
        </div>
    </div>

    <div class="course-grid">
        <?php
        $course_id = $_SESSION['s_course_id'] ?? 0;
        
        $qMode = "SELECT course_mode FROM course_master WHERE course_master_id='$course_id'";
        $rMode = mysqli_query($con, $qMode);
        $rowM = mysqli_fetch_array($rMode);
        $cMode = $rowM['course_mode'] ?? 's';
        $maxSem = (strtolower($cMode) == 's') ? 6 : 3;
        $semLabel = (strtolower($cMode) == 's') ? "Sem" : "Year";

        $query = "SELECT * FROM `subject_master` 
                  WHERE `subject_course_id` = '$course_id' AND `subject_status` = 'active' 
                  ORDER BY `subject_sem_id` ASC, `subject_university_code` ASC";
        $res = mysqli_query($con, $query);
        
        $subjectsBySem = [];
        while($row = mysqli_fetch_assoc($res)) {
            $subjectsBySem[$row['subject_sem_id']][] = $row;
        }

        $colorClasses = ['sem-c1', 'sem-c2', 'sem-c3', 'sem-c4', 'sem-c5', 'sem-c6'];

        if(!empty($subjectsBySem)) {
            for($s = 1; $s <= $maxSem; $s++) {
                if(isset($subjectsBySem[$s])) {
                    $semSubjects = $subjectsBySem[$s];
                    $count = count($semSubjects);
                    $colorClass = $colorClasses[($s-1) % 6];
                    ?>
                    <div class="sem-card <?= $colorClass ?>">
                        <div class="sem-card-header">
                            <h2>
                                <i class="fa fa-bookmark"></i>
                                <?= $semLabel ?> <?= $s ?>
                            </h2>
                            <span class="paper-count"><?= $count ?> Papers</span>
                        </div>
                        <div class="paper-list">
                            <?php foreach($semSubjects as $sub): ?>
                                <div class="paper-item">
                                    <div class="paper-header">
                                        <span class="paper-code"><?= htmlspecialchars($sub['subject_university_code']) ?></span>
                                        <span class="paper-credit"><?= htmlspecialchars($sub['subject_sub_shortname']) ?> CR</span>
                                    </div>
                                    <span class="paper-name" title="<?= htmlspecialchars($sub['subject_name']) ?>">
                                        <?= htmlspecialchars($sub['subject_name']) ?>
                                    </span>
                                    <span class="paper-type"><?= htmlspecialchars($sub['subject_short_name']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php
                }
            }
        }
        ?>
    </div>

</div>

<?php include("footer.php"); ?>