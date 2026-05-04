<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['s_id'])) {
    header('Location: student_dashboard.php');
    exit;
}

if (isset($_SESSION['facultyLogin'])) {
    header('Location: faculty_portal.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GIITChat | Welcome</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }
        .welcome-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        .welcome-card h1 {
            color: #25d366;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .welcome-card p {
            color: #667781;
            margin-bottom: 30px;
        }
        .portal-links {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .portal-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .student-btn {
            background-color: #34b7f1;
            color: white;
        }
        .student-btn:hover {
            background-color: #29a3d8;
        }
        .faculty-btn {
            background-color: #25d366;
            color: white;
        }
        .faculty-btn:hover {
            background-color: #1ebe57;
        }
        .portal-btn i {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="welcome-card">
        <h1>GIITChat</h1>
        <p>The official messaging platform for GIIT Students and Faculty.</p>
        
        <div class="portal-links">
            <a href="student_dashboard.php" class="portal-btn student-btn">
                <i class="fas fa-user-graduate"></i> Student Portal
            </a>
            <a href="faculty_portal.php" class="portal-btn faculty-btn">
                <i class="fas fa-chalkboard-teacher"></i> Faculty Portal
            </a>
        </div>
    </div>
</body>
</html>
