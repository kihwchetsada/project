<?php

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli('localhost', 'root', '', 'competition_system');
$conn->set_charset("utf8");
// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ดึงข้อมูลการแข่งขัน
$result = $conn->query("SELECT * FROM competitions_1 WHERE id = 1");
$competitions_1 = $result ? $result->fetch_assoc() : null;

// ตรวจสอบข้อมูล
if (!$competitions_1) {
    $competitions_1 = [
        'start_date' => '',
        'end_date' => '',
        'is_open' => 0,
        'id' => 1,
    ];
    $alert_message = "ไม่มีข้อมูลการแข่งขันในฐานข้อมูล!";
}

// ตรวจสอบการส่งค่าสำเร็จจาก update_competition.php
$success_message = isset($_GET['success']) ? "บันทึกข้อมูลเรียบร้อยแล้ว" : "";
$error_message = isset($_GET['error']) ? "เกิดข้อผิดพลาดในการบันทึกข้อมูล" : "";

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการการเปิดรับสมัคร</title>
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.03)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
            pointer-events: none;
            z-index: -1;
        }

        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }

        .shape:nth-child(1) { width: 100px; height: 100px; left: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 150px; height: 150px; left: 70%; animation-delay: -5s; }
        .shape:nth-child(3) { width: 80px; height: 80px; left: 30%; animation-delay: -10s; }
        .shape:nth-child(4) { width: 120px; height: 120px; left: 80%; animation-delay: -15s; }

        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 550px;
            animation: slideInUp 0.8s ease-out;
            position: relative;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            border-radius: 24px 24px 0 0;
        }

        .header {
            text-align: center;
            margin-bottom: 35px;
            position: relative;
        }

        .header h1 {
            color: #2d3748;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .header h1 i {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .header p {
            color: #718096;
            font-size: 1.1rem;
            font-weight: 400;
        }

        .alert {
            padding: 18px 24px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideInRight 0.6s ease-out;
            border: 1px solid transparent;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #68d391, #48bb78);
            color: white;
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
        }

        .alert-error {
            background: linear-gradient(135deg, #fc8181, #e53e3e);
            color: white;
            box-shadow: 0 8px 25px rgba(229, 62, 62, 0.3);
        }

        .alert-warning {
            background: linear-gradient(135deg, #f6e05e, #d69e2e);
            color: white;
            box-shadow: 0 8px 25px rgba(214, 158, 46, 0.3);
        }

        .form-container {
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group label i {
            color: #667eea;
            width: 20px;
        }

        .form-group input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-size: 1.05rem;
            font-family: 'Kanit', sans-serif;
            transition: all 0.3s ease;
            background: #f8fafc;
            position: relative;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .form-group input:hover {
            border-color: #cbd5e0;
            background: white;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 30px 0;
            padding: 20px;
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            border-radius: 16px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .checkbox-container:hover {
            border-color: #667eea;
            background: linear-gradient(135deg, #f0f4ff, #e6f0ff);
        }

        .custom-checkbox {
            position: relative;
            cursor: pointer;
        }

        .custom-checkbox input {
            opacity: 0;
            position: absolute;
            width: 24px;
            height: 24px;
        }

        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 24px;
            width: 24px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .custom-checkbox:hover .checkmark {
            border-color: #667eea;
            transform: scale(1.05);
        }

        .custom-checkbox input:checked ~ .checkmark {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: #667eea;
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
            left: 7px;
            top: 3px;
            width: 6px;
            height: 12px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .custom-checkbox input:checked ~ .checkmark:after {
            display: block;
        }

        .checkbox-container label:last-child {
            font-weight: 600;
            color: #4a5568;
            font-size: 1.1rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .checkbox-container:hover label:last-child {
            color: #667eea;
        }

        .button-container {
            text-align: center;
            margin-top: 35px;
        }

        .button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 18px 50px;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 600;
            font-family: 'Kanit', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .button:hover::before {
            left: 100%;
        }

        .button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .button:active {
            transform: translateY(-1px);
        }

        .button i {
            font-size: 1.2rem;
        }

        .footer {
            text-align: center;
            color: #718096;
            font-size: 0.9rem;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
        }

        .footer p:first-child {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 5px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                padding: 25px;
                max-width: calc(100% - 20px);
            }

            .header h1 {
                font-size: 1.8rem;
                flex-direction: column;
                gap: 10px;
            }

            .header h1 i {
                font-size: 2rem;
            }

            .form-group input {
                padding: 14px 16px;
                font-size: 1rem;
            }

            .button {
                padding: 16px 40px;
                font-size: 1rem;
            }

            .checkbox-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        /* Loading Animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading .button {
            position: relative;
        }

        .loading .button::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
        }

        @keyframes spin {
            0% { transform: translateY(-50%) rotate(0deg); }
            100% { transform: translateY(-50%) rotate(360deg); }
        }

        /* Smooth transitions for all elements */
        * {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> จัดการการเปิดรับสมัคร</h1>
            <p>กำหนดวันที่เปิดรับสมัครและสถานะการรับสมัคร</p>
        </div>

        <div class="form-container">
            <?php if (isset($alert_message)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $alert_message; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-times-circle"></i>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="update_competition.php" id="competitionForm">
                <div class="form-group">
                    <label for="start_date"><i class="fas fa-play"></i> วันที่เริ่มรับสมัคร:</label>
                    <input type="date" id="start_date" name="start_date" value="<?= $competitions_1['start_date'] ?>" required>
                </div>

                <div class="form-group">
                    <label for="end_date"><i class="fas fa-flag-checkered"></i> วันที่สิ้นสุดการรับสมัคร:</label>
                    <input type="date" id="end_date" name="end_date" value="<?= $competitions_1['end_date'] ?>" required>
                </div>

                <div class="checkbox-container">
                    
                    <label class="custom-checkbox">
                        <input type="checkbox" id="is_open" name="is_open" <?= $competitions_1['is_open'] ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        <label for="is_open"><i class="fas fa-door-open"></i> เปิดรับสมัคร</label>
                    </label>
                </div>

                <input type="hidden" name="id" value="<?= $competitions_1['id'] ?>">
                
                <div class="button-container">
                    <button type="submit" class="button" id="submitBtn">
                        <i class="fas fa-save"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>

        <div class="footer">
            <p>ระบบจัดการการแข่งขัน - เวอร์ชัน 2.0.0</p>
            <p>© <?php echo date('Y'); ?> สงวนลิขสิทธิ์</p>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('competitionForm');
        const submitBtn = document.getElementById('submitBtn');
        const container = document.querySelector('.container');

        // Enhanced date validation
        function validateDates() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (startDate && endDate) {
                if (new Date(endDate) < new Date(startDate)) {
                    showAlert('วันที่สิ้นสุดต้องมากกว่าวันที่เริ่ม', 'error');
                    return false;
                }
            }
            return true;
        }

        // Custom alert function
        function showAlert(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.dynamic-alert');
            existingAlerts.forEach(alert => alert.remove());

            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} dynamic-alert`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'times-circle' : 'info-circle'}"></i>
                ${message}
            `;
            
            const formContainer = document.querySelector('.form-container');
            formContainer.insertBefore(alertDiv, formContainer.firstChild);

            // Auto remove after 5 seconds
            setTimeout(() => {
                alertDiv.style.animation = 'slideOutLeft 0.5s ease-in';
                setTimeout(() => alertDiv.remove(), 500);
            }, 5000);
        }

        // Date change handlers
        document.getElementById('end_date').addEventListener('change', function() {
            validateDates();
        });

        document.getElementById('start_date').addEventListener('change', function() {
            const endDateInput = document.getElementById('end_date');
            if (endDateInput.value) {
                validateDates();
            }
        });

        // Form submission with loading state
        form.addEventListener('submit', function(e) {
            if (!validateDates()) {
                e.preventDefault();
                return;
            }

            // Add loading state
            container.classList.add('loading');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
        });

        // Smooth scroll for alerts
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.addEventListener('click', function() {
                this.style.animation = 'slideOutLeft 0.5s ease-in';
                setTimeout(() => this.remove(), 500);
            });
        });

        // Add hover effects for form inputs
        const inputs = document.querySelectorAll('input[type="date"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        // Checkbox animation
        const checkbox = document.getElementById('is_open');
        const checkboxContainer = document.querySelector('.checkbox-container');
        
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                checkboxContainer.style.background = 'linear-gradient(135deg, #e6fffa, #b2f5ea)';
                checkboxContainer.style.borderColor = '#38b2ac';
            } else {
                checkboxContainer.style.background = 'linear-gradient(135deg, #f7fafc, #edf2f7)';
                checkboxContainer.style.borderColor = '#e2e8f0';
            }
        });

        // Initialize checkbox state
        if (checkbox.checked) {
            checkboxContainer.style.background = 'linear-gradient(135deg, #e6fffa, #b2f5ea)';
            checkboxContainer.style.borderColor = '#38b2ac';
        }

        // Add particle effect on successful submission
        function createParticles() {
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.style.cssText = `
                    position: fixed;
                    width: 10px;
                    height: 10px;
                    background: #667eea;
                    border-radius: 50%;
                    pointer-events: none;
                    z-index: 1000;
                    left: 50%;
                    top: 50%;
                    animation: explode 1s ease-out forwards;
                    animation-delay: ${i * 0.05}s;
                `;
                document.body.appendChild(particle);
                
                setTimeout(() => particle.remove(), 1000);
            }
        }

        // Add CSS for particle animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes explode {
                0% {
                    transform: translate(-50%, -50%) scale(0);
                    opacity: 1;
                }
                100% {
                    transform: translate(${Math.random() * 400 - 200}px, ${Math.random() * 400 - 200}px) scale(1);
                    opacity: 0;
                }
            }
            @keyframes slideOutLeft {
                to {
                    transform: translateX(-100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Trigger particles on success
        if (window.location.search.includes('success=1')) {
            setTimeout(createParticles, 500);
        }
    });
    </script>
</body>
</html>