<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GIIT Professional College | Portal Login</title>
    <meta name="description" content="GIIT Professional College — Select your portal to access Student, Faculty, Admin or Enquiry dashboards.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 (icons in portal circles) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ────────────────────────────────────────────
           RESET & ROOT TOKENS
        ──────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --glass-bg:     rgba(255, 255, 255, 0.92);
            --glass-border: rgba(255, 255, 255, 0.55);
            --shadow-xl:    0 32px 72px -12px rgba(0, 0, 0, 0.40);
            --radius-2xl:   28px;
            --radius-xl:    18px;
            --radius-card:  20px;
            --text-main:    #0f172a;
            --text-muted:   #475569;
        }

        /* ────────────────────────────────────────────
           BODY — deep-blue campus background
        ──────────────────────────────────────────── */
        body {
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            position: relative;
            overflow-x: hidden;

            /* Layered dark-blue background */
            background-color: #0d1b3e;
            background-image:
                linear-gradient(
                    135deg,
                    rgba(8, 16, 42, 0.78) 0%,
                    rgba(18, 38, 76, 0.65) 50%,
                    rgba(8, 16, 42, 0.78) 100%
                ),
                repeating-linear-gradient(0deg,
                    transparent, transparent 60px,
                    rgba(255,255,255,0.018) 60px,
                    rgba(255,255,255,0.018) 61px),
                repeating-linear-gradient(90deg,
                    transparent, transparent 60px,
                    rgba(255,255,255,0.018) 60px,
                    rgba(255,255,255,0.018) 61px),
                linear-gradient(160deg, #0d1b3e 0%, #1a3560 42%, #0f2d55 72%, #0a1a35 100%);
            background-attachment: fixed;
        }

        /* Animated ambient orbs */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        body::before {
            width: 650px; height: 650px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.20) 0%, transparent 68%);
            top: -200px; left: -180px;
            animation: floatOrb 9s ease-in-out infinite alternate;
        }
        body::after {
            width: 550px; height: 550px;
            background: radial-gradient(circle, rgba(22, 163, 74, 0.14) 0%, transparent 68%);
            bottom: -140px; right: -140px;
            animation: floatOrb 11s ease-in-out infinite alternate-reverse;
        }

        /* ────────────────────────────────────────────
           KEYFRAMES
        ──────────────────────────────────────────── */
        @keyframes floatOrb {
            0%   { transform: translate(0, 0) scale(1); }
            100% { transform: translate(45px, 45px) scale(1.12); }
        }
        @keyframes cardFadeUp {
            from { opacity: 0; transform: translateY(44px) scale(0.93); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }
        @keyframes portalIn {
            from { opacity: 0; transform: translateY(22px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes btnShimmer {
            0%   { left: -90px; }
            100% { left: 130%; }
        }
        @keyframes iconPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255,255,255,0.30); }
            50%       { box-shadow: 0 0 0 8px rgba(255,255,255,0.00); }
        }

        /* ────────────────────────────────────────────
           MAIN GLASS CARD
        ──────────────────────────────────────────── */
        .portal-card {
            background: var(--glass-bg);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            border: 1.5px solid var(--glass-border);
            box-shadow: var(--shadow-xl);
            border-radius: var(--radius-2xl);
            width: 100%;
            max-width: 720px;
            position: relative;
            z-index: 1;
            overflow: hidden;
            animation: cardFadeUp 0.90s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        /* Rainbow top-accent bar */
        .portal-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 5px;
            background: linear-gradient(90deg, #2563eb 0%, #16a34a 33%, #dc2626 66%, #ea580c 100%);
            z-index: 3;
        }



        /* ────────────────────────────────────────────
           PORTAL CONTENT
        ──────────────────────────────────────────── */
        .portal-content {
            padding: 30px 32px 32px;
        }

        /* Heading */
        .portal-heading {
            text-align: center;
            margin-bottom: 26px;
        }
        .portal-heading h1 {
            font-weight: 800;
            font-size: 1.45rem;
            color: var(--text-main);
            letter-spacing: -0.02em;
            margin-bottom: 5px;
        }
        .portal-heading p {
            color: var(--text-muted);
            font-size: 0.91rem;
            font-weight: 500;
        }

        /* ────────────────────────────────────────────
           PORTAL GRID
        ──────────────────────────────────────────── */
        .portal-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 26px;
        }

        /* ────────────────────────────────────────────
           PORTAL BUTTON CARD
        ──────────────────────────────────────────── */
        .portal-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            text-align: center;
            text-decoration: none;
            border-radius: var(--radius-card);
            padding: 22px 10px 18px;
            color: #fff;
            border: none;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition:
                transform  0.28s cubic-bezier(0.34, 1.56, 0.64, 1),
                box-shadow 0.28s ease,
                filter     0.20s ease;
        }
        /* Shimmer sweep */
        .portal-btn::before {
            content: '';
            position: absolute;
            top: 0; left: -90px;
            width: 65px; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.24), transparent);
            transform: skewX(-15deg);
        }
        .portal-btn:hover::before {
            animation: btnShimmer 0.65s ease forwards;
        }
        .portal-btn:hover {
            transform: translateY(-7px) scale(1.04);
            filter: brightness(1.09);
            color: #fff;
            text-decoration: none;
        }
        .portal-btn:active { transform: scale(0.97); }

        /* Entrance stagger */
        .portal-btn:nth-child(1) { animation: portalIn 0.55s 0.12s ease both; }
        .portal-btn:nth-child(2) { animation: portalIn 0.55s 0.22s ease both; }
        .portal-btn:nth-child(3) { animation: portalIn 0.55s 0.32s ease both; }
        .portal-btn:nth-child(4) { animation: portalIn 0.55s 0.42s ease both; }

        /* Colour variants */
        .portal-btn.student {
            background: linear-gradient(148deg, #60a5fa 0%, #2563eb 55%, #1d4ed8 100%);
            box-shadow: 0 10px 28px -5px rgba(37, 99, 235, 0.55);
        }
        .portal-btn.faculty {
            background: linear-gradient(148deg, #4ade80 0%, #16a34a 55%, #15803d 100%);
            box-shadow: 0 10px 28px -5px rgba(22, 163, 74, 0.55);
        }
        .portal-btn.admin {
            background: linear-gradient(148deg, #fca5a5 0%, #dc2626 55%, #b91c1c 100%);
            box-shadow: 0 10px 28px -5px rgba(220, 38, 38, 0.55);
        }
        .portal-btn.enquiry {
            background: linear-gradient(148deg, #fdba74 0%, #ea580c 55%, #c2410c 100%);
            box-shadow: 0 10px 28px -5px rgba(234, 88, 12, 0.55);
        }

        /* ────────────────────────────────────────────
           ICON CIRCLE
        ──────────────────────────────────────────── */
        .portal-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.20);
            border: 2.5px solid rgba(255, 255, 255, 0.40);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 13px;
            font-size: 1.75rem;   /* icon size */
            color: #ffffff;
            transition: transform 0.30s ease, background 0.25s ease;
            animation: iconPulse 3s ease-in-out infinite;
        }
        .portal-btn:hover .portal-icon {
            transform: scale(1.15) rotate(6deg);
            background: rgba(255, 255, 255, 0.32);
        }

        /* Icon overrides per portal */
        .portal-btn.student  .portal-icon { text-shadow: 0 2px 8px rgba(37,99,235,0.5); }
        .portal-btn.faculty  .portal-icon { text-shadow: 0 2px 8px rgba(22,163,74,0.5); }
        .portal-btn.admin    .portal-icon { text-shadow: 0 2px 8px rgba(220,38,38,0.5); }
        .portal-btn.enquiry  .portal-icon { text-shadow: 0 2px 8px rgba(234,88,12,0.5); }

        .portal-label {
            font-weight: 800;
            font-size: 0.76rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            margin-bottom: 5px;
            line-height: 1.2;
            color: #fff;
        }
        .portal-desc {
            font-size: 0.71rem;
            font-weight: 500;
            opacity: 0.88;
            line-height: 1.45;
            color: #fff;
        }

        /* ────────────────────────────────────────────
           FOOTER HELP LINE
        ──────────────────────────────────────────── */
        .portal-footer {
            text-align: center;
            font-size: 0.87rem;
            color: var(--text-muted);
            border-top: 1px solid rgba(0,0,0,0.08);
            padding-top: 18px;
        }
        .portal-footer a {
            color: #2563eb;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.2s;
        }
        .portal-footer a:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        /* ────────────────────────────────────────────
           RESPONSIVE
        ──────────────────────────────────────────── */
        @media (max-width: 640px) {
            .portal-card { max-width: 100%; }
            .portal-content { padding: 22px 14px 26px; }
            .portal-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .portal-btn { padding: 20px 10px 16px; }
            .portal-icon { width: 54px; height: 54px; font-size: 1.5rem; }
            .portal-heading h1 { font-size: 1.2rem; }
        }

        @media (max-width: 420px) {
            .college-branding { flex-direction: column; gap: 8px; }
            .college-text { text-align: center; }
            .college-name { font-size: 1.05rem; }
            .portal-heading h1 { font-size: 1.1rem; }
            .portal-grid { gap: 10px; }
        }
    </style>
</head>
<body>

<div class="portal-card">



    <!-- ── Portal Content ── -->
    <div class="portal-content">

        <!-- Heading -->
        <div class="portal-heading">
            <h1>Welcome back! Please Select Your Portal</h1>
            <p>Choose your login type to access your dashboard</p>
        </div>

        <!-- Portal Grid -->
        <div class="portal-grid">

            <!-- Student Login -->
            <a href="https://mygiit.com/student-login.php"
               class="portal-btn student"
               id="btn-student-login"
               title="Student Login">
                <div class="portal-icon">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <div class="portal-label">Student Login</div>
                <div class="portal-desc">Access courses &amp; grades</div>
            </a>

            <!-- Faculty Login -->
            <a href="faculty-login.php"
               class="portal-btn faculty"
               id="btn-faculty-login"
               title="Faculty Login">
                <div class="portal-icon">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
                <div class="portal-label">Faculty Login</div>
                <div class="portal-desc">Manage classes &amp; resources</div>
            </a>

            <!-- Admin Login -->
            <a href="https://mygiit.com/admin-login.php"
               class="portal-btn admin"
               id="btn-admin-login"
               title="Admin Login">
                <div class="portal-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="portal-label">Admin Login</div>
                <div class="portal-desc">System administration</div>
            </a>

            <!-- Enquiry Portal -->
            <a href="https://mygiit.com/enquiry.php"
               class="portal-btn enquiry"
               id="btn-enquiry-portal"
               title="Enquiry Portal">
                <div class="portal-icon">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <div class="portal-label">Enquiry Portal</div>
                <div class="portal-desc">Admissions &amp; General Information</div>
            </a>

        </div>

        <!-- Footer -->
        <div class="portal-footer">
            Need help? <a href="mailto:support@giitcollege.in">Contact Support</a>
        </div>

    </div><!-- /.portal-content -->
</div><!-- /.portal-card -->

</body>
</html>
