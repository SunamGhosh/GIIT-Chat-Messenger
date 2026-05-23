<?php
session_start();
if (!isset($_SESSION['s_id'])) {
    header('location:student-login.php');
    exit();
}
require_once 'includes/configNew.php';
include("header.php");
?>
<meta name="author" content="Sunam Ghosh --@SunamGhosh">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style type="text/css">
    *,
    *::before,
    *::after {
        box-sizing: border-box !important;
    }

    :root {
        --primary: #6366f1;
        --primary-lt: #a5b4fc;
        --primary-dk: #4338ca;
        --teal: #0ea5e9;
        --bg-main: #f0f7ff;
        --card-bg: rgba(255, 255, 255, .95);
        --text: #1e293b;
        --muted: #64748b;
        --border: rgba(99, 102, 241, .12);
        --radius-lg: 22px;
        --radius-md: 14px;
        --shadow-md: 0 4px 18px -2px rgba(99, 102, 241, .10);
        --shadow-lg: 0 12px 32px -4px rgba(99, 102, 241, .15);
    }

    body {
        background-color: var(--bg-main);
        background-image:
            radial-gradient(at 0% 0%, rgba(147, 197, 253, .25) 0px, transparent 55%),
            radial-gradient(at 100% 100%, rgba(196, 181, 253, .18) 0px, transparent 55%),
            radial-gradient(at 50% 0%, rgba(186, 230, 253, .15) 0px, transparent 40%);
        font-family: 'Outfit', sans-serif !important;
        color: var(--text);
        -webkit-font-smoothing: antialiased;
        overflow-x: hidden;
    }

    .header1,
    .header2,
    .header3,
    #mu-menu {
        display: none !important;
    }

    body {
        padding-top: 0 !important;
    }

    #ttPage {
        max-width: 960px;
        margin: 14px auto;
        padding: 0 10px 60px;
    }

    /* ── Animations ─────────────────────────────── */
    @keyframes shimmer {
        0% {
            transform: translateX(-100%) skewX(-15deg);
        }

        100% {
            transform: translateX(400%) skewX(-15deg);
        }
    }

    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(14px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ── College Branding ────────────────────────── */
    .college-branding {
        text-align: center;
        background: #fff;
        padding: 12px 15px;
        border-radius: var(--radius-md) var(--radius-md) 0 0;
        border: 1px solid rgba(0, 0, 0, .05);
        border-bottom: none;
        animation: fadeUp .8s ease-out;
    }

    .college-name {
        font-weight: 800;
        font-size: 2rem;
        color: #ce1126;
        letter-spacing: .02em;
        text-transform: uppercase;
        display: block;
        line-height: 1.1;
        margin-bottom: 4px;
    }

    .college-affiliation {
        font-size: .85rem;
        color: #000;
        font-weight: 700;
        display: block;
        white-space: nowrap;
        letter-spacing: .01em;
    }

    /* ── Page Header ─────────────────────────────── */
    @keyframes coursePulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(255, 255, 255, .3), 0 4px 15px rgba(0, 0, 0, .15);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(255, 255, 255, .08), 0 4px 20px rgba(0, 0, 0, .2);
        }
    }

    @keyframes badgeFadeIn {
        from {
            opacity: 0;
            transform: scale(.8) translateY(4px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .page-header {
        background: linear-gradient(135deg, #1e1b6e 0%, #312e81 35%, #4f46e5 70%, #818cf8 100%);
        padding: 22px 26px !important;
        border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        color: #fff !important;
        margin-top: -1px;
        margin-bottom: 10px !important;
        box-shadow: 0 20px 40px -8px rgba(49, 46, 129, .45), 0 0 0 1px rgba(255, 255, 255, .08) inset;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

    /* Decorative bubble top-right */
    .page-header::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(255, 255, 255, .12) 0%, transparent 65%);
        border-radius: 50%;
        pointer-events: none;
    }

    /* Shimmer sweep */
    .page-header::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 60px;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .18), transparent);
        animation: shimmer 3.5s ease-in-out infinite;
        pointer-events: none;
    }

    .ph-title {
        font-weight: 800;
        font-size: 1.45rem;
        letter-spacing: -.025em;
        text-shadow: 0 2px 8px rgba(0, 0, 0, .15);
        line-height: 1.2;
        z-index: 2;
        color: #fff;
    }

    .ph-title span {
        display: block;
        font-size: .82rem;
        font-weight: 400;
        opacity: .82;
        margin-top: 5px;
        letter-spacing: .01em;
        color: #fff;
    }

    /* Animated glassmorphism course pill — dark banner */
    .course-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(255, 255, 255, .18);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        padding: 8px 18px 8px 14px;
        border-radius: 50px;
        border: 1.5px solid rgba(255, 255, 255, .4);
        box-shadow: 0 4px 18px rgba(49, 46, 129, .25), inset 0 1px 0 rgba(255, 255, 255, .3);
        font-size: .75rem;
        font-weight: 700;
        color: #fff;
        text-transform: uppercase;
        z-index: 2;
        flex-shrink: 0;
        animation: coursePulse 3s ease-in-out infinite, badgeFadeIn .6s ease-out both;
        letter-spacing: .02em;
        white-space: nowrap;
    }

    .course-pill .cp-icon {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .8rem;
        flex-shrink: 0;
        color: #fff !important;
        border: 1.5px solid transparent;
        background-image: linear-gradient(rgba(255, 255, 255, .2), rgba(255, 255, 255, .2)), linear-gradient(135deg, #f97316, #fbbf24);
        background-origin: border-box;
        background-clip: content-box, border-box;
    }

    /* ── Filter Row ──────────────────────────────── */
    .filter-row {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        gap: 8px;
        align-items: center;
        background: rgba(255, 255, 255, .92);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        padding: 11px 14px;
        margin-bottom: 10px;
        box-shadow: 0 4px 18px -2px rgba(79, 70, 229, .12), inset 0 1px 0 rgba(255, 255, 255, .8);
        animation: fadeUp .5s ease-out both;
        overflow: hidden;
    }

    .filter-label {
        font-size: .62rem;
        font-weight: 800;
        color: var(--primary);
        text-transform: uppercase;
        white-space: nowrap;
        flex-shrink: 0;
        background: rgba(79, 70, 229, .08);
        padding: 5px 9px;
        border-radius: 7px;
        border: 1px solid var(--border);
        letter-spacing: .04em;
    }

    .filter-input {
        padding: 8px 10px;
        border-radius: 9px;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        font-family: 'Outfit', sans-serif;
        font-size: .78rem;
        font-weight: 600;
        transition: all .2s;
        outline: none;
        flex: 1;
        min-width: 0;
        width: 0;
    }

    .filter-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .09);
    }

    .filter-divider {
        color: var(--muted);
        font-size: .85rem;
        flex-shrink: 0;
        line-height: 1;
    }

    .btn-fetch {
        background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
        color: #fff;
        border: none;
        padding: 9px 18px;
        border-radius: 9px;
        font-weight: 700;
        font-size: .8rem;
        cursor: pointer;
        transition: all .22s;
        box-shadow: 0 4px 14px rgba(99, 102, 241, .25);
        white-space: nowrap;
        flex-shrink: 0;
        letter-spacing: .02em;
    }

    .btn-fetch:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, .32);
    }

    /* ── Table Frame ─── */
    /* ── Table Frame Simplified ─── */
    .tt-frame {
        background: #fff;
        padding: 0;
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-md);
        animation: fadeUp .4s ease-out both;
        margin-bottom: 25px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .tt-wrap {
        background: #fff;
        overflow-y: auto;
        max-height: 520px;
    }

    .tt-table {
        width: 100%;
        border-collapse: collapse;
    }

    /* sticky thead — Simple solid style */
    .tt-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8fafc;
        color: #475569 !important;
        font-weight: 700;
        font-size: .75rem;
        padding: 12px 14px;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        text-transform: uppercase;
        white-space: nowrap;
        letter-spacing: .05em;
    }

    .tt-table thead th:last-child {
        text-align: right;
        border-right: none;
    }

    /* day group header — Soft solid yellow */
    .tt-table .row-day td {
        background: #fffbeb;
        padding: 10px 14px;
        font-size: .75rem;
        font-weight: 700;
        color: #92400e;
        text-transform: uppercase;
        letter-spacing: .05em;
        border-bottom: 1px solid #fde68a;
    }

    .tt-table .row-day td i {
        margin-right: 8px;
        color: #d97706;
        font-size: .75rem;
    }

    /* data rows */
    .tt-table tbody tr.tt-row {
        transition: background .18s;
    }

    .tt-table tbody tr.tt-row:nth-child(odd) {
        background: #ffffff;
    }

    .tt-table tbody tr.tt-row:nth-child(even) {
        background: #f8faff;
    }

    .tt-table tbody tr.tt-row:hover {
        background: #eef2ff;
    }

    .tt-table tbody td {
        padding: 9px 12px;
        border-bottom: 1px solid #dde5ff;
        border-right: 1px solid #e8eeff;
        font-size: .82rem;
        color: var(--text);
        vertical-align: middle;
    }

    .tt-table tbody td:last-child {
        border-right: none;
    }

    /* column widths */
    .tt-table .col-time {
        width: 13%;
        white-space: nowrap;
    }

    .tt-table .col-subj {
        width: 35%;
        font-weight: 600;
    }

    .tt-table .col-course {
        width: 14%;
        color: var(--muted);
        font-size: .75rem;
    }

    .tt-table .col-fac {
        width: 21%;
        white-space: nowrap;
        font-size: .78rem;
        color: var(--muted);
    }

    .tt-table .col-act {
        width: 17%;
        text-align: right;
        white-space: nowrap;
    }

    /* time chip */
    .time-chip {
        display: inline-block;
        background: linear-gradient(135deg, #e0e7ff, #ede9fe);
        color: #4338ca;
        border-radius: 8px;
        padding: 4px 9px;
        font-size: .72rem;
        font-weight: 800;
        border: 1px solid #c7d2fe;
        letter-spacing: .01em;
    }

    /* subject clamp — max 2 lines */
    .subj-text {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-weight: 600;
        font-size: .82rem;
        line-height: 1.35;
    }

    /* faculty inline display */
    .fac-cell {
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .fac-icon {
        width: 22px;
        height: 22px;
        background: #e0f2fe;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0369a1;
        font-size: .62rem;
        flex-shrink: 0;
    }

    .fac-name {
        font-size: .78rem;
        font-weight: 600;
        color: var(--text);
    }

    /* holiday chip */
    .holiday-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #fef3c7;
        color: #b45309;
        border-radius: 8px;
        padding: 4px 10px;
        font-size: .72rem;
        font-weight: 800;
        border: 1px solid #fcd34d;
    }

    .event-chip {
        display: inline-block;
        background: #ede9fe;
        color: #7c3aed;
        border-radius: 20px;
        padding: 2px 8px;
        font-size: .68rem;
        font-weight: 800;
        border: 1px solid #c4b5fd;
        margin-left: 5px;
        vertical-align: middle;
    }

    /* join / room / lab action buttons */
    .join-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: linear-gradient(135deg, #6366f1, #818cf8);
        color: #fff !important;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: .72rem;
        font-weight: 700;
        text-decoration: none !important;
        white-space: nowrap;
        box-shadow: 0 3px 10px rgba(99, 102, 241, .28);
        transition: all .2s;
    }

    .join-btn:hover {
        background: linear-gradient(135deg, #4338ca, #6366f1);
        transform: scale(1.04);
    }

    .room-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #fef2f2;
        color: #ef4444;
        border: 1px solid #fee2e2;
        border-radius: 8px;
        padding: 5px 10px;
        font-size: .72rem;
        font-weight: 800;
    }

    .lab-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        padding: 5px 10px;
        font-size: .72rem;
        font-weight: 800;
    }

    /* empty / loading */
    .no-data {
        text-align: center;
        padding: 50px 20px;
        background: #fff;
        border-radius: var(--radius-md);
        border: 1.5px dashed var(--border);
        color: var(--muted);
    }

    .no-data i {
        font-size: 2.5rem;
        display: block;
        margin-bottom: 12px;
        opacity: .4;
    }

    /* ── Responsive ──────────────────────────────── */
    @media (max-width:768px) {
        #ttPage {
            margin: 5px auto;
            padding: 0 6px 50px;
        }

        .college-name {
            font-size: 1.15rem !important;
        }

        .college-affiliation {
            font-size: .65rem !important;
            white-space: normal !important;
        }

        .page-header {
            padding: 16px 18px !important;
            margin-bottom: 16px !important;
        }

        .ph-title {
            font-size: 1.05rem !important;
        }

        .ph-title span {
            font-size: .75rem !important;
        }

        /* Keep filter row on ONE line on mobile too */
        .filter-row {
            padding: 8px 10px !important;
            gap: 6px !important;
        }

        .filter-input {
            font-size: .72rem !important;
            padding: 6px 7px !important;
        }

        .btn-fetch {
            padding: 7px 12px !important;
            font-size: .75rem !important;
        }

        .tt-table .col-course {
            display: none;
        }

        .tt-table thead th,
        .tt-table tbody td {
            padding: 8px 8px;
            font-size: .72rem;
        }

        .course-pill {
            font-size: .65rem;
            padding: 6px 12px 6px 10px;
            gap: 5px;
        }

        .course-pill .cp-icon {
            width: 20px;
            height: 20px;
            font-size: .7rem;
        }
    }

    @media (max-width:480px) {
        .page-header {
            border-radius: 0 0 28px 28px !important;
            padding: 20px 16px !important;
        }

        .college-branding {
            border-radius: 20px 20px 0 0 !important;
            padding: 10px !important;
        }

        .college-name {
            font-size: 1.15rem !important;
            letter-spacing: -.01em !important;
        }

        .college-affiliation {
            font-size: .63rem !important;
            white-space: normal !important;
        }
    }
</style>

<div id="ttPage">

    <!-- ── College Branding ── -->
    <div class="college-branding">
        <span class="college-name">GIIT PROFESSIONAL COLLEGE</span>
        <span class="college-affiliation">Affiliated to KOLHAN UNIVERSITY | Approved by HRD, Govt. of Jharkhand</span>
    </div>

    <!-- ── Page Header ── -->
    <div class="page-header">
        <div class="ph-title">
            Class Timetable
            <span>Weekly schedule &amp; online lecture links</span>
        </div>
        <div class="course-pill">
            <span class="cp-icon"><i class="fa fa-graduation-cap"></i></span>
            <?php echo htmlspecialchars($_SESSION['course']['course_name'] ?? 'Course'); ?>
        </div>
    </div>

    <!-- ── Filter Row ── -->
    <div class="filter-row">
        <span class="filter-label"><i class="fa fa-calendar"></i>&nbsp;From</span>
        <input type="date" id="start_date" class="filter-input">
        <span class="filter-divider">→</span>
        <input type="date" id="end_date" class="filter-input">
        <button id="fetch_timetable" class="btn-fetch">
            <i class="fa fa-search" style="margin-right:5px;"></i>Fetch
        </button>
    </div>

    <!-- ── Table Frame ── -->
    <div id="ttContainer">
        <div class="no-data">
            <i class="fa fa-calendar-alt"></i>
            <p>Select a date range and click Fetch to load your timetable.</p>
        </div>
    </div>

</div><!-- /#ttPage -->

<script src="dn_js/jquery.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<script>
    $(() => {
        // Match old code's auto-date generation logic
        const start = moment('Mon', ['dddd']).format('YYYY-MM-DD');
        const end = moment('Sat', ['dddd']).add(1, 'days').format('YYYY-MM-DD');
        
        $('#start_date').val(start);
        $('#end_date').val(end);
        
        // Trigger fetch immediately
        $("#fetch_timetable").click();
    });

    $(document).on('click', '#fetch_timetable', function () {
        const st_dt = $('#start_date').val();
        const en_dt = $('#end_date').val();

        if (!st_dt || !en_dt) {
            if (window.alert_msg) alert_msg('Please select both dates!');
            else alert('Please select both dates!');
            return;
        }

        $('#ttContainer').html(`
        <div class="no-data">
            <i class="fas fa-circle-notch fa-spin"></i>
            <p>Generating timetable…</p>
        </div>
    `);

        if (window.loader_custom_show) loader_custom_show('fetching timetable…');

        $.post('/student-management/student/process.php', {
            POST_TYPE: 'FETCH_TIMETABLE',
            POST_DATA: JSON.stringify({ st_dt, en_dt })
        }).done(res => {
            if (window.loader_custom_hide) loader_custom_hide();

            try { res = typeof res === 'string' ? JSON.parse(res) : res; }
            catch (e) {
                $('#ttContainer').html('<div class="no-data"><p>Error reading server response.</p></div>');
                return;
            }

            if (res.error != 0) {
                $('#ttContainer').html(`<div class="no-data"><p>${res.error}</p></div>`);
                return;
            }

            const data = res.data;
            const days = Object.keys(data);

            if (!days.length) {
                $('#ttContainer').html(`
                <div class="no-data">
                    <i class="fa fa-calendar-times"></i>
                    <p>No classes scheduled for the selected date range.</p>
                </div>
            `);
                return;
            }

            /* Build ONE table with day-group rows separating each date */
            let rows = '';
            days.forEach(day => {
                const dayLabel = moment(day, 'YYYY-MM-DD').format('ddd, D MMM YYYY');
                rows += `
                <tr class="row-day">
                    <td colspan="6"><i class="fa fa-calendar-day"></i>${dayLabel}</td>
                </tr>`;

                const times = Object.keys(data[day]);
                times.forEach(time => {
                    data[day][time].forEach(s => {
                        const isHoliday = s.holiday !== 'false';
                        const isEvent = s.event !== 'false';
                        const timeRange = `${s.class_start_time}–${s.class_end_time}`;

                        let actionCell = '';
                        if (isHoliday) {
                            actionCell = `<span class="holiday-chip"><i class="fa fa-umbrella-beach"></i>&nbsp;${s.holiday.substr(0, 18)}</span>`;
                        } else if (s.class_room == '0') {
                            actionCell = `<a href="${s.class_link}" target="_blank" class="join-btn"><i class="fa fa-video"></i> Join</a>`;
                        } else {
                            // Detect if it's a lab (room name contains LAB) or a regular room
                            const isLab = s.room_name && s.room_name.toUpperCase().includes('LAB');
                            if (isLab) {
                                actionCell = `<span class="lab-chip"><i class="fa fa-flask"></i>&nbsp;${s.room_name}</span>`;
                            } else {
                                actionCell = `<span class="room-chip"><i class="fa fa-door-open"></i>&nbsp;${s.room_name}</span>`;
                            }
                        }

                        rows += `
                        <tr class="tt-row">
                            <td class="col-time">
                                <span class="time-chip">${timeRange}</span>
                            </td>
                            <td class="col-subj">
                                <div class="subj-text">
                                    ${s.subject_name}${isEvent ? `<span class="event-chip"><i class="fa fa-star"></i> ${s.event.substr(0, 14)}</span>` : ''}
                                </div>
                            </td>
                            <td class="col-course">${s.course_name} Sem&nbsp;${s.semester_id}</td>
                            <td class="col-fac">
                                <div class="fac-cell">
                                    <div class="fac-icon"><i class="fa fa-user"></i></div>
                                    <span class="fac-name">${s.faculty_name}</span>
                                </div>
                            </td>
                            <td class="col-act">${actionCell}</td>
                        </tr>`;
                    });
                });
            });

            $('#ttContainer').html(`
            <div class="tt-frame">
                <div class="tt-wrap">
                    <table class="tt-table">
                        <thead>
                            <tr>
                                <th class="col-time">Time</th>
                                <th class="col-subj">Subject</th>
                                <th class="col-course">Course / Sem</th>
                                <th class="col-fac">Faculty</th>
                                <th class="col-act">Room</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        `);

        }).fail(() => {
            if (window.loader_custom_hide) loader_custom_hide();
            $('#ttContainer').html('<div class="no-data"><p>Connection failed. Please try again.</p></div>');
        });
    });
</script>