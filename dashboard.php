<?php
session_start();

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>ผู้จัดการแข่งขัน | ระบบจัดการการแข่งขัน ROV</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link rel="stylesheet" href="css/dashboard.css">
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
                    <a href="dashboard.php"><i class="fas fa-home"></i><span>หน้าหลัก</span></a>
                </li>
                <li>
                    <a href="view_img.php"><i class="fas fa-users"></i><span>จัดการทีม</span></a>
                </li>
                <li>
                    <a href="https://challonge.com/th/rmuti_5/participants"><i class="fas fa-calendar-days"></i><span>ตารางการแข่งขัน</span></a>
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
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="ค้นหาการแข่งขัน...">
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="badge">5</span>
                </div>
                <div class="user-info">
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
                <button class="filter-button active" data-filter="all">ทั้งหมด</button>
                <button class="filter-button" data-filter="completed">เสร็จสิ้นแล้ว</button>
                <button class="filter-button" data-filter="upcoming">กำลังจะมาถึง</button>
                <button class="filter-button" data-filter="pending">รอดำเนินการ</button>
                
                <button class="btn-add-round">
                    <i class="fas fa-plus"></i> เพิ่มรอบการแข่งขันใหม่
                </button>
            </div>

            <!-- ตารางการแข่งขันตามรอบ -->
            <div class="schedule-container">
                <?php foreach ($tournaments as $tournament): ?>
                <div class="schedule-round" data-status="<?php echo $tournament['status']; ?>">
                    <div class="round-header">
                        <div class="round-title"><?php echo htmlspecialchars($tournament['round']); ?></div>
                        <div>
                            <span class="round-badge badge-<?php echo $tournament['status']; ?>">
                                <?php 
                                if ($tournament['status'] == 'completed') echo 'เสร็จสิ้น';
                                elseif ($tournament['status'] == 'upcoming') echo 'กำลังจะมาถึง';
                                else echo 'รอดำเนินการ';
                                ?>
                            </span>
                            <button class="btn-add-match">
                                <i class="fas fa-plus"></i> เพิ่มการแข่งขัน
                            </button>
                        </div>
                    </div>
                    
                    <?php if (empty($tournament['matches'])): ?>
                    <div class="empty-round">
                        <p>ยังไม่มีการแข่งขันในรอบนี้</p>
                    </div>
                    <?php else: ?>
                    <table class="match-table">
                        <thead>
                            <tr>
                                <th>รหัสแมตช์</th>
                                <th>ทีม</th>
                                <th>วันที่ & เวลา</th>
                                <th>สถานที่</th>
                                <th>ผลการแข่งขัน</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tournament['matches'] as $match): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($match['match_id']); ?></td>
                                <td>
                                    <div class="match-teams">
                                        <span><?php echo htmlspecialchars($match['team1']); ?></span>
                                        <span class="team-vs">VS</span>
                                        <span><?php echo htmlspecialchars($match['team2']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($match['date']); ?> | <?php echo htmlspecialchars($match['time']); ?></td>
                                <td><?php echo htmlspecialchars($match['venue']); ?></td>
                                <td><?php echo htmlspecialchars($match['result']); ?></td>
                                <td>
                                    <div class="match-actions">
                                        <a href="#" class="btn-action btn-edit">
                                            <i class="fas fa-edit"></i> แก้ไข
                                        </a>
                                        <?php if ($tournament['status'] == 'completed'): ?>
                                        <a href="<?php echo htmlspecialchars($match['stream']); ?>" target="_blank" class="btn-action btn-watch">
                                            <i class="fas fa-play"></i> ดูย้อนหลัง
                                        </a>
                                        <?php endif; ?>
                                        <a href="#" class="btn-action btn-delete">
                                            <i class="fas fa-trash"></i> ลบ
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
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