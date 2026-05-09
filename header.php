<?php
include("includes/configNew.php");
include("includes/config.php");

error_reporting(E_ERROR | E_PARSE);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <?php
    #include("admin/include/class.phpmailer.php");
    $studentid = $_SESSION['student_id'];
    // Setting page select query
    $sql = mysqli_query($con, "SELECT * FROM  tblsetting");
    $Hpage = mysqli_fetch_array($sql);
    ?>
    <title>
        <?= $Hpage["title"]; ?>
    </title>
    <link href="https://fonts.googleapis.com/css?family=Merienda:700" rel="stylesheet">
    <!-- Facebook Pixel Code -->
    <script>
        !function (f, b, e, v, n, t, s) {
            if (f.fbq) return; n = f.fbq = function () {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0';
            n.queue = []; t = b.createElement(e); t.async = !0;
            t.src = v; s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '1670088116415921');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id=1670088116415921&ev=PageView&noscript=1" /></noscript>
    <!-- End Facebook Pixel Code -->

    <meta name="description" content="<?= $Hpage["keywords"]; ?>" />
    <meta name="google-site-verification" content="T36WDfFmZgZS6B5tooNBu_FkECFfosj-_IHijVJmkG8" />
    <meta name="keywords" content="<?= $Hpage["description"]; ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="assets/img/logo.jpg" />
    <!-- <script data-ad-client="ca-pub-3989999643286195" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script> -->
    <?php if (isset($_SESSION['s_id'])) { ?>
        <!-- The core Firebase JS SDK is always required and must be listed first -->
        <script src="https://www.gstatic.com/firebasejs/8.6.7/firebase-app.js"></script>
        <script src="https://www.gstatic.com/firebasejs/8.6.7/firebase-messaging.js"></script>
        <script src="/assets/js/firebase-sdk.js"></script>
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <?php } ?>
    <?php
    $root = $_SERVER["DOCUMENT_ROOT"];
    $cssFiles = array(
        "assets/css/bootstrap.min.css",
        "assets/font-awesome/css/font-awesome.min.css",
        "assets/css/owl.carousel.css",
        "assets/css/style.css",
        "assets/css/style-responsive.css",
        "dn_w3css.css"
    );
    foreach ($cssFiles as $css) {
        $t = filemtime($css);
        echo ("
      <link href='$css?mtime=$t' rel='stylesheet'/>
    ");
    }

    $jsFiles = array("dn_js/jquery.js", "assets/js/bootstrap.min.js");
    foreach ($jsFiles as $js) {
        $t = filemtime($js);
        echo ("
      <script src='$js?mtime=$t' ></script>
    ");
    }
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <style type="text/css">
        :root {
            --primary: #1e1b6e;
            --primary-light: #4f46e5;
            --primary-dark: #16144b;
            --secondary: #cc202e;
            --bg-main: #f5f5ff;
            --card-bg: #ffffff;
            --text-main: #ffffff;
            --text-dark: #0f172a;
            --glass-border: rgba(30, 27, 110, 0.12);
            --shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.08);
            --shadow-md: 0 4px 12px -1px rgb(0 0 0 / 0.12);
            --shadow-lg: 0 12px 28px -4px rgb(30 27 110 / 0.18);
        }

        body {
            font-family: 'Outfit', sans-serif !important;
            -webkit-font-smoothing: antialiased;
            background-color: var(--bg-main);
            padding-top: 165px;
        }

        /* ── Animations ── */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes tickerShimmer {
            0% {
                background-position: -400px 0;
            }

            100% {
                background-position: 400px 0;
            }
        }

        @keyframes dropIn {
            from {
                opacity: 0;
                transform: translateY(-6px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes navUnderline {
            from {
                width: 0;
            }

            to {
                width: 70%;
            }
        }

        /* ── Header 1 — Ticker Bar ── */
        section.header1 {
            top: 0;
            left: 0;
            position: fixed;
            width: 100%;
            height: 38px;
            z-index: 9999;
            background: linear-gradient(135deg, #16144b 0%, #1e1b6e 45%, #4f46e5 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(30, 27, 110, 0.35);
            display: flex;
            align-items: center;
            font-size: 0.82rem;
            font-weight: 500;
            animation: slideDown 0.4s ease-out;
        }

        section.header1 a {
            color: rgba(255, 255, 255, 0.92) !important;
            text-decoration: none;
            transition: opacity 0.2s, letter-spacing 0.2s;
        }

        section.header1 a:hover {
            opacity: 0.8;
            letter-spacing: 0.02em;
        }

        section.header1 marquee {
            font-weight: 600;
            letter-spacing: 0.025em;
        }

        /* Login Panel */
        .h-login-panel {
            position: relative;
            padding: 0 16px;
            display: flex;
            align-items: center;
            height: 100%;
            background: rgba(0, 0, 0, 0.15);
            border-right: 1px solid rgba(255, 255, 255, 0.12);
            cursor: pointer;
            transition: background 0.2s;
        }

        .h-login-panel:hover {
            background: rgba(0, 0, 0, 0.25);
        }

        #loginDropdown {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            position: absolute;
            left: 0;
            top: 38px;
            display: none;
            padding: 8px 0;
            box-shadow: var(--shadow-lg);
            border-radius: 0 0 14px 14px;
            border: 1px solid var(--glass-border);
            min-width: 168px;
            border-top: none;
            animation: dropIn 0.22s ease-out;
        }

        #loginDropdown li {
            list-style: none;
        }

        #loginDropdown a {
            color: var(--text-dark) !important;
            padding: 9px 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 0.82rem;
            transition: all 0.2s ease;
        }

        #loginDropdown a:hover {
            background: var(--bg-main);
            color: var(--primary-light) !important;
            padding-left: 26px;
        }

        /* ── Header 2 — Banner with CTA Buttons ── */
        section.header2 {
            top: 38px;
            position: fixed;
            width: 100%;
            z-index: 9998;
            background: linear-gradient(135deg, #fff9c4 0%, #fff59d 50%, #fff176 100%);
            padding: 6px 0;
            border-bottom: 2px solid rgba(180, 120, 0, 0.15);
            box-shadow: 0 4px 16px rgba(255, 241, 118, 0.4);
            animation: slideDown 0.45s ease-out;
        }

        .header2 .container-fluid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 0 24px;
        }

        .header2_left ul,
        .header2_right ul {
            margin: 0;
            padding: 0;
            list-style: none;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header2_left li {
            color: #3b2500;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
        }

        .header2_left i {
            color: #7c4a00;
        }

        .header2_left a {
            color: #1e1b6e;
            font-weight: 700;
            font-size: 0.8rem;
            text-decoration: none;
            transition: color 0.2s;
        }

        .header2_left a:hover {
            color: #cc202e;
            text-decoration: underline;
        }

        .header2_right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        /* CTA Buttons */
        .header2_right a.w3-round-xxlarge {
            padding: 5px 14px !important;
            font-size: 0.72rem !important;
            font-weight: 700;
            border-radius: 50px !important;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12) !important;
            border: none !important;
            text-decoration: none;
        }

        .header2_right a.w3-round-xxlarge:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.18) !important;
        }

        .w3-red {
            background: linear-gradient(135deg, #f43f5e, #dc2626) !important;
            color: #fff !important;
        }

        .w3-purple {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed) !important;
            color: #fff !important;
        }

        .w3-blue {
            background: linear-gradient(135deg, #38bdf8, #2563eb) !important;
            color: #fff !important;
        }

        .w3-green {
            background: linear-gradient(135deg, #34d399, #059669) !important;
            color: #fff !important;
        }

        .top-menu-links {
            border-left: 2px solid rgba(0, 0, 0, 0.1);
            padding-left: 14px;
            display: flex;
            gap: 14px;
        }

        .top-menu-links a {
            color: #3b2500;
            font-weight: 700;
            text-decoration: none;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            transition: color 0.2s;
        }

        .top-menu-links a:hover {
            color: #cc202e;
        }

        /* ── Header 3 — College Branding ── */
        section.header3 {
            background: #ffffff;
            padding: 10px 0;
            margin: 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            overflow: visible;
            position: fixed;
            top: 88px;
            width: 100%;
            z-index: 9997;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
            animation: slideDown 0.5s ease-out;
        }

        .header3_logo img {
            max-height: 52px;
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .header3_logo img:hover {
            transform: scale(1.06);
        }

        .college-branding {
            text-align: center;
            padding: 2px 0;
        }

        .college-name {
            font-family: 'Bookman Old Style', 'Bookman', 'URW Bookman L', serif !important;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            font-size: 2.1rem;
            font-weight: 900;
            color: #cc202e;
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .college-affiliation {
            display: block;
            font-size: 0.7rem;
            color: #444;
            font-weight: 600;
        }

        /* ── Navbar (mu-menu) ── */
        #mu-menu {
            position: sticky;
            top: 165px;
            z-index: 9996;
        }

        #mu-menu .navbar-default {
            background: #cc202e;
            border: none;
            border-radius: 0;
            margin: 0;
            box-shadow: 0 3px 10px rgba(204, 32, 46, 0.4);
        }

        #mu-menu .container {
            width: 100% !important;
            padding: 0 10px !important;
            max-width: none !important;
        }

        #mu-menu .navbar-header {
            display: none;
        }

        #mu-menu .navbar-nav {
            float: none !important;
            display: flex;
            justify-content: flex-start;
            flex-wrap: nowrap;
            width: 100%;
            overflow-x: auto;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        #mu-menu .navbar-nav::-webkit-scrollbar {
            display: none;
        }

        #mu-menu .navbar-nav>li {
            float: none !important;
            flex-shrink: 0;
            position: relative;
        }

        #mu-menu .navbar-nav>li>a {
            font-family: 'Outfit', sans-serif;
            color: rgba(255, 255, 255, 0.92) !important;
            font-weight: 600;
            font-size: 12px;
            text-transform: none;
            letter-spacing: 0;
            padding: 8px 11px;
            transition: background 0.25s ease, color 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        /* Underline indicator */
        #mu-menu .navbar-nav>li>a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: rgba(255, 255, 255, 0.85);
            border-radius: 3px 3px 0 0;
            transition: width 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform: translateX(-50%);
        }

        #mu-menu .navbar-nav>li>a:hover,
        #mu-menu .navbar-nav>li.active>a {
            background: rgba(0, 0, 0, 0.14) !important;
            color: #fff !important;
        }

        #mu-menu .navbar-nav>li>a:hover::after,
        #mu-menu .navbar-nav>li.active>a::after {
            width: 70%;
        }

        /* Dropdown */
        #mu-menu .dropdown-menu {
            border-radius: 0 0 14px 14px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-lg);
            padding: 8px 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            animation: dropIn 0.2s ease-out;
        }

        #mu-menu .dropdown-menu>li>a {
            padding: 8px 20px;
            font-weight: 600;
            font-size: 12px;
            color: var(--text-dark);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #mu-menu .dropdown-menu>li>a:hover {
            background: var(--bg-main);
            color: var(--primary-light);
            padding-left: 26px;
        }

        .new-result {
            padding: 0;
        }

        .new-result img {
            max-width: 110px;
        }

        /* ── Responsive ── */
        @media (max-width: 991px) {

            .header2_left,
            .top-menu-links {
                display: none;
            }

            .header2 .container-fluid {
                justify-content: center;
            }

            section.header3 {
                top: 80px;
            }

            #mu-menu {
                top: 140px;
            }

            #mu-menu .navbar-header {
                display: block;
            }

            #mu-menu .navbar-nav {
                flex-direction: column;
                overflow-x: visible;
            }

            #mu-menu .navbar-collapse {
                background: white;
                border-radius: 0 0 14px 14px;
                box-shadow: var(--shadow-md);
                padding: 12px;
            }

            .college-name {
                font-size: 0.95rem;
            }

            .college-affiliation {
                font-size: 0.62rem;
                white-space: normal;
            }

            body {
                padding-top: 140px;
            }
        }
    </style>
    <script type="text/javascript">
        $(document).ready(function () {
            $('#loginToggle').click(function () {
                $('#loginDropdown').toggle();
                return false;
            });
        });
    </script>
</head>

<body>
    <section class="header1">
        <?php
        if (isset($_SESSION['s_id']) || isset($_SESSION['adminSession']) || isset($_SESSION['facultyLogin'])) {
            echo '<div class="col-sm-1 text-center"><a style="color:#fff;" href="logmeout.php"><i class="fa fa-user"></i> Logout</a></div>';
        } else {
            echo
                '<div class="col-sm-1 text-center h-login-panel">
      <a id="loginToggle" style="color:#fff;" href="#"><i class="fa fa-user"></i> login</a>
      <ul id="loginDropdown">
        <li><a href="faculty-login.php"><i class="fa fa-user-circle-o"></i> Faculty</a></li>
        <li><a href="student-login.php"><i class="fa fa-user"></i> Student</a></li>
        <li><a href="/refer"><i class="fa fa-paper-plane"></i> Enquiry</a></li>
      </ul>
    </div>';
        }
        ?>
        <div class="col-sm-11">




            <marquee behavior="scroll" direction="left" scrollamount="3">
                <?php
                $plac_query = "SELECT
        `pvm`.`vacancy_name`,
        `pvm`.`vacancy_interview_date`,
        `pvm`.`vacancy_interview_time`,
        `pcm`.`company_name`
         FROM
          `placement_vacancy_master` pvm,
          `placement_company_master` pcm
         WHERE
        `pcm`.`company_id` = `pvm`.`company_id`
           AND `pvm`.`vacancy_status`!='inactive'
           ORDER BY `pvm`.`vacancy_interview_date` DESC LIMIT 5;
    ";
                $sqll = mysqli_query($con, $plac_query);
                while ($rl = mysqli_fetch_object($sqll)) {
                    $pl_st = date("D dS M, Y", strtotime($rl->vacancy_interview_date));
                    echo "<a href='/placement'>Campus Interview by $rl->company_name for $rl->vacancy_name at $rl->vacancy_interview_time on $pl_st at GIIT.</a>";
                }
                ?>
            </marquee>
        </div>
    </section>
    <section class="header2">
        <div class="container-fluid">

            <div class="header2_left">
                <ul>
                    <li><i class="fa fa-phone"></i> <a href="tel:9117781111">9117781111</a>, <a
                            href="tel:9334820937">9334820937</a></li>
                    <li><i class="fa fa-envelope"></i> info@mygiit.com</li>
                </ul>
            </div>

            <div class="header2_right">

                <ul>
                    <li>
                        <a href="applyOnline"
                            class="w3-round-xxlarge w3-hover-shadow w3-card-4 w3-red w3-ripple w3-border w3-border-white w3-large"
                            target="_blank">
                            <i class="fa fa-paper-plane"></i> Apply Online
                        </a>
                    </li>
                    <li>
                        <a class="w3-hover-shadow w3-card-4 w3-ripple w3-round-xxlarge w3-purple w3-text-white w3-border w3-border-white w3-large"
                            href="http://genius.mygiit.com" target="_blank">
                            <i class="fa fa-graduation-cap"></i> Summer Internship
                        </a>
                    </li>
                    <li>
                        <a class="w3-hover-shadow w3-card-4 w3-ripple w3-round-xxlarge w3-blue w3-text-white w3-border w3-border-white w3-large"
                            href="https://mygiit.com/placement" target="_blank">
                            <i class="fa fa-handshake-o"></i> Placements
                        </a>
                    </li>
                    <li>
                        <a class="w3-hover-shadow w3-card-4 w3-ripple w3-round-xxlarge w3-green w3-border w3-border-white w3-large"
                            href="/fee">
                            <i class="fa fa-paypal"></i> Pay Fee Online
                        </a>
                    </li>
                </ul>


                <ul class="top-menu-links">
                    <?php
                    $sqll = mysqli_query($con, "select * from tbl_top_menu where status='1' order by id DESC");
                    $Links = '';
                    while ($rl = mysqli_fetch_array($sqll)) {
                        ?>
                        <li><a href="<?= $rl['link']; ?>">
                                <?= $rl['title']; ?>
                            </a></li>
                        <?php
                    }
                    /*
                    <li><a href="#">BOB-MANIPAL-PO</a></li>
                      <li><a href="#">RBI-OFFICER GR-B</a></li>
                      <li><a href="#">CWE PO-2016</a></li>
                      <li><a href="#">MOCK TEST</a></li>
                      <li><a href="#">On line Test Series</a></li>
                      <li><a href="#">Login</a></li>
                    */
                    ?>
                </ul>
            </div>
        </div>
    </section>
    <section class="header3">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="college-branding">
                        <div class="college-name">
                            <span>GIIT</span>
                            <a href="/">
                                <img src="assets/img/logo.jpg" alt="GIIT" style="max-height: 65px;" />
                            </a>
                            <span>PROFESSIONAL COLLEGE</span>
                        </div>
                        <span class="college-affiliation">Affiliated to KOLHAN UNIVERSITY Approved by HRD, Govt. of
                            Jharkhand</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="mu-menu">
        <nav class="navbar navbar-default" role="navigation">
            <div class="container">
                <div class="navbar-header">
                    <!-- FOR MOBILE VIEW COLLAPSED BUTTON -->
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar"
                        aria-expanded="false" aria-controls="navbar"> <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span>
                    </button>
                    <!-- LOGO REMOVED FOR CLEANER LOOK -->
                </div>
                <div id="navbar" class="navbar-collapse collapse">
                    <ul class="nav navbar-nav">
                        <li class="active"><a href="/"><i class="fa fa-home"></i></a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact-us.php">Contact Us</a></li>
                        <li><a href="notice.php">Notice</a></li>
                        <li><a href="placements.php">Placements</a></li>
                        <li class="dropdown"> <a href="awards.php">Awards</a> <span class="caret dropdown-toggle"
                                data-toggle="dropdown"></span>
                            <ul class="dropdown-menu">
                                <?php
                                $sql = mysqli_query($con, "select id,title,date from tblaward_cat where status='1' order by date desc");
                                while ($row = mysqli_fetch_array($sql)) {
                                    ?>
                                    <li><a href="view.php?aw=<?php echo $row['id']; ?>"><?php echo $row['title']; ?></a>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                        </li>
                        <li><a href="gallery.php">Gallery</a></li>
                        <li><a href="#" class="dropdown-toggle" data-toggle="dropdown">Videos<span
                                    class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <?php
                                $sql = "SELECT `video_type` .* FROM `video_type` WHERE `video_type_status`='active' ORDER BY video_type_id ASC";
                                $res = mysqli_query($con, $sql);
                                while ($row = mysqli_fetch_array($res)) {
                                    ?>
                                    <li><a
                                            href="video-admin.php?ids=<?php echo $row['video_type_id']; ?>"><?php echo $row['video_type_name']; ?></a>
                                    </li>
                                    <?php
                                }
                                ?>

                            </ul>
                        </li>
                        <li><a href="press.php">Press</a></li>
                        <li><a href="#" class="dropdown-toggle" data-toggle="dropdown">Download <span
                                    class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <li><a href="#" class="has-submenu">Time Table <span class="caret"></span></a>
                                    <ul class="dropdown-menu">
                                        <?php
                                        $sql = mysqli_query($con, "select id,title,date from tbl_download_page where status='1' order by date desc");
                                        while ($row = mysqli_fetch_array($sql)) {
                                            ?>
                                            <li><a
                                                    href="time-table.php?ttid=<?php echo base64_encode($row['id']); ?>"><?php echo $row['title']; ?></a>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                    </ul>
                                </li>
                                <li><a href="smu-form.php">Form/Application</a></li>
                                <li><a href="#" class="has-submenu" data-toggle="dropdown">Assignments <span
                                            class="caret"></span></a>
                                    <ul class="dropdown-menu">
                                        <?php
                                        $sql = mysqli_query($con, "select id,title,date from tbl_assignments_page where status='1' order by date desc");
                                        while ($row = mysqli_fetch_array($sql)) {
                                            ?>
                                            <li><a
                                                    href="assignments.php?asid=<?php echo base64_encode($row['id']); ?>"><?php echo $row['title']; ?></a>
                                            </li>
                                            <?php
                                        }
                                        /*if(isset($_SESSION['adm_assignment']) || isset($_SESSION['s_id'])){
                                  $courses = mysqli_query($con,"SELECT * FROM `course_master` WHERE `course_end_year` = '0000'");
                                  while($course = mysqli_fetch_array($courses)){
                                    echo '<li><a href="download-assignment.php?course='.$course['course_master_id'].'">'.$course['course_name'].'</a></li>';
                                  }
                                } else {
                                  $sql=mysqli_query($con,"select id,title,date from tbl_assignments_page where status='1' order by date desc");
                                  while($row=mysqli_fetch_array($sql)) {
                                    ?>
                                                <li><a href="assignments.php?asid=<?php echo base64_encode($row['id']); ?>"><?php echo $row['title'];  ?></a></li>
                                                <?php
                                  }
                                }*/
                                        ?>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <li><a href="student-project.php">Project</a></li>
                        <li><a href="#" class="dropdown-toggle" data-toggle="dropdown">Student <span
                                    class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <li><a href="student-forum.php">Student Forum</a></li>
                                <?php
                                if (isset($_SESSION['s_id'])) {
                                    ?>
                                    <li><a href="student-dashboard.php">Student Dashboard</a></li>
                                    <li><a href="student-assignment.php">Assignments</a> </li>
                                    <li><a href="student-profile.php">Update Profile</a></li>
                                    <?php
                                } else {
                                    ?>
                                    <li><a href="student-login.php">Student Login</a></li>
                                    <?php
                                }
                                ?>
                            </ul>
                        </li>
                        <li><a href="#" class="dropdown-toggle" data-toggle="dropdown">Alumni <span
                                    class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <li><a href="alumni-speak.php">Alumni Speaks</a></li>
                                <li><a href="alumni.php">Alumni At a Glance</a></li>
                                <li><a href="alumni-login.php">Alumni Login</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <!--/.nav-collapse -->
            </div>
        </nav>
    </section>
    <!-- Start menu -->