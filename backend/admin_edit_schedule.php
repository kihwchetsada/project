<?php
require '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $under18 = $_POST['under_18'] ?? '';
    $above18 = $_POST['above_18'] ?? '';

    $stmt = $conn->prepare("UPDATE tournament_links SET iframe_url = ? WHERE category = ?");
    $stmt->execute([$under18, 'under_18']);
    $stmt->execute([$above18, 'above_18']);

    $message = "อัปเดตลิงก์สำเร็จแล้ว!";
}

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
    <title>จัดการลิงก์ตาราง - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<style>
    :root {
        --primary-color: #2e91ca;
        --secondary-color: #8e44ad;
        --accent-color: #e74c3c;
        --success-color: #27ae60;
        --warning-color: #f39c12;
        --dark-bg: #0a0e17;
        --card-bg: #1a1e2e;
        --light-text: #ffffff;
        --input-bg: #2c3e50;
        --border-color: rgba(46, 145, 202, 0.3);
        --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
        padding: 20px;
        position: relative;
        overflow-x: hidden;
    }

    /* Animated background elements */
    .bg-animation {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1;
        overflow: hidden;
    }

    .floating-shape {
        position: absolute;
        border-radius: 50%;
        background: rgba(46, 145, 202, 0.1);
        animation: float 20s infinite linear;
    }

    .floating-shape:nth-child(1) {
        width: 80px;
        height: 80px;
        top: 20%;
        left: 10%;
        animation-delay: 0s;
    }

    .floating-shape:nth-child(2) {
        width: 60px;
        height: 60px;
        top: 60%;
        left: 80%;
        animation-delay: 7s;
    }

    .floating-shape:nth-child(3) {
        width: 100px;
        height: 100px;
        top: 80%;
        left: 20%;
        animation-delay: 14s;
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0px) rotate(0deg);
            opacity: 0.3;
        }
        50% {
            transform: translateY(-20px) rotate(180deg);
            opacity: 0.7;
        }
    }

    .container {
        max-width: 1000px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }

    .admin-header {
        text-align: center;
        margin-bottom: 40px;
        padding: 30px;
        background: var(--card-bg);
        border-radius: 20px;
        border: 2px solid var(--border-color);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        position: relative;
        overflow: hidden;
    }

    .admin-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: var(--gradient-1);
        opacity: 0.1;
        transition: left 0.8s ease;
        z-index: -1;
    }

    .admin-header:hover::before {
        left: 0;
    }

    .admin-title {
        font-size: 2.5rem;
        font-weight: 700;
        background: var(--gradient-1);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 15px;
        text-shadow: 0 0 30px rgba(46, 145, 202, 0.5);
        animation: pulse 2s ease-in-out infinite alternate;
    }

    @keyframes pulse {
        from {
            text-shadow: 0 0 20px rgba(46, 145, 202, 0.5);
        }
        to {
            text-shadow: 0 0 30px rgba(46, 145, 202, 0.8), 0 0 40px rgba(46, 145, 202, 0.6);
        }
    }

    .admin-subtitle {
        font-size: 1.1rem;
        color: rgba(255, 255, 255, 0.7);
        font-weight: 300;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .admin-icon {
        font-size: 2rem;
        color: var(--primary-color);
        margin-bottom: 10px;
        animation: spin 3s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .success-message {
        background: var(--gradient-success);
        color: white;
        padding: 15px 25px;
        border-radius: 15px;
        margin-bottom: 30px;
        text-align: center;
        font-weight: 600;
        box-shadow: 0 10px 30px rgba(39, 174, 96, 0.3);
        border: 2px solid rgba(39, 174, 96, 0.5);
        animation: slideInDown 0.6s ease-out;
        position: relative;
        overflow: hidden;
    }

    .success-message::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .success-message i {
        margin-right: 10px;
        font-size: 1.2rem;
    }

    .form-container {
        background: var(--card-bg);
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        border: 2px solid var(--border-color);
        position: relative;
        overflow: hidden;
    }

    .form-container::before {
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

    .form-container:hover::before {
        opacity: 1;
    }

    .form-group {
        margin-bottom: 30px;
        position: relative;
    }

    .form-label {
        display: block;
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
    }

    .form-label i {
        margin-right: 10px;
        font-size: 1.1rem;
        color: var(--accent-color);
    }

    .form-label::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 50px;
        height: 3px;
        background: var(--gradient-2);
        border-radius: 3px;
    }

    .form-input {
        width: 100%;
        padding: 15px 20px;
        border: 2px solid var(--border-color);
        border-radius: 15px;
        background: var(--input-bg);
        color: var(--light-text);
        font-size: 1rem;
        font-family: 'Kanit', sans-serif;
        transition: all 0.4s ease;
        position: relative;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 20px rgba(46, 145, 202, 0.3);
        background: #34495e;
        transform: translateY(-2px);
    }

    .form-input:hover {
        border-color: rgba(46, 145, 202, 0.6);
        transform: translateY(-1px);
    }

    .input-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary-color);
        font-size: 1.2rem;
        pointer-events: none;
    }

    .submit-btn {
        background: var(--gradient-1);
        color: white;
        padding: 18px 40px;
        border: none;
        border-radius: 25px;
        font-size: 1.2rem;
        font-weight: 600;
        font-family: 'Kanit', sans-serif;
        cursor: pointer;
        transition: all 0.4s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 10px 30px rgba(46, 145, 202, 0.3);
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin: 0 auto;
    }

    .submit-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: var(--gradient-2);
        transition: left 0.4s ease;
        z-index: -1;
    }

    .submit-btn:hover::before {
        left: 0;
    }

    .submit-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(46, 145, 202, 0.5);
    }

    .submit-btn:active {
        transform: translateY(-1px);
    }

    .submit-btn i {
        font-size: 1.1rem;
        transition: transform 0.3s ease;
    }

    .submit-btn:hover i {
        transform: rotate(360deg);
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: var(--light-text);
        text-decoration: none;
        font-weight: 500;
        padding: 12px 25px;
        border: 2px solid var(--border-color);
        border-radius: 25px;
        transition: all 0.3s ease;
        margin-bottom: 30px;
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
    }

    .back-btn:hover {
        color: var(--primary-color);
        border-color: var(--primary-color);
        transform: translateX(-5px);
        box-shadow: 0 5px 15px rgba(46, 145, 202, 0.2);
    }

    .back-btn i {
        transition: transform 0.3s ease;
    }

    .back-btn:hover i {
        transform: translateX(-5px);
    }

    .preview-section {
        margin-top: 40px;
        padding: 30px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        border: 1px solid var(--border-color);
    }

    .preview-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .preview-links {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .preview-item {
        background: var(--input-bg);
        padding: 15px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
    }

    .preview-item h4 {
        color: var(--accent-color);
        margin-bottom: 8px;
        font-size: 1rem;
    }

    .preview-item p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.9rem;
        word-break: break-word;
    }

    .preview-item.empty {
        border: 2px dashed var(--border-color);
        text-align: center;
        color: rgba(255, 255, 255, 0.5);
        font-style: italic;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .container {
            padding: 0 15px;
        }

        .admin-title {
            font-size: 2rem;
        }

        .form-container {
            padding: 25px;
        }

        .form-input {
            font-size: 16px; /* Prevent zoom on iOS */
        }

        .preview-links {
            grid-template-columns: 1fr;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
        }
    }

    @media (max-width: 480px) {
        .admin-title {
            font-size: 1.8rem;
        }

        .admin-header {
            padding: 20px;
        }

        .form-container {
            padding: 20px;
        }
    }
</style>

<body>
    <!-- Animated background -->
    <div class="bg-animation">
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
    </div>

    <div class="container">
        <!-- Back Button -->
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            กลับไปหน้า Admin
        </a>

        <!-- Header -->
        <div class="admin-header">
            <div class="admin-icon">
                <i class="fas fa-cogs"></i>
            </div>
            <h1 class="admin-title">
                จัดการลิงก์ตารางการแข่งขัน
            </h1>
            <div class="admin-subtitle">
                <i class="fas fa-calendar-alt"></i>
                แอดมินแพนเนล - แก้ไขตารางการแข่งขัน
            </div>
        </div>

        <!-- Success Message -->
        <?php if (!empty($message)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <div class="form-container">
            <form method="POST" id="scheduleForm">
                <div class="form-group">
                    <label class="form-label" for="under_18">
                        <i class="fas fa-child"></i>
                        ลิงก์รุ่นไม่เกิน 18 ปี
                    </label>
                    <div style="position: relative;">
                        <input 
                            type="url" 
                            id="under_18"
                            name="under_18" 
                            class="form-input"
                            value="<?= htmlspecialchars($iframes['under_18'] ?? '') ?>"
                            placeholder="https://example.com/embed/under18"
                        >
                        <i class="fas fa-link input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="above_18">
                        <i class="fas fa-user-graduate"></i>
                        ลิงก์รุ่นทั่วไป (18 ปีขึ้นไป)
                    </label>
                    <div style="position: relative;">
                        <input 
                            type="url" 
                            id="above_18"
                            name="above_18" 
                            class="form-input"
                            value="<?= htmlspecialchars($iframes['above_18'] ?? '') ?>"
                            placeholder="https://example.com/embed/above18"
                        >
                        <i class="fas fa-link input-icon"></i>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i>
                    บันทึกการเปลี่ยนแปลง
                </button>
            </form>

            <!-- Preview Section -->
            <div class="preview-section">
                <h3 class="preview-title">
                    <i class="fas fa-eye"></i>
                    ตัวอย่างลิงก์ปัจจุบัน
                </h3>
                <div class="preview-links">
                    <div class="preview-item <?= empty($iframes['under_18']) ? 'empty' : '' ?>">
                        <h4>รุ่นไม่เกิน 18 ปี</h4>
                        <?php if (!empty($iframes['under_18'])): ?>
                            <p><?= htmlspecialchars($iframes['under_18']) ?></p>
                        <?php else: ?>
                            <p>ยังไม่ได้ตั้งค่าลิงก์</p>
                        <?php endif; ?>
                    </div>
                    <div class="preview-item <?= empty($iframes['above_18']) ? 'empty' : '' ?>">
                        <h4>รุ่นทั่วไป (18 ปีขึ้นไป)</h4>
                        <?php if (!empty($iframes['above_18'])): ?>
                            <p><?= htmlspecialchars($iframes['above_18']) ?></p>
                        <?php else: ?>
                            <p>ยังไม่ได้ตั้งค่าลิงก์</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // เอฟเฟกต์แอนิเมชันเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            // Fade in animation
            const elements = document.querySelectorAll('.admin-header, .form-container');
            elements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    el.style.transition = 'all 0.6s ease';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 200);
            });

            // Form validation
            const form = document.getElementById('scheduleForm');
            const inputs = form.querySelectorAll('input[type="url"]');
            
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value && !isValidUrl(this.value)) {
                        this.style.borderColor = getComputedStyle(document.documentElement).getPropertyValue('--accent-color');
                        this.style.boxShadow = '0 0 10px rgba(231, 76, 60, 0.3)';
                    } else {
                        this.style.borderColor = '';
                        this.style.boxShadow = '';
                    }
                });
            });

            // Auto-save indication
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('.submit-btn');
                const originalText = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
                submitBtn.disabled = true;
                
                // Re-enable after form submission
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 1000);
            });
        });

        // URL validation function
        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }

        // Floating shapes animation
        setInterval(() => {
            const shapes = document.querySelectorAll('.floating-shape');
            shapes.forEach(shape => {
                const randomX = Math.random() * 100;
                const randomY = Math.random() * 100;
                shape.style.left = randomX + '%';
                shape.style.top = randomY + '%';
            });
        }, 10000);

        // Success message auto-hide
        const successMsg = document.querySelector('.success-message');
        if (successMsg) {
            setTimeout(() => {
                successMsg.style.transition = 'all 0.5s ease';
                successMsg.style.opacity = '0';
                successMsg.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    successMsg.remove();
                }, 500);
            }, 5000);
        }
    </script>
</body>
</html>