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
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>ผู้เข้าร่วมการแข่งขัน | ระบบจัดการการแข่งขัน ROV</title>
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-trophy"></i>
                <span>ROV Tournament</span>
            </div>
            <button class="mobile-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="sidebar-menu">
            <ul>
                <li>
                    <a href="participant_dashboard.php"><i class="fas fa-home"></i><span>หน้าหลัก</span></a>
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

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <div class="top-navbar">
            <div class="user-menu">
                <div class="user-info">
                    <?php include 'header.php'; ?>
                    <span><?php echo htmlspecialchars($userData['username']); ?></span>
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <div class="welcome-header">
                <h2>ROV Tournament</h2>
                <p>จัดการกำหนดการแข่งขันทั้งหมดของทัวร์นาเมนต์</p>
            </div>

            <!-- ส่วนการกรอง -->
            <div class="schedule-filters">
                <button class="filter-button active" data-filter="tournaments" onclick="showContent('tournaments')">
                    <i class="fas fa-calendar-alt"></i> ตารางการแข่งขัน
                </button>
                <button class="filter-button" data-filter="approved" onclick="showContent('approved')">
                    <i class="fas fa-check-circle"></i> สมัครทีมเข้าร่วมการแข่งขัน
                </button>
                <button class="filter-button" data-filter="upcoming" onclick="showContent('upcoming')">
                    <i class="fas fa-clock"></i> กำลังจะมาถึง
                </button>
                <button class="filter-button" data-filter="pending" onclick="showContent('pending')">
                    <i class="fas fa-hourglass-half"></i> รายชื่อทีมที่รออนุมัติ
                </button>
            </div>

            <!-- เนื้อหาหลัก -->
            <div id="content-tournaments" class="content-section active">
                <div class="tournament-container">
                    <!-- Tabs สำหรับสลับระหว่างรุ่นอายุ -->
                    <div class="tournament-tabs">
                        <button class="tournament-tab active" onclick="switchTournament('above_18')">
                            <i class="fas fa-users"></i> รุ่นอายุ 18 ปีขึ้นไป
                        </button>
                        <button class="tournament-tab" onclick="switchTournament('under_18')">
                            <i class="fas fa-user-friends"></i> รุ่นอายุต่ำกว่า 18 ปี
                        </button>
                    </div>

                    <!-- ตารางรุ่น 18 ปีขึ้นไป -->
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

                    <!-- ตารางรุ่นต่ำกว่า 18 ปี -->
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

            <!-- เนื้อหาอื่นๆ (placeholder) -->
            <div id="content-approved" class="content-section">
                <div class="placeholder-content">
                    <h3><i class="fas fa-check-circle"></i> สมัครทีมเข้าร่วมการแข่งขัน</h3>
                    <a href="../register.php">คลิกที่นี่เพื่อสมัคร</a>
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

            <!-- Footer -->
            <div class="dashboard-footer">
                <p>&copy; <?php echo date('Y'); ?> ระบบจัดการการแข่งขัน ROV. สงวนลิขสิทธิ์</p>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('sidebar-active');
        });

        // ปิด sidebar เมื่อคลิกที่เนื้อหาหลักในโหมดมือถือ
        document.querySelector('.main-content').addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                document.querySelector('.sidebar').classList.remove('sidebar-active');
            }
        });

        // ฟังก์ชันสำหรับสลับเนื้อหาหลัก
        function showContent(contentType) {
            // ซ่อนเนื้อหาทั้งหมด
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // แสดงเนื้อหาที่เลือก
            document.getElementById('content-' + contentType).classList.add('active');
            
            // อัพเดทปุ่ม active
            document.querySelectorAll('.filter-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector('[data-filter="' + contentType + '"]').classList.add('active');
        }

        // ฟังก์ชันสำหรับสลับระหว่างตารางการแข่งขัน
        function switchTournament(category) {
            // ซ่อนตารางทั้งหมด
            document.querySelectorAll('.tournament-page').forEach(page => {
                page.classList.remove('active');
            });
            
            // แสดงตารางที่เลือก
            document.getElementById('tournament-' + category).classList.add('active');
            
            // อัพเดท tab active
            document.querySelectorAll('.tournament-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // เพิ่ม CSS สำหรับ content sections
        const style = document.createElement('style');
        style.textContent = `
            .content-section {
                display: none;
            }
            .content-section.active {
                display: block;
            }
            .placeholder-content {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                text-align: center;
                color: #7f8c8d;
            }
            .placeholder-content h3 {
                color: #2c3e50;
                margin-bottom: 15px;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>