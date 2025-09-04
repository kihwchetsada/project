<?php
session_start();

// 🔒 ตรวจสอบการ logout
if (isset($_GET['logout'])) {
    if (isset($_SESSION['userData']['id'])) {
        require_once '../db.php';
        $userId = $_SESSION['userData']['id'];
        $stmt = $userDb->prepare("UPDATE users SET last_activity = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    }
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['userData']) || $_SESSION['userData']['role'] !== 'participant') {
    header('Location: ../login.php');
    exit;
}

$userData = $_SESSION['userData'];

require '../db_connect.php';

// ดึงข้อมูล iframe URLs
$stmt = $conn->query("SELECT category, iframe_url FROM tournament_links");
$iframes = [];
while ($row = $stmt->fetch()) {
    $iframes[$row['category']] = $row['iframe_url'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผู้เข้าร่วมการแข่งขัน | ระบบจัดการการแข่งขัน ROV</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --background-color: #f4f7f6;
            --sidebar-bg: #2c3e50;
            --sidebar-text: #ecf0f1;
            --sidebar-active-bg: #34495e;
            --card-bg: #ffffff;
            --text-color: #333;
            --border-color: #e0e0e0;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #3e5771;
        }

        .sidebar-header .logo {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .sidebar-header .logo i {
            margin-right: 10px;
        }

        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--sidebar-text);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .sidebar-menu ul {
            list-style: none;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .sidebar-menu li a i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background-color: var(--sidebar-active-bg);
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
        }

        .top-navbar {
            background: var(--card-bg);
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info span {
            margin-right: 15px;
            font-weight: 500;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
        }
        
        .dashboard-container {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .welcome-header {
            margin-bottom: 20px;
        }

        .schedule-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-button {
            background: #e9ecef;
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-family: 'Kanit', sans-serif;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .filter-button.active, .filter-button:hover {
            background: var(--primary-color);
            color: white;
        }

        .tournament-tabs {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 20px;
        }

        .tournament-tab {
            padding: 10px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-family: 'Kanit', sans-serif;
            font-size: 1rem;
            position: relative;
            color: #7f8c8d;
        }

        .tournament-tab.active {
            color: var(--primary-color);
            font-weight: 500;
        }

        .tournament-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--primary-color);
        }

        .loading-spinner, .no-iframe-message, .placeholder-content {
            text-align: center;
            padding: 50px 20px;
            background: #fdfdfd;
            border: 1px dashed var(--border-color);
            border-radius: 8px;
        }
        
        .loading-spinner .fa-spinner {
            font-size: 2.5em;
            color: var(--primary-color);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .tournament-iframe {
            width: 100%;
            height: 70vh; /* Adjust height as needed */
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }

        .content-section {
            display: none;
        }
        .content-section.active {
            display: block;
        }
        
        .tournament-page {
            display: none;
        }
        .tournament-page.active {
            display: block;
        }
        
        .dashboard-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        /* 📱 MEDIA QUERIES FOR MOBILE */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: 0 0 15px rgba(0,0,0,0.2);
            }

            .sidebar.sidebar-active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 10px;
            }

            .mobile-toggle {
                display: block;
            }
            
            .top-navbar {
                justify-content: space-between;
            }

            /* This moves the hamburger menu to the top-nav bar on mobile */
            .top-navbar .mobile-toggle {
                order: -1; /* Place it at the start */
                color: var(--secondary-color);
                font-size: 1.2rem;
            }

            .welcome-header h2 {
                font-size: 1.5rem;
            }

            .schedule-filters, .tournament-tabs {
                justify-content: center;
            }
        }
    </style>
    </head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-trophy"></i>
                <span>ROV Tournament</span>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <ul>
                <li>
                    <a href="participant_dashboard.php" class="active"><i class="fas fa-home"></i><span>หน้าหลัก</span></a>
                </li>
                <li>
                    <a href="results.php"><i class="fas fa-ranking-star"></i><span>ผลการแข่งขัน</span></a>
                </li>
                <li>
                    <a href="stats.php"><i class="fas fa-chart-bar"></i><span>สถิติ</span></a>
                </li>
                <li>
                    <a href="settings.php"><i class="fas fa-cog"></i><span>ตั้งค่า</span></a>
                </li>
                <li>
                    <a href="?logout=1"><i class="fas fa-sign-out-alt"></i><span>ออกจากระบบ</span></a>
                </li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <div class="top-navbar">
            <button class="mobile-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="user-menu">
                <div class="user-info">
                    <?php // include 'header.php'; // Consider including header content directly or ensure it's mobile-friendly ?>
                    <span><?php echo htmlspecialchars($userData['username']); ?></span>
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-container">
            <div class="welcome-header">
                <h2>ROV Tournament</h2>
                <p>จัดการกำหนดการแข่งขันทั้งหมดของทัวร์นาเมนต์</p>
            </div>

            <div class="schedule-filters">
                <button class="filter-button active" data-filter="tournaments" onclick="showContent('tournaments')">
                    <i class="fas fa-calendar-alt"></i> ตารางการแข่งขัน
                </button>
                <button class="filter-button" data-filter="approved" onclick="showContent('approved')">
                    <i class="fas fa-check-circle"></i> สมัครทีมเข้าร่วม
                </button>
                <button class="filter-button" data-filter="upcoming" onclick="showContent('upcoming')">
                    <i class="fas fa-clock"></i> กำลังจะมาถึง
                </button>
                <button class="filter-button" data-filter="pending" onclick="showContent('pending')">
                    <i class="fas fa-hourglass-half"></i> ทีมที่รออนุมัติ
                </button>
            </div>

            <div id="content-tournaments" class="content-section active">
                <div class="tournament-container">
                    <div class="tournament-tabs">
                        <button class="tournament-tab active" onclick="switchTournament(event, 'above_18')">
                            <i class="fas fa-users"></i> รุ่นอายุ 18 ปีขึ้นไป
                        </button>
                        <button class="tournament-tab" onclick="switchTournament(event, 'under_18')">
                            <i class="fas fa-user-friends"></i> รุ่นอายุต่ำกว่า 18 ปี
                        </button>
                    </div>

                    <div id="tournament-above_18" class="tournament-page active">
                        <h3><i class="fas fa-trophy"></i> ตารางการแข่งขันรุ่นอายุ 18 ปีขึ้นไป</h3>
                        <?php if (!empty($iframes['above_18'])): ?>
                            <div id="loading-above-18" class="loading-spinner">
                                <i class="fas fa-spinner"></i>
                                <p>กำลังโหลดตารางการแข่งขัน...</p>
                            </div>
                            <iframe 
                                class="tournament-iframe"
                                src="<?= htmlspecialchars($iframes['above_18']) ?>" 
                                onload="document.getElementById('loading-above-18').style.display='none'"
                                title="ตารางการแข่งขันรุ่นอายุตั้งแต่ 18 ปีขึ้นไป">
                            </iframe>
                        <?php else: ?>
                            <div class="no-iframe-message">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2em; margin-bottom: 10px;"></i>
                                <p>ยังไม่มีตารางการแข่งขันสำหรับรุ่นอายุ 18 ปีขึ้นไป</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="tournament-under_18" class="tournament-page">
                        <h3><i class="fas fa-trophy"></i> ตารางการแข่งขันรุ่นอายุต่ำกว่า 18 ปี</h3>
                        <?php if (!empty($iframes['under_18'])): ?>
                            <div id="loading-under-18" class="loading-spinner">
                                <i class="fas fa-spinner"></i>
                                <p>กำลังโหลดตารางการแข่งขัน...</p>
                            </div>
                            <iframe 
                                class="tournament-iframe"
                                src="<?= htmlspecialchars($iframes['under_18']) ?>" 
                                onload="document.getElementById('loading-under-18').style.display='none'"
                                title="ตารางการแข่งขันรุ่นอายุต่ำกว่า 18 ปี">
                            </iframe>
                        <?php else: ?>
                            <div class="no-iframe-message">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2em; margin-bottom: 10px;"></i>
                                <p>ยังไม่มีตารางการแข่งขันสำหรับรุ่นอายุต่ำกว่า 18 ปี</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="content-approved" class="content-section">
                <div class="placeholder-content">
                    <h3><i class="fas fa-check-circle"></i> สมัครทีมเข้าร่วมการแข่งขัน</h3>
                    <p>คุณสามารถสมัครเข้าร่วมการแข่งขันได้ที่นี่</p>
                    <a href="../register.php" style="display:inline-block; margin-top:10px; padding:10px 20px; background:var(--primary-color); color:white; text-decoration:none; border-radius:5px;">คลิกที่นี่เพื่อสมัคร</a>
                </div>
            </div>

            <div id="content-upcoming" class="content-section">
                <div class="placeholder-content">
                    <h3><i class="fas fa-clock"></i> การแข่งขันที่กำลังจะมาถึง</h3>
                    <p>ส่วนนี้จะแสดงการแข่งขันที่กำลังจะมาถึง</p>
                </div>
            </div>

            <div id="content-pending" class="content-section">
                <div class="placeholder-content">
                    <h3><i class="fas fa-hourglass-half"></i> รายชื่อทีมที่รออนุมัติ</h3>
                    <p>ส่วนนี้จะแสดงรายชื่อทีมที่รออนุมัติ</p>
                </div>
            </div>
            
        </div>
        <div class="dashboard-footer">
            <p>&copy; <?php echo date('Y'); ?> ระบบจัดการการแข่งขัน ROV. สงวนลิขสิทธิ์</p>
        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mainContent = document.querySelector('.main-content');

        // Toggle sidebar on mobile
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('sidebar-active');
        });

        // ปิด sidebar เมื่อคลิกที่เนื้อหาหลักในโหมดมือถือ
        mainContent.addEventListener('click', function() {
            if (sidebar.classList.contains('sidebar-active')) {
                sidebar.classList.remove('sidebar-active');
            }
        });

        // ฟังก์ชันสำหรับสลับเนื้อหาหลัก
        function showContent(contentType) {
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById('content-' + contentType).classList.add('active');
            
            document.querySelectorAll('.filter-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-filter="${contentType}"]`).classList.add('active');
        }

        // ฟังก์ชันสำหรับสลับระหว่างตารางการแข่งขัน
        function switchTournament(event, category) {
            document.querySelectorAll('.tournament-page').forEach(page => {
                page.classList.remove('active');
            });
            document.getElementById('tournament-' + category).classList.add('active');
            
            document.querySelectorAll('.tournament-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>