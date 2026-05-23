<?php
include("index-cached.php");
include("header.php");
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/super-treadmill.css" />

<style>
/* ═══════════════════════════════════════════════════
   DESIGN TOKENS  — matching student portal palette
═══════════════════════════════════════════════════ */
:root {
    --primary:       #00205b; /* NSU Navy Blue */
    --primary-light: #163e8a;
    --primary-dark:  #001133;
    --teal:          #14b8a6;
    --amber:         #e5a823;
    --rose:          #a6192e; /* NSU Maroon/Red */
    --bg:            #eae8e3; /* Beige background */
    --card:          #ffffff;
    --text:          #0f172a;
    --muted:         #555555;
    --border:        #d1cfca;
    --shadow-sm:     0 1px 2px 0 rgba(0,0,0,.08);
    --shadow-md:     0 4px 6px -1px rgba(0,0,0,.12), 0 2px 4px -2px rgba(0,0,0,.08);
    --shadow-lg:     0 10px 15px -3px rgba(0,0,0,.15);
    --radius-md:     6px; /* Structured blocky corners */
    --radius-lg:     8px;
}

/* ── Reset & Base ── */
*, *::before, *::after { box-sizing: border-box; }

body {
    background: var(--bg);
    font-family: 'Outfit', sans-serif !important;
    color: var(--text);
    -webkit-font-smoothing: antialiased;
}

img { max-width: 100%; height: auto; }

/* ── Keyframes ── */
@keyframes headerShimmer {
    0%   { transform: translateX(-100%) skewX(-15deg); }
    100% { transform: translateX(400%)  skewX(-15deg); }
}
@keyframes fadeInUp {
    from { opacity:0; transform:translateY(22px); }
    to   { opacity:1; transform:translateY(0);    }
}
@keyframes badgePulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(255,255,255,.3), 0 4px 15px rgba(0,0,0,.15); }
    50%     { box-shadow: 0 0 0 6px rgba(255,255,255,.08), 0 4px 20px rgba(0,0,0,.2); }
}
@keyframes float {
    0%,100% { transform: translateY(0px); }
    50%     { transform: translateY(-6px); }
}
@keyframes ripple {
    0%   { transform:scale(1);   opacity:.6; }
    100% { transform:scale(2.5); opacity:0;  }
}

/* ══════════════════════════════════════════════════
   FIXED QUICK ENQUIRY BUTTON
══════════════════════════════════════════════════ */
.navbar-fixed-bottom {
    position: fixed;
    bottom: 0; right: 0;
    z-index: 9999;
    padding: 0 18px 16px 0;
}
.btn-quick-enquiry {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    background: linear-gradient(135deg, #1e1b6e 0%, #4f46e5 60%, #818cf8 100%);
    color: #fff !important;
    border: none;
    padding: 13px 22px;
    border-radius: 50px;
    font-family: 'Outfit', sans-serif;
    font-size: .9rem;
    font-weight: 700;
    letter-spacing: .02em;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(79,70,229,.45), inset 0 1px 0 rgba(255,255,255,.2);
    transition: all .3s cubic-bezier(.34,1.56,.64,1);
    text-transform: uppercase;
    position: relative;
    overflow: hidden;
}
.btn-quick-enquiry::after {
    content:'';
    position:absolute; top:0; left:0;
    width:55px; height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.22),transparent);
    animation:headerShimmer 2.8s ease-in-out infinite;
}
.btn-quick-enquiry:hover {
    transform: translateY(-3px) scale(1.04);
    box-shadow: 0 14px 32px rgba(79,70,229,.55);
}

/* ══════════════════════════════════════════════════
   CAROUSEL / BANNER
══════════════════════════════════════════════════ */
.home_slider { border-radius: 0 0 var(--radius-lg) var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-lg); }
.carousel-indicators li { background: rgba(255,255,255,.5); width:10px; height:10px; border-radius:50%; margin:0 4px; border:none; }
.carousel-indicators li.active { background:#fff; }

/* Desktop overlay — premium GIIT branding */
.w3-display-bottommiddle {
    background: linear-gradient(180deg, rgba(0,32,91,.0) 0%, rgba(0,32,91,.72) 18%, rgba(0,32,91,.92) 100%);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-top: 1px solid rgba(255,255,255,.1);
    padding: 18px 32px 22px;
    width: 100%;
    bottom: 0 !important;
    position: absolute;
}

/* College branding row inside overlay */
.overlay-college-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}
.overlay-college-name {
    font-family: 'Outfit', sans-serif;
    font-size: 1.55rem;
    font-weight: 900;
    color: #ce1126;
    text-transform: uppercase;
    letter-spacing: .06em;
    line-height: 1.1;
    text-shadow: 0 2px 12px rgba(0,0,0,.55), 0 0 30px rgba(206,17,38,.4);
    display: block;
}
.overlay-college-affil {
    font-family: 'Outfit', sans-serif;
    font-size: .75rem;
    font-weight: 700;
    color: rgba(255,255,255,.85);
    letter-spacing: .04em;
    text-transform: uppercase;
    display: block;
    margin-top: 3px;
}
.overlay-brand-divider {
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.18), transparent);
    margin-bottom: 12px;
}
.overlay-label {
    font-family:'Outfit',sans-serif;
    font-size: .72rem;
    font-weight: 700;
    color: rgba(224,231,255,.8);
    letter-spacing: .1em;
    text-transform: uppercase;
    margin-bottom: 10px;
    display: block;
}
.apply-btn-grid { display:flex; gap:10px; flex-wrap:wrap; justify-content:center; align-items:center; }
.apply-btn {
    display:inline-flex; align-items:center; justify-content:center;
    padding: 10px 24px;
    border-radius: 50px;
    font-family:'Outfit',sans-serif;
    font-size:.85rem; font-weight:800;
    letter-spacing:.05em;
    color:#fff !important;
    text-decoration:none !important;
    transition:all .25s cubic-bezier(.34,1.56,.64,1);
    border:2px solid rgba(255,255,255,.4);
    background:rgba(255,255,255,.1);
    backdrop-filter:blur(8px);
    text-transform:uppercase;
}
.apply-btn:hover { background:rgba(255,255,255,.25); transform:translateY(-3px); box-shadow:0 8px 22px rgba(0,0,0,.3); }
.apply-btn.highlight {
    background: linear-gradient(135deg, #e11d48, #be123c);
    border-color: rgba(255,255,255,.3);
    box-shadow: 0 6px 18px rgba(225,29,72,.5);
}
.apply-btn.highlight:hover { background:linear-gradient(135deg,#be123c,#9f1239); transform:translateY(-3px) scale(1.05); }

.btn-call-banner {
    display:inline-flex; align-items:center; gap:9px;
    padding:12px 28px;
    border-radius:50px;
    background:linear-gradient(135deg,#f59e0b,#f97316);
    color:#fff !important;
    font-family:'Outfit',sans-serif;
    font-size:.9rem; font-weight:900;
    text-decoration:none !important;
    border: 2px solid rgba(255,255,255,.25);
    box-shadow:0 6px 22px rgba(245,158,11,.5), inset 0 1px 0 rgba(255,255,255,.2);
    transition:all .3s cubic-bezier(.34,1.56,.64,1);
    margin-top:12px;
    letter-spacing:.03em;
    text-transform:uppercase;
    position:relative; overflow:hidden;
}
.btn-call-banner::after {
    content:'';
    position:absolute; top:0; left:0;
    width:50px; height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.25),transparent);
    animation:headerShimmer 2.5s ease-in-out infinite;
}
.btn-call-banner:hover { transform:translateY(-3px) scale(1.04); box-shadow:0 12px 30px rgba(245,158,11,.6); color:#fff !important; }

/* Mobile banner strip */
.mobile-apply-strip {
    background: #fff;
    padding: 16px;
    display: none;
}
.mobile-apply-strip .overlay-label { color:var(--primary-dark); margin-bottom:10px; }
.mobile-apply-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.mobile-apply-btn {
    display:flex; align-items:center; justify-content:center;
    padding:11px 8px;
    border-radius:10px;
    font-family:'Outfit',sans-serif;
    font-size:.85rem; font-weight:700;
    color:#fff !important;
    text-decoration:none !important;
    background:var(--rose);
    box-shadow:0 4px 12px rgba(225,29,72,.3);
    transition:all .25s ease;
}
.mobile-apply-btn:hover { transform:translateY(-2px); }
.mobile-call-btn {
    display:flex; align-items:center; justify-content:center; gap:8px;
    padding:13px;
    border-radius:10px;
    background:linear-gradient(135deg,#f59e0b,#f97316);
    color:#fff !important;
    font-family:'Outfit',sans-serif;
    font-size:.9rem; font-weight:800;
    text-decoration:none !important;
    margin-top:10px;
    box-shadow:0 4px 14px rgba(245,158,11,.35);
}

/* Carousel nav */
.carousel-control {
    background: rgba(10,8,60,.4) !important;
    width: 44px; height:44px;
    border-radius:50%; top:50%; margin-top:-22px;
    opacity:1;
    display:flex; align-items:center; justify-content:center;
    transition:all .25s ease;
}
.carousel-control:hover { background: rgba(79,70,229,.7) !important; }
.left.carousel-control { left:12px; }
.right.carousel-control { right:12px; }
.carousel-control .glyphicon { font-size:1rem; }

/* ══════════════════════════════════════════════════
   PAGE CONTAINER
══════════════════════════════════════════════════ */
.page-wrap { max-width: 1200px; margin: 0 auto; padding: 0 18px; }

/* ══════════════════════════════════════════════════
   SECTION HEADERS
══════════════════════════════════════════════════ */
.section-header {
    display:flex; align-items:center; gap:14px;
    margin-bottom:24px;
    animation:fadeInUp .5s ease-out both;
}
.section-badge {
    width:44px; height:44px;
    border-radius:14px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem;
    flex-shrink:0;
}
.section-badge.indigo { background:rgba(79,70,229,.1); color:var(--primary); }
.section-badge.teal   { background:rgba(20,184,166,.1); color:var(--teal); }
.section-badge.amber  { background:rgba(245,158,11,.1); color:#d97706; }
.section-badge.rose   { background:rgba(225,29,72,.1);  color:var(--rose); }

.section-title { font-size:1.35rem; font-weight:800; color:var(--primary-dark); margin:0; line-height:1.2; }
.section-sub   { font-size:.78rem;  color:var(--muted); font-weight:500; margin-top:2px; }

/* ══════════════════════════════════════════════════
   TESTIMONIALS SECTION
══════════════════════════════════════════════════ */
#SECTION_TESTIMONIAL {
    background: var(--primary);
    padding: 55px 0 60px;
    position: relative;
    border-top: 4px solid var(--rose);
}
.testi-section-title {
    text-align:center;
    font-family:'Outfit',sans-serif;
    font-size:1.9rem; font-weight:900;
    color:#fff;
    letter-spacing:-.02em;
    margin-bottom:6px;
    text-shadow:0 2px 12px rgba(0,0,0,.2);
}
.testi-section-sub {
    text-align:center;
    color:rgba(255,255,255,.7);
    font-size:.9rem; font-weight:500;
    margin-bottom:36px;
}
.testi-grid {
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;
    position:relative; z-index:1;
}
.testi-card {
    background: #ffffff;
    border: 1px solid #eaeaea;
    border-radius: var(--radius-md);
    padding: 24px 20px 20px;
    position: relative;
    box-shadow: var(--shadow-md);
    transition: all .3s ease;
    animation:fadeInUp .5s ease-out both;
}
.testi-card:nth-child(1) { animation-delay:.1s; }
.testi-card:nth-child(2) { animation-delay:.18s; }
.testi-card:nth-child(3) { animation-delay:.26s; }
.testi-card:nth-child(4) { animation-delay:.34s; }
.testi-card:hover {
    transform:translateY(-8px);
    box-shadow:0 15px 30px rgba(0,0,0,.15);
}
.testi-avatar-wrap {
    display:flex; align-items:center; gap:12px;
    margin-bottom:16px;
}
.testi-avatar {
    width:54px; height:54px;
    border-radius:50%;
    border:3px solid var(--primary);
    object-fit:cover;
    flex-shrink:0;
}
.testi-meta { flex:1; }
.testi-name {
    font-family:'Outfit',sans-serif;
    font-size:.95rem; font-weight:800;
    color:var(--primary); line-height:1.2;
    display:block;
}
.testi-course {
    font-size:.72rem; font-weight:700;
    color:var(--rose);
    text-transform:uppercase; letter-spacing:.04em;
    display:block; margin-top:2px;
}
.testi-yt-btn {
    display:inline-flex; align-items:center; gap:6px;
    background:#fff;
    border:1px solid #ddd;
    color:var(--text) !important;
    padding:5px 12px;
    border-radius:50px;
    font-size:.72rem; font-weight:700;
    text-decoration:none !important;
    transition:all .2s ease;
}
.testi-yt-btn:hover { background:#f5f5f5; transform:scale(1.05); }
.testi-yt-btn i { font-size:.9rem; color:#ff4444; }
.testi-quote {
    font-size:.82rem; font-weight:500;
    color:var(--muted);
    line-height:1.65;
    margin:0;
    position:relative;
    padding:0 4px;
}
.testi-quote::before {
    content:'\201C';
    font-size:2.5rem; font-weight:900;
    color:rgba(0,32,91,.1);
    position:absolute; top:-14px; left:-6px;
    line-height:1;
}

/* ══════════════════════════════════════════════════
   LINKS SECTION (Three Blocks)
══════════════════════════════════════════════════ */
.links-section { padding: 48px 0; }
.links-grid {
    display:grid;
    grid-template-columns: 2fr 1fr;
    gap:18px;
}
.links-left-grid {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:16px;
}
.link-card {
    background: var(--primary);
    border-radius: var(--radius-md);
    border: none;
    box-shadow: var(--shadow-md);
    overflow: hidden;
    transition: transform .3s ease, box-shadow .3s ease;
    animation: fadeInUp .5s ease-out both;
}
.links-left-grid .link-card:nth-child(even) { background: var(--rose); }

.link-card:hover { transform:translateY(-6px); box-shadow:var(--shadow-lg); }
.link-card-header {
    padding:14px 18px 12px;
    border-bottom:1px solid rgba(255,255,255,.15);
    display:flex; align-items:center; gap:10px;
}
.link-card-icon {
    width:36px; height:36px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.2rem; flex-shrink:0;
    color: #fff !important; background: transparent !important;
}

.link-card-title { font-size:1.1rem; font-weight:800; color:#fff; margin:0; text-transform:uppercase; }
.link-list { padding:10px 18px 14px; list-style:none; margin:0; }
.link-list li { padding:0; margin:0; }
.link-list li + li { border-top:1px solid rgba(255,255,255,.1); }
.link-list a {
    display:flex; align-items:center; gap:8px;
    padding:8px 0;
    font-size:.85rem; font-weight:600;
    color:rgba(255,255,255,.9) !important;
    text-decoration:none !important;
    transition:all .2s ease;
}
.link-list a::before {
    content:'\f054';
    font-family:'Font Awesome 6 Free';
    font-weight:900;
    font-size:.6rem;
    color:rgba(255,255,255,.5);
    flex-shrink:0;
    transition:transform .2s ease;
}
.link-list a:hover { color:#fff !important; padding-left:4px; }
.link-list a:hover::before { transform:translateX(2px); color:#fff; }

/* Announcements card */
.announce-card {
    background:#fff;
    border-radius:var(--radius-md);
    border:1px solid var(--border);
    box-shadow:var(--shadow-md);
    overflow:hidden;
    display:flex; flex-direction:column;
    height:100%;
    transition:transform .3s ease, box-shadow .3s ease;
    animation:fadeInUp .5s ease-out both;
    animation-delay:.2s;
}
.announce-card:hover { transform:translateY(-4px); box-shadow:var(--shadow-lg); }
.announce-header {
    padding:14px 18px 12px;
    border-bottom:3px solid var(--rose);
    display:flex; align-items:center; gap:10px;
    background:var(--primary);
}
.announce-header .link-card-title { color:#fff; }
.announce-header .link-card-icon { color:#fff !important; }

.treadmill-wrap {
    flex:1;
    padding:8px 18px;
    overflow:hidden;
    position:relative;
}
.treadmill-unit {
    padding:8px 0;
    border-bottom:1px solid #eaeaea;
    font-size:.85rem;
}
.treadmill-unit a {
    color:var(--text) !important;
    font-weight:700;
    text-decoration:none !important;
    display:block;
    margin-bottom:4px;
    transition:color .2s;
}
.treadmill-unit a:hover { color:var(--rose) !important; }
.treadmill-unit b { font-size:.7rem; color:var(--rose); font-weight:700; display:block; text-transform:uppercase; }
.view-all-btn {
    display:block;
    margin:10px 18px 14px;
    padding:10px;
    text-align:center;
    background:var(--rose);
    border:none;
    border-radius:4px;
    font-size:.8rem; font-weight:800;
    color:#fff !important;
    text-decoration:none !important;
    text-transform:uppercase;
    transition:all .2s ease;
}
.view-all-btn:hover { background:#8a1425; }

/* ══════════════════════════════════════════════════
   GALLERY SECTION
══════════════════════════════════════════════════ */
.gallery-section { padding:0 0 48px; }
.gallery-frame {
    background:#fff;
    padding:12px;
    border-radius:var(--radius-md);
    box-shadow:var(--shadow-md);
    border-top:4px solid var(--primary);
}
.gallery-inner {
    background:#fff;
    border-radius:calc(var(--radius-lg) - 4px);
    overflow:hidden;
}
.owl-carousel .item img {
    height: 200px;
    width: 100%;
    object-fit: cover;
    display: block;
    transition: transform .4s ease;
}
.owl-carousel .item:hover img { transform:scale(1.04); }
.owl-carousel .item {
    border-radius: 0;
    overflow: hidden;
}

/* ══════════════════════════════════════════════════
   QUICK ENQUIRY & MAP
══════════════════════════════════════════════════ */
.enquiry-section {
    padding: 48px 0;
    background: var(--bg);
    border-top: 1px solid #dcdcdc;
    border-bottom: 1px solid #dcdcdc;
}
.enquiry-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:24px;
}

/* Form Card */
.form-card {
    background:#fff;
    border:1px solid var(--border);
    border-radius:var(--radius-md);
    padding:28px;
    box-shadow:var(--shadow-sm);
    border-top: 4px solid var(--rose);
}
.form-card-title {
    font-size:1.4rem; font-weight:900;
    color:var(--primary); margin:0 0 20px;
    display:flex; align-items:center; gap:10px;
    text-transform:uppercase;
}
.form-card-title i { color:var(--rose); }

.form-group-modern { margin-bottom:14px; }
.form-group-modern label {
    display:block;
    font-size:.75rem; font-weight:700;
    color:var(--muted);
    text-transform:uppercase; letter-spacing:.05em;
    margin-bottom:6px;
}
.form-control-modern {
    width:100%;
    background:#fdfdfd !important;
    border:1px solid #ccc !important;
    border-radius:4px !important;
    padding:11px 14px !important;
    font-family:'Outfit',sans-serif !important;
    font-size:.9rem !important;
    color:var(--text) !important;
    outline:none;
    transition:all .25s ease;
    -webkit-appearance:none;
}
.form-control-modern::placeholder { color:#999 !important; opacity:1; }
.form-control-modern:focus {
    background:#fff !important;
    border-color:var(--primary) !important;
    box-shadow:0 0 0 3px rgba(0,32,91,.1) !important;
}
.form-control-modern option { background:#fff; color:var(--text); }

.btn-submit-modern {
    width:100%;
    padding:13px;
    background:var(--rose);
    border:none;
    border-radius:4px;
    font-family:'Outfit',sans-serif;
    font-size:.95rem; font-weight:800;
    color:#fff;
    cursor:pointer;
    transition:all .3s ease;
    margin-top:6px;
    letter-spacing:.05em;
    text-transform:uppercase;
}
.btn-submit-modern:hover {
    background:#8a1425;
    transform:translateY(-2px);
}

/* Map Card */
.map-card {
    border-radius:var(--radius-md);
    overflow:hidden;
    border:1px solid var(--border);
    box-shadow:var(--shadow-sm);
    height:100%;
    min-height:420px;
}
.map-card iframe { width:100%; height:100%; border:none; display:block; }

/* ══════════════════════════════════════════════════
   WELCOME SECTION
══════════════════════════════════════════════════ */
.welcome-section { padding:52px 0; }
.welcome-header-bar {
    background:var(--primary);
    padding:20px 30px;
    border-radius:var(--radius-md) var(--radius-md) 0 0;
    color:#fff;
    display:flex; align-items:center; gap:16px;
}
.welcome-icon-box {
    width:52px; height:52px;
    background:rgba(255,255,255,.1);
    border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.4rem;
    flex-shrink:0;
    border:1px solid rgba(255,255,255,.2);
}
.welcome-heading { font-size:1.6rem; font-weight:900; z-index:1; letter-spacing:.02em; text-transform:uppercase; }
.welcome-body {
    background:#fff;
    border:1px solid var(--border);
    border-top:none;
    border-radius:0 0 var(--radius-md) var(--radius-md);
    padding:28px 30px;
    box-shadow:var(--shadow-sm);
}
.welcome-body p {
    font-size:.95rem; font-weight:500;
    color:#444; line-height:1.8;
    margin:0 0 16px;
}
.welcome-body p:last-child { margin-bottom:0; }

/* ══════════════════════════════════════════════════
   JOB READY SECTION
══════════════════════════════════════════════════ */
.jobready-section { padding:0 0 52px; }
.jobready-card {
    background:var(--primary);
    border-radius:var(--radius-md);
    padding:32px;
    display:flex; align-items:center; gap:32px;
    box-shadow:var(--shadow-md);
    border-bottom: 4px solid var(--rose);
}
.jobready-img { max-width:280px; height:auto; z-index:1; flex-shrink:0; animation:float 4s ease-in-out infinite; }
.jobready-content { z-index:1; }
.jobready-title {
    font-size:1.6rem; font-weight:900;
    color:#fff; margin:0 0 8px;
    text-shadow:0 2px 8px rgba(0,0,0,.15);
}
.jobready-sub { color:rgba(255,255,255,.8); font-size:.9rem; font-weight:500; margin-bottom:20px; }
.jobready-courses {
    display:flex; flex-wrap:wrap; gap:10px;
}
.course-pill {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 18px;
    background:#fff;
    border:1px solid #ddd;
    border-radius:4px;
    font-size:.85rem; font-weight:800;
    color:var(--primary) !important;
    text-decoration:none !important;
    transition:all .2s ease;
}
.course-pill:hover {
    background:var(--rose);
    color:#fff !important;
    border-color:var(--rose);
    transform:translateY(-2px);
}

/* ══════════════════════════════════════════════════
   MODAL — Quick Enquiry
══════════════════════════════════════════════════ */
.w3-modal { z-index:99999 !important; }
.w3-modal-inner {
    background:#fff;
    border-radius:var(--radius-lg);
    overflow:hidden;
    box-shadow:0 25px 50px -10px rgba(0,0,0,.4);
    max-width:680px;
    width:calc(100% - 32px);
    margin:auto;
    animation:fadeInUp .35s ease-out;
}
.modal-header-bar {
    background:linear-gradient(135deg,#1e1b6e 0%,#4f46e5 100%);
    padding:16px 22px;
    display:flex; align-items:center; justify-content:space-between;
    position:relative; overflow:hidden;
}
.modal-header-bar::after {
    content:'';
    position:absolute; top:0; left:0;
    width:55px; height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.18),transparent);
    animation:headerShimmer 3s ease-in-out infinite;
}
.modal-title-text { font-size:1rem; font-weight:800; color:#fff; z-index:1; letter-spacing:.02em; text-transform:uppercase; }
.modal-close-btn {
    background:rgba(255,255,255,.15);
    border:1px solid rgba(255,255,255,.3);
    color:#fff; width:32px; height:32px;
    border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; font-size:1.1rem; z-index:1;
    transition:background .2s;
}
.modal-close-btn:hover { background:rgba(255,255,255,.28); }
.modal-body-wrap { padding:22px 22px 18px; }
.modal-input {
    width:100%;
    background:#f8faff;
    border:1.5px solid rgba(99,102,241,.2);
    border-radius:10px;
    padding:10px 14px;
    font-family:'Outfit',sans-serif;
    font-size:.88rem; color:var(--text);
    outline:none; transition:all .25s ease;
    margin-bottom:10px;
    display:block;
}
.modal-input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(79,70,229,.12); background:#fff; }
.modal-input::placeholder { color:var(--muted); }
.modal-footer-bar {
    padding:12px 22px;
    border-top:1px solid var(--border);
    display:flex; justify-content:space-between; align-items:center;
    background:#f8faff;
}
.btn-modal-close {
    padding:9px 20px;
    background:#fff;
    border:1.5px solid var(--border);
    border-radius:50px;
    font-family:'Outfit',sans-serif;
    font-size:.85rem; font-weight:700;
    color:var(--muted); cursor:pointer;
    transition:all .2s ease;
}
.btn-modal-close:hover { border-color:var(--primary); color:var(--primary); }
.btn-modal-submit {
    padding:9px 24px;
    background:linear-gradient(135deg,var(--primary),var(--primary-light));
    border:none;
    border-radius:50px;
    font-family:'Outfit',sans-serif;
    font-size:.85rem; font-weight:800;
    color:#fff; cursor:pointer;
    box-shadow:0 4px 14px rgba(79,70,229,.4);
    transition:all .3s cubic-bezier(.34,1.56,.64,1);
    display:flex; align-items:center; gap:7px;
}
.btn-modal-submit:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(79,70,229,.5); }

/* ══════════════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════════════ */
@media (max-width: 991px) {
    .testi-grid { grid-template-columns:repeat(2,1fr); }
    .links-grid { grid-template-columns:1fr; }
    .links-left-grid { grid-template-columns:repeat(2,1fr); }
    .enquiry-grid { grid-template-columns:1fr; }
    .jobready-card { flex-direction:column; text-align:center; }
    .jobready-img { max-width:220px; }
    .jobready-courses { justify-content:center; }
}

@media (max-width: 767px) {
    .testi-grid { grid-template-columns:1fr; }
    .links-left-grid { grid-template-columns:1fr; }
    .testi-section-title { font-size:1.4rem; }
    .map-card { min-height:280px; }
    /* Mobile banner */
    .mobile-apply-strip { display:block; }
    .w3-display-bottommiddle { display:none !important; }
    /* Nav arrows smaller */
    .carousel-control { width:36px; height:36px; margin-top:-18px; }
}

@media (max-width: 480px) {
    .jobready-title { font-size:1.2rem; }
    .welcome-heading { font-size:1.1rem; }
    .section-title { font-size:1.1rem; }
}

/* ── Utility ── */
.hidden-xs { display:block; }
.visible-xs { display:none; }
@media (max-width:767px) {
    .hidden-xs { display:none !important; }
    .visible-xs { display:block !important; }
}
</style>

<!-- ══════════════════════════════════════════════
     FIXED QUICK ENQUIRY BUTTON
══════════════════════════════════════════════ -->
<div class="navbar-fixed-bottom">
    <button class="btn-quick-enquiry" dn-toggle="modal" dn-target="#MODAL_QUICK_ENQUIRY">
        <i class="fa-solid fa-paper-plane"></i>
        Quick Enquiry
    </button>
</div>

<!-- ══════════════════════════════════════════════
     BANNER CAROUSEL
══════════════════════════════════════════════ -->
<section class="home_slider">
    <div id="carousel-example-generic" class="carousel slide" data-ride="carousel">
        <ol class="carousel-indicators">
            <li data-target="#carousel-example-generic" data-slide-to="0" class="active"></li>
            <li data-target="#carousel-example-generic" data-slide-to="1"></li>
        </ol>

        <div class="carousel-inner" style="padding:0 !important">

            <!-- SLIDE 1 — Admission -->
            <div class="item active">
                <div class="w3-display-container">
                    <img src="assets/img/banner/admission-image.png" alt="Admission Open GIIT 2024" class="img-responsive" style="width:100%;">

                    <!-- Desktop overlay — Premium GIIT Branding -->
                    <div class="w3-display-bottommiddle w3-center hidden-xs" style="bottom:0!important;width:100%!important;">

                        <!-- College Branding -->
                        <div class="overlay-college-brand">
                            <div>
                                <span class="overlay-college-name">GIIT Professional College</span>
                                <span class="overlay-college-affil">Affiliated to KOLHAN UNIVERSITY &nbsp;|&nbsp; Approved by HRD, Govt. of Jharkhand</span>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="overlay-brand-divider"></div>

                        <!-- Apply Buttons -->
                        <span class="overlay-label">&#9642; Click below to apply for &#9642;</span>
                        <div class="apply-btn-grid">
                            <a href="/applyOnline/register?_crs=BBA"    target="_blank" class="apply-btn highlight"><i class="fa-solid fa-pen-nib" style="font-size:.7rem;margin-right:4px;"></i>BBA</a>
                            <a href="/applyOnline/register?_crs=BCA"    target="_blank" class="apply-btn highlight"><i class="fa-solid fa-pen-nib" style="font-size:.7rem;margin-right:4px;"></i>BCA</a>
                            <a href="/applyOnline/register?_crs=B.Sc-IT" target="_blank" class="apply-btn highlight"><i class="fa-solid fa-pen-nib" style="font-size:.7rem;margin-right:4px;"></i>BSc.IT</a>
                            <a href="/applyOnline/register?_crs=B.Com-H" target="_blank" class="apply-btn highlight"><i class="fa-solid fa-pen-nib" style="font-size:.7rem;margin-right:4px;"></i>B.COM</a>
                            <a href="tel:9117781111" class="btn-call-banner" style="margin-top:0;">
                                <i class="fa-solid fa-phone"></i> Click here to speak
                            </a>
                        </div>

                    </div>
                </div>

                <!-- Mobile strip BELOW image — Premium -->
                <div class="mobile-apply-strip visible-xs">
                    <!-- College branding for mobile -->
                    <div style="text-align:center;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid rgba(79,70,229,.12);">
                        <span style="font-family:'Outfit',sans-serif;font-size:1.1rem;font-weight:900;color:#ce1126;text-transform:uppercase;letter-spacing:.04em;display:block;line-height:1.15;">GIIT Professional College</span>
                        <span style="font-family:'Outfit',sans-serif;font-size:.62rem;font-weight:700;color:#334155;letter-spacing:.03em;display:block;margin-top:3px;text-transform:uppercase;">Affiliated to Kolhan University &nbsp;|&nbsp; Approved by HRD, Govt. of Jharkhand</span>
                    </div>
                    <span class="overlay-label" style="color:var(--primary-dark);">Apply Now</span>
                    <div class="mobile-apply-grid">
                        <a href="/applyOnline/register?_crs=BBA"     target="_blank" class="mobile-apply-btn"><i class="fa-solid fa-pen-nib" style="margin-right:5px;font-size:.75rem;"></i>BBA</a>
                        <a href="/applyOnline/register?_crs=BCA"     target="_blank" class="mobile-apply-btn"><i class="fa-solid fa-pen-nib" style="margin-right:5px;font-size:.75rem;"></i>BCA</a>
                        <a href="/applyOnline/register?_crs=B.Sc-IT" target="_blank" class="mobile-apply-btn"><i class="fa-solid fa-pen-nib" style="margin-right:5px;font-size:.75rem;"></i>BSc.IT</a>
                        <a href="/applyOnline/register?_crs=B.Com-H" target="_blank" class="mobile-apply-btn"><i class="fa-solid fa-pen-nib" style="margin-right:5px;font-size:.75rem;"></i>B.COM</a>
                    </div>
                    <a href="tel:9117781111" class="mobile-call-btn">
                        <i class="fa-solid fa-phone"></i> Click here to speak
                    </a>
                </div>
            </div>

            <!-- SLIDE 2 — Crash Course -->
            <div class="item">
                <div class="w3-display-container">
                    <a href="http://genius.mygiit.com/new/summer-2019.php" target="_blank">
                        <img src="assets/img/banner/banner_crash_course_mobile.jpg" alt="Crash Course GIIT 2024" class="img-responsive" style="width:100%;">
                    </a>
                    <div style="position:absolute;top:16px;right:16px;z-index:2;">
                        <a href="tel:9117781111" class="btn-call-banner" style="margin-top:0;">
                            <i class="fa-solid fa-phone"></i> Call Us
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <a class="left  carousel-control" href="#carousel-example-generic" data-slide="prev">
            <span class="glyphicon glyphicon-chevron-left"></span>
        </a>
        <a class="right carousel-control" href="#carousel-example-generic" data-slide="next">
            <span class="glyphicon glyphicon-chevron-right"></span>
        </a>
    </div>
</section>

<?php require('design.php'); ?>

<!-- ══════════════════════════════════════════════
     TESTIMONIALS
══════════════════════════════════════════════ -->
<section id="SECTION_TESTIMONIAL">
    <div class="page-wrap">
        <div class="testi-section-title">What Our Students Say</div>
        <div class="testi-section-sub">Real stories from real achievers at GIIT Professional College</div>

        <div class="testi-grid">

            <!-- Card 1 -->
            <div class="testi-card">
                <div class="testi-avatar-wrap">
                    <img src="assets/img/testimonial/afreen_bcom.jpg" class="testi-avatar" alt="Afreen Khanam">
                    <div class="testi-meta">
                        <span class="testi-name">Afreen Khanam</span>
                        <span class="testi-course">B.Com (H)</span>
                    </div>
                </div>
                <a href="https://youtu.be/pIHVWBZVg60" target="_blank" class="testi-yt-btn" style="margin-bottom:12px;display:inline-flex;">
                    <i class="fa-brands fa-youtube"></i> Watch Video
                </a>
                <p class="testi-quote">Action speaks louder than words is perfectly true for GIIT Professional College, which has transformed me to a smart performer. GIIT's vibrant and supportive environment motivates everyone to bring their best.</p>
            </div>

            <!-- Card 2 -->
            <div class="testi-card">
                <div class="testi-avatar-wrap">
                    <img src="assets/img/testimonial/akriti_shree_bscit.jpg" class="testi-avatar" alt="Akriti Shree">
                    <div class="testi-meta">
                        <span class="testi-name">Akriti Shree</span>
                        <span class="testi-course">B.Sc.IT</span>
                    </div>
                </div>
                <a href="#" target="_blank" class="testi-yt-btn" style="margin-bottom:12px;display:inline-flex;">
                    <i class="fa-brands fa-youtube"></i> Watch Video
                </a>
                <p class="testi-quote">My capabilities got wings at GIIT. The best part is the innovative and simplified method of teaching backed by extensive practicals, which clarifies basics and builds strong concepts. Weekly tests keep us on our toes.</p>
            </div>

            <!-- Card 3 -->
            <div class="testi-card">
                <div class="testi-avatar-wrap">
                    <img src="assets/img/testimonial/nikita_bscit.jpg" class="testi-avatar" alt="Nikita Kumari">
                    <div class="testi-meta">
                        <span class="testi-name">Nikita Kumari</span>
                        <span class="testi-course">B.Sc.IT</span>
                    </div>
                </div>
                <a href="https://youtu.be/ADuEeU2K83o" target="_blank" class="testi-yt-btn" style="margin-bottom:12px;display:inline-flex;">
                    <i class="fa-brands fa-youtube"></i> Watch Video
                </a>
                <p class="testi-quote">Robust academics, inspiring events, and the "Ready-To-Help" support system have transformed me from an introvert to both academically sound and personally confident. With 92% in 10+2, GIIT was the best decision.</p>
            </div>

            <!-- Card 4 -->
            <div class="testi-card">
                <div class="testi-avatar-wrap">
                    <img src="assets/img/testimonial/deepika_bscit.jpg" class="testi-avatar" alt="Deepika Kumari">
                    <div class="testi-meta">
                        <span class="testi-name">Deepika Kumari</span>
                        <span class="testi-course">B.Sc.IT</span>
                    </div>
                </div>
                <a href="https://youtu.be/_IUQ4yJIjGo" target="_blank" class="testi-yt-btn" style="margin-bottom:12px;display:inline-flex;">
                    <i class="fa-brands fa-youtube"></i> Watch Video
                </a>
                <p class="testi-quote">Positive environment and encouraging words at GIIT are the most motivating factors. GIIT has provided me a stage to re-invent my knowledge and professional abilities with thorough practical exposures and amazing events.</p>
            </div>

        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════
     LINKS SECTION (Courses / Alumni / Student Corner / Announcements)
══════════════════════════════════════════════ -->
<section class="links-section">
    <div class="page-wrap">
        <div class="links-grid">

            <!-- Left 3 cards -->
            <div class="links-left-grid">

                <!-- Courses -->
                <div class="link-card" style="animation-delay:.1s;">
                    <div class="link-card-header">
                        <div class="link-card-icon indigo"><i class="fa-solid fa-graduation-cap"></i></div>
                        <h3 class="link-card-title">Courses</h3>
                    </div>
                    <ul class="link-list">
                        <li><a href="/service/course_structure/pdf/bscit.pdf" target="_blank">B.SC.IT</a></li>
                        <li><a href="/service/course_structure/pdf/bca.pdf"   target="_blank">BCA</a></li>
                        <li><a href="/service/course_structure/pdf/bba.pdf"   target="_blank">BBA</a></li>
                        <li><a href="/service/course_structure/pdf/bcomh.pdf" target="_blank">B.Com (Hons)</a></li>
                        <li><a href="/service/course_structure/pdf/bcomg.pdf" target="_blank">B.Com (Gen)</a></li>
                    </ul>
                </div>

                <!-- Alumni Forum -->
                <div class="link-card" style="animation-delay:.18s;">
                    <div class="link-card-header">
                        <div class="link-card-icon emerald"><i class="fa-solid fa-people-group"></i></div>
                        <h3 class="link-card-title">Alumni Forum</h3>
                    </div>
                    <ul class="link-list">
                        <li><a href="alumni-speak.php">Alumni Speak</a></li>
                        <li><a href="alumni.php">Alumni at a Glance</a></li>
                        <li><a href="alumni-registration.php">Alumni Registration</a></li>
                        <li><a href="alumni-login.php">Alumni Login</a></li>
                        <li><a href="alumni-profile.php">View Your Profile</a></li>
                    </ul>
                </div>

                <!-- Student Corner -->
                <div class="link-card" style="animation-delay:.26s;">
                    <div class="link-card-header">
                        <div class="link-card-icon violet"><i class="fa-solid fa-user-graduate"></i></div>
                        <h3 class="link-card-title">Student Corner</h3>
                    </div>
                    <ul class="link-list">
                        <li><a href="student-login.php">Student Login</a></li>
                        <li><a href="student-profile.php">Update Profile</a></li>
                        <li><a href="student-job-profile.php">Job Profile</a></li>
                        <li><a href="student-assignment.php">Assignments</a></li>
                        <li><a href="student-timetable.php">Class Time Table</a></li>
                        <li><a href="student-examdate.php">Examination Schedule</a></li>
                    </ul>
                </div>

            </div>

            <!-- Announcements (right column) -->
            <div class="announce-card">
                <div class="announce-header">
                    <div class="link-card-icon amber"><i class="fa-solid fa-bullhorn"></i></div>
                    <div>
                        <h3 class="link-card-title" style="font-size:.88rem;">Announcements</h3>
                        <div style="font-size:.68rem;color:var(--muted);font-weight:500;">Latest notices</div>
                    </div>
                </div>
                <div class="treadmill-wrap">
                    <div id="mytreadmill" class="treadmill">
                        <?php
                        $sqlN = "select * from tblnotice order by date DESC limit 0,10";
                        $rQ   = mysqli_query($con, $sqlN);
                        while ($rA = mysqli_fetch_array($rQ)) {
                        ?>
                        <div class="treadmill-unit">
                            <a href="notices.php?nid=<?php echo $rA['id']; ?>"><?php echo $rA['title']; ?></a>
                            <b><?php $dd=$rA['date']; echo date("F j, Y", strtotime($dd)); ?></b>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <a href="notice.php" class="view-all-btn">
                    <i class="fa-solid fa-arrow-right" style="margin-right:5px;"></i>View All Notices
                </a>
            </div>

        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════
     GALLERY
══════════════════════════════════════════════ -->
<section class="gallery-section">
    <div class="page-wrap">
        <div class="section-header">
            <div class="section-badge teal"><i class="fa-solid fa-images"></i></div>
            <div>
                <h2 class="section-title">Gallery</h2>
                <div class="section-sub">Moments captured at GIIT Professional College</div>
            </div>
        </div>
        <div class="gallery-frame">
            <div class="gallery-inner">
                <div class="owl-carousel">
                    <?php
                    $Imgsql = mysqli_query($con, "SELECT * FROM `tblgallery` ORDER BY `date` desc limit 30");
                    while ($ImgRow = mysqli_fetch_array($Imgsql)) {
                    ?>
                    <div class="item">
                        <a href="gallery-details.php?id=<?php echo $ImgRow['categetoryid']; ?>" title="<?php echo htmlspecialchars($ImgRow['title']); ?>">
                            <img src="upload/gallery/thumbs/thumb_<?php echo $ImgRow['image']; ?>" alt="<?php echo htmlspecialchars($ImgRow['title']); ?>" />
                        </a>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════
     QUICK ENQUIRY + MAP
══════════════════════════════════════════════ -->
<section class="enquiry-section">
    <div class="page-wrap">
        <div class="section-header" style="margin-bottom:28px;">
            <div class="section-badge" style="background:rgba(255,255,255,.15);color:#fff;">
                <i class="fa-solid fa-envelope"></i>
            </div>
            <div>
                <h2 class="section-title" style="color:#fff;">Quick Enquiry</h2>
                <div class="section-sub" style="color:rgba(255,255,255,.65);">Fill the form and we'll contact you shortly</div>
            </div>
        </div>

        <div class="enquiry-grid">

            <!-- Form -->
            <div class="form-card">
                <?php
                if (isset($_REQUEST['submit'])) {
                    $name          = $_REQUEST['name'];
                    $email         = $_REQUEST['email'];
                    $phone         = $_REQUEST['phone'];
                    $qualification = $_REQUEST['qualification'];
                    $course        = $_REQUEST['course'];
                    $ip            = $_SERVER['REMOTE_ADDR'];
                    $sql           = "insert into tblenquery (name,email,phone,qualification,course,date,ip,status) values('$name','$email','$phone','$qualification','$course',now(),'$ip','0')";
                    mysqli_query($con, $sql);
                    $succ = "Thank You for your interest! We will contact you soon.";
                }
                ?>
                <h3 class="form-card-title">
                    <i class="fa-solid fa-paper-plane"></i>
                    Send Enquiry
                </h3>

                <form id="enquery" name="enquery" action="" method="post">
                    <?php if (!empty($succ)): ?>
                    <div style="background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.35);border-radius:10px;padding:12px 16px;color:#a7f3d0;font-weight:600;font-size:.85rem;margin-bottom:14px;">
                        <i class="fa-solid fa-circle-check" style="margin-right:6px;"></i><?php echo $succ; ?>
                    </div>
                    <?php endif; ?>

                    <div class="form-group-modern">
                        <label>Your Name</label>
                        <input class="form-control-modern" type="text" name="name" id="name" placeholder="Enter your full name" required />
                    </div>
                    <div class="form-group-modern">
                        <label>Email Address</label>
                        <input class="form-control-modern" type="email" name="email" id="email" placeholder="Enter your email" required />
                    </div>
                    <div class="form-group-modern">
                        <label>Mobile Number</label>
                        <input class="form-control-modern" type="text" name="phone" id="phone" placeholder="Enter mobile number" required />
                    </div>
                    <div class="form-group-modern">
                        <label>Last Qualification</label>
                        <input class="form-control-modern" type="text" name="qualification" id="qualification" placeholder="e.g. 12th, Graduation" required />
                    </div>
                    <div class="form-group-modern">
                        <label>Course Interested In</label>
                        <select class="form-control-modern" name="course" id="course" required>
                            <option value="">Select Course</option>
                            <option value="BBA">BBA</option>
                            <option value="BCA">BCA</option>
                            <option value="BScIT">B.Sc.IT</option>
                            <option value="B Com">B.Com</option>
                            <option value="MBA">MBA</option>
                            <option value="MCA">MCA</option>
                            <option value="PGDCA">PGDCA</option>
                            <option value="ADHCM">ADHCM</option>
                            <option value="BAJM">BAJM</option>
                            <option value="BBARO">BBARO</option>
                            <option value="BCom">BCom</option>
                            <option value="BComIS">BComIS</option>
                            <option value="BScAFD">BScAFD</option>
                            <option value="BScBT">BScBT</option>
                            <option value="BScHCM">BScHCM</option>
                            <option value="BScHIA">BScHIA</option>
                            <option value="BScMIT">BScMIT</option>
                            <option value="BScMLT">BScMLT</option>
                            <option value="BScNT">BScNT</option>
                            <option value="DHCM">DHCM</option>
                            <option value="DHIA">DHIA</option>
                            <option value="DIT">DIT</option>
                            <option value="DMIT">DMIT</option>
                            <option value="DMLT">DMLT</option>
                            <option value="M A">M.A.</option>
                            <option value="M Com">M.Com</option>
                            <option value="MAJM">MAJM</option>
                            <option value="MBA in Finance and Banking">MBA Finance &amp; Banking</option>
                            <option value="MBAHCS">MBAHCS</option>
                            <option value="MScBI">MScBI</option>
                            <option value="MScBT">MScBT</option>
                            <option value="MScCRRA">MScCRRA</option>
                            <option value="MScCS">MScCS</option>
                            <option value="MScIT">MScIT</option>
                            <option value="MScTT">MScTT</option>
                            <option value="PGDBA">PGDBA</option>
                            <option value="PGDBI">PGDBI</option>
                            <option value="PGDBM">PGDBM</option>
                            <option value="PGDCRRA">PGDCRRA</option>
                            <option value="PGDHSM">PGDHSM</option>
                            <option value="PGDIT">PGDIT</option>
                            <option value="PGDTTM">PGDTTM</option>
                        </select>
                    </div>
                    <button type="submit" name="submit" class="btn-submit-modern">
                        <i class="fa-solid fa-paper-plane" style="margin-right:6px;"></i>
                        Submit Enquiry
                    </button>
                </form>
            </div>

            <!-- Map -->
            <div class="map-card">
                <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d14713.057039002882!2d86.17862!3d22.79267!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x77a07daac5d4b336!2sGIIT+Professional+College+-+Professional+College!5e0!3m2!1sen!2sin!4v1491825255000" allowfullscreen loading="lazy" title="GIIT Location"></iframe>
            </div>

        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════
     WELCOME SECTION
══════════════════════════════════════════════ -->
<section class="welcome-section">
    <div class="page-wrap">
        <div class="welcome-header-bar">
            <div class="welcome-icon-box"><i class="fa-solid fa-building-columns"></i></div>
            <h2 class="welcome-heading">Welcome to GIIT Professional College</h2>
        </div>
        <div class="welcome-body">
            <p>GIIT Professional College, affiliated to <strong>KOLHAN UNIVERSITY</strong> and approved by Dept. of HRD, Govt of Jharkhand, is imparting graduation level programmes in IT, Management and Commerce. GIIT Professional College is the first initiative of Genius Intellectuals Initiative Trust (GIIT) established in 2012 by the group of Professionals having 18 years of proven experience in running graduation and post-graduation Education in IT and Management.</p>
            <p>To get employment after graduation is the biggest problem for the students. To resolve this issue, GIIT Professional College has come out with <strong>JOB READY GRADUATION</strong> in B.Sc.IT / BBA / B.Com — a unique way to develop a competent and capable workforce. JOB READY GRADUATION combines University Syllabus with preparation for competitive examinations, skills development training, and extensive practical exposures.</p>
            <p>GIIT Professional College has adopted a <strong>360-degree Educational model</strong>, under which students are taught in technology-enabled classrooms with audio and video tools by experienced faculties. Every year, students get opportunities to participate in presentations, workshops, events, and interviews which develop professional abilities like time management, team spirit, problem-solving, leadership and communication skills.</p>
            <p>GIIT has designed events like <strong>SPANDAN, PRAYAS, DAKSH, GYAN, PRERNA, UDAN, ACADEMIC EXCELLENCE PROGRAM</strong> etc. to nurture professional attitudes. Now, parents need not send their wards outside Jamshedpur — enroll at GIIT for a stronger foundation, better future, and 20-30 campus interview opportunities every year.</p>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════
     JOB READY GRADUATION
══════════════════════════════════════════════ -->
<section class="jobready-section">
    <div class="page-wrap">
        <div class="jobready-card">
            <img src="assets/img/jobready.png" class="jobready-img" alt="Job Ready Graduation" />
            <div class="jobready-content">
                <h2 class="jobready-title">Job Ready Graduation</h2>
                <p class="jobready-sub">Combining University Syllabus with competitive exam prep, skill training &amp; campus placements</p>
                <div class="jobready-courses">
                    <a href="/service/course_structure/pdf/bscit.pdf" target="_blank" class="course-pill">
                        <i class="fa-solid fa-file-pdf"></i> B.Sc.IT
                    </a>
                    <a href="/service/course_structure/pdf/bca.pdf" target="_blank" class="course-pill">
                        <i class="fa-solid fa-file-pdf"></i> BCA
                    </a>
                    <a href="/service/course_structure/pdf/bba.pdf" target="_blank" class="course-pill">
                        <i class="fa-solid fa-file-pdf"></i> BBA
                    </a>
                    <a href="/service/course_structure/pdf/bcomh.pdf" target="_blank" class="course-pill">
                        <i class="fa-solid fa-file-pdf"></i> B.Com (Hons)
                    </a>
                    <a href="/service/course_structure/pdf/bcomg.pdf" target="_blank" class="course-pill">
                        <i class="fa-solid fa-file-pdf"></i> B.Com (Gen)
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include("footer.php"); ?>

<!-- ══════════════════════════════════════════════
     QUICK ENQUIRY MODAL (Redesigned)
══════════════════════════════════════════════ -->
<div class="w3-modal w3-animate-zoom" id="MODAL_QUICK_ENQUIRY" style="display:none;align-items:center;justify-content:center;">
    <div class="w3-modal-inner" style="margin:auto;">

        <div class="modal-header-bar">
            <span class="modal-title-text"><i class="fa-solid fa-paper-plane" style="margin-right:8px;"></i>Quick Enquiry</span>
            <button class="modal-close-btn" dn-dismiss="modal">&times;</button>
        </div>

        <div class="modal-body-wrap">
            <div class="row">
                <div class="col-sm-6">
                    <input type="text"  class="modal-input" placeholder="Your Full Name"   id="ENQUIRY_NAME"    />
                    <input type="email" class="modal-input" placeholder="Email Address"    id="ENQUIRY_EMAIL"   />
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
                        <input type="text" class="modal-input" style="margin-bottom:0;" placeholder="Mobile Number"    maxlength="13" id="ENQUIRY_PHONE_1" />
                        <input type="text" class="modal-input" style="margin-bottom:0;" placeholder="Alternate Number" maxlength="13" id="ENQUIRY_PHONE_2" />
                    </div>
                </div>
                <div class="col-sm-6">
                    <select id="ENQUIRY_LAST_EXAM" class="modal-input">
                        <option value="NULL">Last Exam Passed</option>
                        <?php
                        $query = "SELECT * FROM `last_exam_passed`;";
                        $res   = mysqli_query($con, $query);
                        while ($row = mysqli_fetch_object($res)) {
                            echo "<option value='$row->last_exam_id'>$row->last_exam</option>";
                        }
                        ?>
                    </select>
                    <select id="ENQUIRY_STREAM" class="modal-input">
                        <option value="NULL">Select Stream</option>
                        <?php
                        $query = "SELECT * FROM `12th_stream`;";
                        $res   = mysqli_query($con, $query);
                        while ($row = mysqli_fetch_object($res)) {
                            echo "<option value='$row->streamid'>$row->short_name ($row->stream_name)</option>";
                        }
                        ?>
                    </select>
                    <select class="modal-input" id="ENQUIRY_COURSE">
                        <option value="NULL">Course Interested In</option>
                        <?php
                        $query = "SELECT * FROM `course_master`;";
                        $res   = mysqli_query($con, $query);
                        while ($row = mysqli_fetch_object($res)) {
                            echo "<option value='$row->course_master_id'>$row->course_name ($row->course_short_name)</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="modal-footer-bar">
            <button class="btn-modal-close" dn-dismiss="modal">
                <i class="fa-solid fa-xmark" style="margin-right:5px;"></i>Close
            </button>
            <button class="btn-modal-submit" id="QUICK_ENQUIRY_SUBMIT_BTN">
                Submit <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>

    </div>
</div>

<!-- Ad Video Modal -->
<div class="w3-modal" id="ad_video_modal" style="background:rgba(0,0,0,0.85)!important;">
    <div class="w3-modal-content" style="width:calc(100vmax - 50vmin);height:calc(68vmin)">
        <div class="w3-bar w3-cyan w3-text-white w3-card-4">
            <div class="w3-bar-item w3-xlarge w3-hide-small" style="font-family:'Outfit',sans-serif;font-weight:800;">GIIT PROFESSIONAL COLLEGE</div>
            <div class="w3-bar-item w3-xlarge w3-hide-large w3-hide-medium" style="font-family:'Outfit',sans-serif;font-weight:800;">GIIT</div>
            <div class="w3-bar-item w3-right">
                <span class="fa-solid fa-xmark w3-xxlarge w3-ripple" id="close_ad_video_modal" style="cursor:pointer;padding:12px;"></span>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════════ -->
<script>
window.addEventListener('load', function() {
    jQuery('.slider_right a').click(function() {
        jQuery('body').append('<img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/878468253/?label=PJgNCJ7BoXIQnbnxogM&guid=ON&script=0"/>');
    });
});

$("#close_ad_video_modal").click(function() {
    $("#ad_video_modal").slideUp("fast");
    $("#ad_video_modal").children(".w3-modal-content").remove();
});

$(".slider_arrow").click(function() {
    $(".slider_right").toggleClass('slider_right_show');
    $(".slider_arrow span").toggleClass('fa-angle-double-left fa-angle-double-right');
});

$(document).on("click", "#QUICK_ENQUIRY_SUBMIT_BTN", function() {
    var data = {
        student_name:     ($("#ENQUIRY_NAME").val()     || false),
        email:            ($("#ENQUIRY_EMAIL").val()    || false),
        mobile_1:         ($("#ENQUIRY_PHONE_1").val()  || false),
        mobile_2:         ($("#ENQUIRY_PHONE_2").val()  || false),
        last_qualification:($("#ENQUIRY_LAST_EXAM").val()|| false),
        stream:           ($("#ENQUIRY_STREAM").val()   || false),
        course_intrested: ($("#ENQUIRY_COURSE").val()   || false)
    };
    var k;
    for (k in data) {
        if (!data[k] || data[k] == "NULL") {
            alert_msg("Please fill all fields");
            return;
        }
        data[k] = `'${data[k]}'`;
    }
    if (data.mobile_1.length != 12) {
        alert_msg("Invalid Mobile Number");
        return;
    }
    data = JSON.stringify(data);
    loader_custom_show("Please Wait...");
    $.post("/dn_script/quick_enquiry.php", { POST_DATA: data })
        .done(function(res) {
            loader_custom_hide();
            res = res.trim();
            if (res == "SUCCESS") {
                $("#QUICK_ENQUIRY_SUBMIT_BTN").attr("disabled", "true");
                alert_msg_reload("Thank You for your enquiry. We will contact you soon!");
            } else {
                alert_msg("Service unavailable. Please try again later.");
            }
        })
        .error(function() {
            loader_custom_hide();
            alert_msg("Service unavailable. Please try again later.");
        });
});
</script>
