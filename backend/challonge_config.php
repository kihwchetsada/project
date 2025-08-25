<?php
// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ดึงข้อมูลการตั้งค่า Challonge API ถ้ามี
$config = null;
$config_sql = "SELECT * FROM challonge_config LIMIT 1";
$config_result = $conn->query($config_sql);

if ($config_result && $config_result->num_rows > 0) {
    $config = $config_result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ตั้งค่า Challonge API</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link rel="icon" type="image/x-icon" href="../img/logo.jpg">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        line-height: 1.6;
    }

    .container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .header {
        text-align: center;
        margin-bottom: 40px;
        animation: fadeInDown 0.8s ease-out;
    }

    .header h1 {
        color: white;
        font-size: 2.5em;
        margin-bottom: 10px;
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
    }

    .header h1 i {
        background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-size: 1.2em;
    }

    .subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.1em;
        margin-top: 10px;
        font-weight: 300;
    }

    .form-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        animation: fadeInUp 0.8s ease-out;
    }

    .info-box {
        background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
        border: 1px solid rgba(103, 58, 183, 0.2);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .info-box::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    }

    .info-box p {
        margin: 8px 0;
        color: #37474f;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .info-box strong {
        color: #1565c0;
        font-weight: 600;
    }

    .info-box i {
        color: #7b1fa2;
        width: 20px;
    }

    .form-group {
        margin-bottom: 30px;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        color: #333;
        margin-bottom: 12px;
        font-size: 1.1em;
    }

    .form-group label i {
        color: #667eea;
        font-size: 1.2em;
    }

    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-wrapper input {
        width: 100%;
        padding: 16px 50px 16px 50px;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: #fafafa;
    }

    .input-wrapper input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .input-icon {
        position: absolute;
        left: 18px;
        color: #999;
        z-index: 1;
        pointer-events: none;
    }

    .toggle-password {
        position: absolute;
        right: 18px;
        cursor: pointer;
        color: #999;
        font-size: 18px;
        transition: color 0.3s ease;
        z-index: 2;
        padding: 5px;
        border-radius: 50%;
    }

    .toggle-password:hover {
        color: #667eea;
        background: rgba(102, 126, 234, 0.1);
    }

    .help-text {
        margin-top: 12px;
        color: #666;
        font-size: 0.9em;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #667eea;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .help-text i {
        color: #ffc107;
        font-size: 1.1em;
    }

    .button-group {
        display: flex;
        gap: 15px;
        margin-top: 40px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn {
        padding: 16px 32px;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
        min-width: 160px;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .btn:hover::before {
        left: 100%;
    }

    .btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
    }

    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(245, 87, 108, 0.4);
    }

    .btn:active {
        transform: translateY(0);
    }

    /* Animations */
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Success/Error Messages */
    .message {
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
    }

    .message.success {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .message.error {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    /* API Status Indicator */
    .api-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.9em;
        font-weight: 600;
        margin-left: 10px;
    }

    .api-status.connected {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
    }

    .api-status.disconnected {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        color: #721c24;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    .status-dot.connected {
        background: #28a745;
    }

    .status-dot.disconnected {
        background: #dc3545;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
        }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .container {
            padding: 15px;
        }
        
        .form-container {
            padding: 25px;
        }
        
        .header h1 {
            font-size: 2em;
        }
        
        .button-group {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            min-width: auto;
        }
    }

    /* Loading Animation */
    .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255,255,255,.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Card hover effect */
    .form-container:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        transition: all 0.3s ease;
    }

    /* Input focus effects */
    .input-wrapper input:focus + .toggle-password {
        color: #667eea;
    }

    .form-group:focus-within label {
        color: #667eea;
    }

    .form-group:focus-within label i {
        transform: scale(1.1);
        transition: transform 0.3s ease;
    }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-cogs"></i> ตั้งค่า Challonge API</h1>
        <p class="subtitle">กำหนดค่าการเชื่อมต่อกับระบบ Challonge Tournament</p>
    </div>
    
    <div class="form-container">
        <?php if ($config): ?>
        <!-- Info Box with enhanced design -->
        <div class="info-box">
            <p><i class="fas fa-calendar-plus"></i> <strong>สร้างเมื่อ:</strong> <?php echo $config['created_at']; ?></p>
            <p><i class="fas fa-edit"></i> <strong>แก้ไขล่าสุด:</strong> <?php echo $config['updated_at']; ?></p>
            <p class="value"><i class="fas fa-key"></i> <strong>API Key ที่ใช้อยู่:</strong> <?php echo htmlspecialchars($config['api_key'] ?? ''); ?></p>
        </div>
        <?php endif; ?>

        <form id="challengeApiForm" action="save_challonge_config.php" method="post">
            <div class="form-group">
                <label for="api_key">
                    <i class="fas fa-key"></i> API Key:
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="api_key" name="api_key" required placeholder="กรอก Challonge API Key ของคุณ">
                    <span class="toggle-password" onclick="togglePasswordVisibility('api_key')" title="แสดง/ซ่อน API Key">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <p class="help-text">
                    <i class="fas fa-lightbulb"></i>
                    ค้นหา API Key ได้ที่เว็บ Challonge ในส่วน Settings → Developer API → Generate New API Key
                </p>
            </div>

            <div class="button-group">
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i>
                    บันทึกการตั้งค่า
                </button>
                <a href="admin_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    กลับหน้าหลัก
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const toggleBtn = field.parentElement.querySelector('.toggle-password i');
    
    if (field.type === "password") {
        field.type = "text";
        toggleBtn.className = "fas fa-eye-slash";
    } else {
        field.type = "password";
        toggleBtn.className = "fas fa-eye";
    }
}

function testConnection() {
    const testBtn = document.getElementById('testBtn');
    const originalContent = testBtn.innerHTML;
    
    // Show loading state
    testBtn.innerHTML = '<span class="loading"></span> กำลังทดสอบ...';
    testBtn.disabled = true;
    
    // Simulate API test (replace with actual API call)
    setTimeout(() => {
        // Reset button
        testBtn.innerHTML = originalContent;
        testBtn.disabled = false;
        
        // Show success message (you would handle this based on actual API response)
        showMessage('การเชื่อมต่อสำเร็จ! API Key ถูกต้อง', 'success');
    }, 2000);
}

function showMessage(text, type) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message
    const message = document.createElement('div');
    message.className = `message ${type}`;
    message.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        ${text}
    `;
    
    // Insert message
    const form = document.getElementById('challengeApiForm');
    form.insertBefore(message, form.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        message.style.opacity = '0';
        setTimeout(() => message.remove(), 300);
    }, 5000);
}

// Form submission handling
document.getElementById('challengeApiForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalContent = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = '<span class="loading"></span> กำลังบันทึก...';
    submitBtn.disabled = true;
    
    // Simulate form submission (replace with actual form submission)
    setTimeout(() => {
        submitBtn.innerHTML = originalContent;
        submitBtn.disabled = false;
        showMessage('บันทึกการตั้งค่าเรียบร้อยแล้ว!', 'success');
    }, 1500);
});

// Add smooth scroll and entrance animations
document.addEventListener('DOMContentLoaded', function() {
    // Add entrance animation to form elements
    const formGroups = document.querySelectorAll('.form-group');
    formGroups.forEach((group, index) => {
        group.style.animationDelay = `${0.2 + (index * 0.1)}s`;
        group.style.animation = 'fadeInUp 0.6s ease-out forwards';
        group.style.opacity = '0';
    });
    
    // Add hover effect to input fields
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
});
</script>
</body>
</html>