<?php
session_start();


// 🔒 ตรวจสอบการ logout
if (isset($_GET['logout'])) {
    if (isset($_SESSION['userData']['id'])) {
        require_once '../db.php'; // ให้แน่ใจว่ามีการเชื่อม DB ก่อน

        $userId = $_SESSION['userData']['id'];
        $stmt = $userDb->prepare("UPDATE users SET last_activity = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    }

    session_destroy();
    header('Location: ../login.php');
    exit;
}

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['userData']) || $_SESSION['userData']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$userData = $_SESSION['userData'];
$tournaments = [/* ตัวอย่างข้อมูลหรือ query จาก DB */];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>ผู้ดูแลระบบ | ระบบจัดการการแข่งขัน ROV</title>
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
                    <a href="admin_dashboard.php"><i class="fas fa-home"></i><span>หน้าหลัก</span></a>
                </li>
                <li>
                    <a href="manage_user.php"><i class="fas fa-chart-pie"></i><span>จัดการสิทธิ์ผู้ใช้งาน</span></a>
                </li>
                <li>
                    <a href="admin_view_the_teams.php"><i class="fas fa-users"></i><span>จัดการทีม</span></a>
                </li>
                <li>
                    <a href="https://challonge.com/th/dashboard"><i class="fas fa-calendar-days"></i><span>ตารางการแข่งขัน</span></a>
                </li>
                <li>
                    <a href="../view_tournaments.php"><i class="fas fa-ranking-star"></i><span>ดูทัวร์นาเมนต์</span></a>
                </li>
                <li>
                    <a href="../add_tournament.php"><i class="fas fa-chart-bar"></i><span>จัดการส่งข้อมูลรายชื่อทีม</span></a>
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
                <h2> ROV Tournament</h2>
                <p>จัดการกำหนดการแข่งขันทั้งหมดของทัวร์นาเมนต์</p>
            </div>

            <!-- ส่วนการกรอง -->
            <div class="schedule-filters">
                <button class="filter-button" onclick="window.location.href='admin_edit_schedule.php'">เพิ่มตารางการแข่งขันไปแสดง</button>
                <button class="filter-button" onclick="location.href='../approved_teams.php'">รายชื่อทีมที่อนุมัติ</button>
                <button class="filter-button" onclick="window.location.href='../view_teams.php'">เพิ่มทีมเข้า Tournament </button>
                <button class="filter-button" onclick="window.location.href='challonge_config.php'">จัดการ API</button>
                <button class="filter-button" onclick="window.location.href='competition.php'">กำหนดวันแข่งขัน</button>
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

        // ฟังก์ชันสำหรับกรองรายการแข่งขัน
        document.querySelectorAll('.filter-button').forEach(button => {
            button.addEventListener('click', function() {
                // เอาคลาส active ออกจากปุ่มทั้งหมด
                document.querySelectorAll('.filter-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // เพิ่มคลาส active ให้กับปุ่มที่ถูกคลิก
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                const rounds = document.querySelectorAll('.schedule-round');
                
                rounds.forEach(round => {
                    if (filter === 'all' || round.getAttribute('data-status') === filter) {
                        round.style.display = 'block';
                    } else {
                        round.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>