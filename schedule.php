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
</head>
<style>
    :root {
        --primary-color: #2e91ca;
        --secondary-color: #8e44ad;
        --accent-color: #e74c3c;
        --dark-bg: #0a0e17;
        --card-bg: #1a1e2e;
        --light-text: #ffffff;
        --nav-hover: #2980b9;
        --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Kanit', sans-serif;
        background: linear-gradient(135deg, #0c0c0c 0%, #1a1a2e 50%, #16213e 100%);
        min-height: 100vh;
        color: var(--light-text);
        overflow-x: hidden;
    }

    /* Animated background particles */
    .particles {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1;
    }

    .particle {
        position: absolute;
        width: 4px;
        height: 4px;
        background: rgba(46, 145, 202, 0.3);
        border-radius: 50%;
        animation: float 20s infinite linear;
    }

    @keyframes float {
        0% {
            transform: translateY(100vh) rotate(0deg);
            opacity: 0;
        }
        10% {
            opacity: 1;
        }
        90% {
            opacity: 1;
        }
        100% {
            transform: translateY(-100vh) rotate(360deg);
            opacity: 0;
        }
    }

    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 5%;
        background: rgba(10, 14, 23, 0.95);
        backdrop-filter: blur(20px);
        border-bottom: 2px solid var(--primary-color);
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
    }

    .logo {
        display: flex;
        align-items: center;
    }

    .logo img {
        width: 60px;
        height: 60px;
        border-radius: 30px;
        object-fit: cover;
        border: 3px solid var(--primary-color);
        margin-right: 15px;
        box-shadow: 0 0 20px rgba(52, 152, 219, 0.8);
        transition: all 0.5s ease;
    }

    .logo img:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 0 30px rgba(41, 45, 244, 1);
    }

    .logo h2 {
        color: var(--primary-color);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        text-shadow: 0 0 15px rgba(46, 145, 202, 0.6);
        transition: all 0.3s ease;
    }

    .logo:hover h2 {
        color: #ffffff;
        text-shadow: 0 0 20px rgba(46, 145, 202, 0.9);
    }

    nav {
        display: flex;
        gap: 25px;
    }

    nav a {
        color: var(--light-text);
        text-decoration: none;
        font-weight: 600;
        padding: 10px 18px;
        border-radius: 8px;
        transition: all 0.3s ease;
        position: relative;
        letter-spacing: 1px;
        text-transform: uppercase;
        font-size: 1.3rem;
    }

    nav a:hover {
        color: var(--primary-color);
        background-color: rgba(52, 152, 219, 0.1);
        transform: translateY(-3px);
    }

    nav a::after {
        content: '';
        position: absolute;
        width: 0;
        height: 3px;
        background-color: var(--primary-color);
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        transition: width 0.3s ease;
        border-radius: 3px;
    }

    nav a:hover::after {
        width: 70%;
    }

    .main-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    .page-header {
        text-align: center;
        margin-bottom: 60px;
        position: relative;
    }

    .page-title {
        font-size: 3.5rem;
        font-weight: 700;
        background: var(--gradient-1);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 20px;
        text-shadow: 0 0 30px rgba(46, 145, 202, 0.5);
        animation: glow 2s ease-in-out infinite alternate;
    }

    @keyframes glow {
        from {
            text-shadow: 0 0 20px rgba(46, 145, 202, 0.5);
        }
        to {
            text-shadow: 0 0 30px rgba(46, 145, 202, 0.8), 0 0 40px rgba(46, 145, 202, 0.6);
        }
    }

    .page-subtitle {
        font-size: 1.2rem;
        color: rgba(255, 255, 255, 0.8);
        font-weight: 300;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }

    .tournament-section {
        margin: 60px 0;
        opacity: 0;
        animation: fadeInUp 0.8s ease forwards;
    }

    .tournament-section:nth-child(2) {
        animation-delay: 0.2s;
    }

    .tournament-section:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .section-header {
        text-align: center;
        margin-bottom: 30px;
        position: relative;
    }

    .section-title {
        display: inline-flex;
        align-items: center;
        gap: 15px;
        font-size: 2.2rem;
        font-weight: 600;
        color: #ffffff;
        background: var(--card-bg);
        padding: 20px 40px;
        border-radius: 50px;
        border: 2px solid var(--primary-color);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        position: relative;
        overflow: hidden;
        transition: all 0.4s ease;
    }

    .section-title::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: var(--gradient-2);
        transition: left 0.5s ease;
        z-index: -1;
    }

    .section-title:hover::before {
        left: 0;
    }

    .section-title:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(46, 145, 202, 0.3);
    }

    .age-icon {
        font-size: 2rem;
        color: var(--accent-color);
        text-shadow: 0 0 10px rgba(231, 76, 60, 0.5);
    }

    .iframe-wrapper {
        background: var(--card-bg);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(46, 145, 202, 0.2);
        position: relative;
        overflow: hidden;
        transition: all 0.4s ease;
    }

    .iframe-wrapper::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: var(--gradient-3);
        border-radius: 22px;
        z-index: -1;
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    .iframe-wrapper:hover::before {
        opacity: 1;
    }

    .iframe-wrapper:hover {
        transform: translateY(-10px);
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
    }

    .iframe-container {
        position: relative;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        background: #000;
    }

    .iframe-container iframe {
        width: 100%;
        height: 600px;
        border: none;
        border-radius: 15px;
        transition: all 0.3s ease;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(26, 30, 46, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        border-radius: 15px;
        z-index: 10;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(46, 145, 202, 0.3);
        border-top: 4px solid var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 20px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .loading-text {
        color: var(--primary-color);
        font-weight: 500;
        text-align: center;
    }

    .no-schedule {
        text-align: center;
        padding: 60px 20px;
        color: rgba(255, 255, 255, 0.6);
        font-size: 1.1rem;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        border: 2px dashed rgba(46, 145, 202, 0.3);
    }

    .no-schedule i {
        font-size: 3rem;
        margin-bottom: 20px;
        color: var(--primary-color);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .navbar {
            flex-direction: column;
            gap: 20px;
            padding: 20px;
        }

        nav {
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
        }

        nav a {
            font-size: 0.9rem;
            padding: 10px 15px;
        }

        .page-title {
            font-size: 2.5rem;
        }

        .section-title {
            font-size: 1.8rem;
            padding: 15px 25px;
            flex-direction: column;
            gap: 10px;
        }

        .iframe-wrapper {
            padding: 20px;
        }

        .iframe-container iframe {
            height: 400px;
        }

        .main-container {
            padding: 20px 15px;
        }
    }

    @media (max-width: 480px) {
        .page-title {
            font-size: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
        }

        .iframe-container iframe {
            height: 350px;
        }
    }
</style>

<body>
    <!-- Animated background particles -->
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
            <a href="register.php">สมัครทีม</a>
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

        <!-- รุ่นอายุไม่เกิน 18 ปี -->
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

                        <div class="loading-overlay" id="loading-under-18">
                            <div class="loading-spinner"></div>
                            <div class="loading-text">กำลังโหลดตารางการแข่งขัน...</div>
                        </div>

                        <iframe 
                            src="<?= htmlspecialchars($iframes['under_18']) ?>" 
                            onload="document.getElementById('loading-under-18').style.display='none'"
                            title="ตารางการแข่งขันรุ่นอายุไม่เกิน 18 ปี">
                        </iframe>
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

        <!-- รุ่นอายุตั้งแต่ 18 ปีขึ้นไป -->
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

                        <div class="loading-overlay" id="loading-above-18">
                            <div class="loading-spinner"></div>
                            <div class="loading-text">กำลังโหลดตารางการแข่งขัน...</div>
                        </div>

                        <iframe 
                            src="<?= htmlspecialchars($iframes['above_18']) ?>" 
                            onload="document.getElementById('loading-above-18').style.display='none'"
                            title="ตารางการแข่งขันรุ่นอายุตั้งแต่ 18 ปีขึ้นไป">
                        </iframe>
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