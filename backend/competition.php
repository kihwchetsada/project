<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// เชื่อมต่อฐานข้อมูล
require '../db_connect.php';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset("utf8");
// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ดึงข้อมูลการแข่งขัน
$result = $conn->query("SELECT * FROM competitions WHERE id = 1");
$competitions = $result ? $result->fetch_assoc() : null;

// ตรวจสอบข้อมูล
if (!$competitions) {
    $competitions = [
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
    <link rel="stylesheet" href="../css/competition.css">
</head>
<body>

    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
<a href="admin_dashboard.php" class="button-link fixed-back-button">
    กลับสู่หน้าหลัก
</a>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> จัดการการเปิดรับสมัคร</h1>
            <p>กำหนดวันที่เปิดรับสมัครและสถานะการรับสมัคร</p>
        </div>
        <div>
            
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
                    <input type="date" id="start_date" name="start_date" value="<?= $competitions['start_date'] ?>" required>
                </div>

                <div class="form-group">
                    <label for="end_date"><i class="fas fa-flag-checkered"></i> วันที่สิ้นสุดการรับสมัคร:</label>
                    <input type="date" id="end_date" name="end_date" value="<?= $competitions['end_date'] ?>" required>
                </div>

                <div class="checkbox-container">
                    <!-- กล่อง checkbox -->
                    <label class="custom-checkbox" for="is_open">
                        <input type="checkbox" id="is_open" name="is_open" <?= $competitions['is_open'] ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                    </label>

                    <!-- ข้อความ -->
                    <label for="is_open">เปิดรับสมัคร</label>
                </div>


                <input type="hidden" name="id" value="<?= $competitions['id'] ?>">
                
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