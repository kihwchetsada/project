<?php
require 'db_connect.php'; // เชื่อมต่อฐานข้อมูล

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
    <title>ตารางการแข่งขัน | ROV Tournament Hub</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/schedule.css">
</head>
<body>
    <div class="particles">
        <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="left: 20%; animation-delay: 2s;"></div>
        <div class="particle" style="left: 30%; animation-delay: 4s;"></div>
        <div class="particle" style="left: 40%; animation-delay: 6s;"></div>
        <div class="particle" style="left: 50%; animation-delay: 8s;"></div>
        <div class="particle" style="left: 60%; animation-delay: 10s;"></div>
        <div class="particle" style="left: 70%; animation-delay: 12s;"></div>
        <div class="particle" style="left: 80%; animation-delay: 14s;"></div>
        <div class="particle" style="left: 90%; animation-delay: 16s;"></div>
    </div>

    <div class="navbar">
        <div class="logo">
            <img src="img/logo.jpg" alt="ROV Tournament Hub Logo">
            <h2>ROV Tournament Hub</h2>
        </div>
        <nav>
            <a href="index.php">หน้าหลัก</a>
            <a href="schedule.php">ตารางการแข่งขัน</a>
            <a href="register_user.php">สมัครทีม</a>
            <a href="annunciate.php">ประกาศ</a>
            <a href="contact.php">ติดต่อเรา</a>
            <a href="login.php">เข้าสู่ระบบ</a>
        </nav>
    </div>

    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-trophy"></i>
                ตารางการแข่งขัน
            </h1>
            <p class="page-subtitle">
                ผลการแข่งขันทุกรุ่นอายุได้ที่นี่
            </p>
        </div>

        <div class="tournament-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-child age-icon"></i>
                    รุ่นอายุไม่เกิน 18 ปี
                    <i class="fas fa-gamepad"></i>
                </h2>
            </div>
            <div class="iframe-wrapper">
                <div class="iframe-container">
                    <?php if (!empty($iframes['under_18'])): ?>
                        
                        <?php echo stripslashes($iframes['under_18']); ?>
                        <?php else: ?>
                        <div class="no-schedule">
                            <i class="fas fa-calendar-times"></i>
                            <p>ยังไม่มีตารางการแข่งขันสำหรับรุ่นอายุไม่เกิน 18 ปี</p>
                            <p>โปรดติดตามประกาศเพิ่มเติม</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tournament-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-user-graduate age-icon"></i>
                    รุ่นอายุตั้งแต่ 18 ปีขึ้นไป
                    <i class="fas fa-medal"></i>
                </h2>
            </div>
            <div class="iframe-wrapper">
                <div class="iframe-container">
                    <?php if (!empty($iframes['above_18'])): ?>

                        <?php echo stripslashes($iframes['above_18']); ?>
                        <?php else: ?>
                        <div class="no-schedule">
                            <i class="fas fa-calendar-times"></i>
                            <p>ยังไม่มีตารางการแข่งขันสำหรับรุ่นอายุตั้งแต่ 18 ปีขึ้นไป</p>
                            <p>โปรดติดตามประกาศเพิ่มเติม</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <script>
        // เอฟเฟกต์พื้นหลังเคลื่อนไหว
        document.addEventListener('DOMContentLoaded', function() {
            // เพิ่ม particles เพิ่มเติม
            const particlesContainer = document.querySelector('.particles');
            
            setInterval(() => {
                if (particlesContainer.children.length < 20) {
                    const particle = document.createElement('div');
                    particle.className = 'particle';
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.animationDelay = '0s';
                    particle.style.animationDuration = (Math.random() * 10 + 15) + 's';
                    particlesContainer.appendChild(particle);
                    
                    // ลบ particle หลังจากจบ animation
                    setTimeout(() => {
                        if (particle.parentNode) {
                            particle.remove();
                        }
                    }, 25000);
                }
            }, 2000);
        });

        // Smooth scrolling สำหรับลิงก์
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // เอฟเฟกต์ hover สำหรับ iframe wrapper
        document.querySelectorAll('.iframe-wrapper').forEach(wrapper => {
            wrapper.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            wrapper.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>