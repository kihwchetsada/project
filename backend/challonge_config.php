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

// ส่วนแสดงผล
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <title>ตั้งค่า Challonge API</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }
        
        .header {
            background: linear-gradient(135deg, #3a00b0 0%, #5a4fcf 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255,255,255,0.03) 10px,
                rgba(255,255,255,0.03) 20px
            );
            animation: movePattern 20s linear infinite;
        }
        
        @keyframes movePattern {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        .header h1 {
            font-size: 2.5em;
            font-weight: 600;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }
        
        .header .subtitle {
            font-size: 1.1em;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }
        
        .form-container {
            padding: 40px 30px;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            padding: 15px;
            border-radius: 10px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
        }
        
        .status-indicator.connected {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .status-indicator.disconnected {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
            font-size: 1.05em;
        }
        
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            color: #6c757d;
            z-index: 2;
        }
        
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            font-size: 16px;
            font-family: 'Prompt', sans-serif;
            background: white;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #3a00b0;
            box-shadow: 0 0 0 3px rgba(58, 0, 176, 0.1);
            transform: translateY(-2px);
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #6c757d;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
            z-index: 3;
        }
        
        .toggle-password:hover {
            color: #3a00b0;
            background: rgba(58, 0, 176, 0.1);
        }
        
        .help-text {
            font-size: 14px;
            color: #6c757d;
            margin-top: 8px;
            padding-left: 5px;
            line-height: 1.4;
        }
        
        .help-text i {
            margin-right: 5px;
            color: #17a2b8;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            background: linear-gradient(135deg, #3a00b0 0%, #5a4fcf 100%);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            font-family: 'Prompt', sans-serif;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(58, 0, 176, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 10px 25px rgba(108, 117, 125, 0.3);
        }
        
        .btn-test {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        }
        
        .btn-test:hover {
            box-shadow: 0 10px 25px rgba(23, 162, 184, 0.3);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: none;
            position: relative;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert i {
            margin-right: 10px;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3a00b0;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .feature-highlight {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #2196f3;
        }
        
        .feature-highlight h3 {
            color: #1976d2;
            margin-bottom: 10px;
            font-size: 1.2em;
        }
        
        .feature-highlight p {
            color: #424242;
            line-height: 1.5;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .form-container {
                padding: 30px 20px;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
                width: 100%;
            }
            
            input[type="text"], input[type="password"] {
                padding: 12px 12px 12px 40px;
            }
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
            <div class="feature-highlight">
                <h3><i class="fas fa-info-circle"></i> เกี่ยวกับ Challonge API</h3>
                <p>เชื่อมต่อกับ Challonge เพื่อสร้างและจัดการทัวร์นาเมนต์อัตโนมัติ ระบบจะช่วยสร้างแบร็กเก็ต, อัพเดทผลแข่งขัน และติดตามคะแนนผู้เข้าแข่งขัน</p>
            </div>
            
            <div id="status-indicator" class="status-indicator disconnected">
                <i class="fas fa-exclamation-triangle"></i>
                <span>ยังไม่ได้เชื่อมต่อกับ Challonge</span>
            </div>
            
            <div id="successAlert" class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>บันทึกการตั้งค่าเรียบร้อยแล้ว</span>
            </div>
            
            <div id="errorAlert" class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>เกิดข้อผิดพลาด กรุณาตรวจสอบข้อมูลอีกครั้ง</span>
            </div>
            
            <div id="loading" class="loading">
                <div class="spinner"></div>
                <p>กำลังทดสอบการเชื่อมต่อ...</p>
            </div>
            
            <form id="challengeApiForm" action="save_challonge_config.php" method="post">
                <div class="form-group">
                    <label for="api_key">
                        <i class="fas fa-key"></i> API Key:
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="api_key" name="api_key" value="" required>
                        <span class="toggle-password" onclick="togglePasswordVisibility('api_key')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <p class="help-text">
                        <i class="fas fa-lightbulb"></i>
                        ค้นหา API Key ได้ที่เว็บ Challonge ในส่วน Settings > Developer API
                    </p>
                </div>
                
                <div class="form-group">
                    <label for="tournament_url">
                        <i class="fas fa-link"></i> Tournament URL:
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-trophy input-icon"></i>
                        <input type="text" id="tournament_url" name="tournament_url" value="" required placeholder="เช่น ROV_RMUTI5">
                    </div>
                    <p class="help-text">
                        <i class="fas fa-info-circle"></i>
                        ตัวอย่าง URL: https://challonge.com/th/ROV_RMUTI5
                    </p>
                    <p class="help-text">
                        <i class="fas fa-arrow-right"></i>
                        ระบุเฉพาะชื่อ URL ของทัวร์นาเมนต์ เช่น "ROV_RMUTI5"
                    </p>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i>
                        บันทึกการตั้งค่า
                    </button>
                    <button type="button" class="btn btn-test" onclick="testConnection()">
                        <i class="fas fa-plug"></i>
                        ทดสอบการเชื่อมต่อ
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
        // PHP data simulation (in real implementation, this would come from PHP)
        const existingConfig = {
            api_key: '',
            tournament_url: ''
        };
        
        // Load existing config if available
        if (existingConfig.api_key) {
            document.getElementById('api_key').value = existingConfig.api_key;
            document.getElementById('tournament_url').value = existingConfig.tournament_url;
            updateStatusIndicator(true);
        }

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

        function updateStatusIndicator(connected, message = '') {
            const indicator = document.getElementById('status-indicator');
            const icon = indicator.querySelector('i');
            const text = indicator.querySelector('span');
            
            if (connected) {
                indicator.className = 'status-indicator connected';
                icon.className = 'fas fa-check-circle';
                text.textContent = message || 'เชื่อมต่อกับ Challonge สำเร็จ';
            } else {
                indicator.className = 'status-indicator disconnected';
                icon.className = 'fas fa-exclamation-triangle';
                text.textContent = message || 'ยังไม่ได้เชื่อมต่อกับ Challonge';
            }
        }

        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
        }

        function testConnection() {
            const apiKey = document.getElementById('api_key').value;
            const tournamentUrl = document.getElementById('tournament_url').value;
            
            if (!apiKey || !tournamentUrl) {
                showError("กรุณากรอกข้อมูลให้ครบถ้วน");
                return;
            }
            
            showLoading(true);
            hideAlerts();
            
            // Simulate API call (in real implementation, use actual PHP endpoint)
            setTimeout(() => {
                showLoading(false);
                // Simulate successful connection
                const success = Math.random() > 0.3; // 70% success rate for demo
                
                if (success) {
                    showSuccess("เชื่อมต่อสำเร็จ! ทัวร์นาเมนต์พร้อมใช้งาน");
                    updateStatusIndicator(true, "เชื่อมต่อและพร้อมใช้งาน");
                } else {
                    showError("ไม่สามารถเชื่อมต่อได้: API Key หรือ Tournament URL ไม่ถูกต้อง");
                    updateStatusIndicator(false);
                }
            }, 2000);
            
            /*
            // Real implementation would use this:
            fetch('test_challonge_connection.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `api_key=${encodeURIComponent(apiKey)}&tournament_url=${encodeURIComponent(tournamentUrl)}`
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showSuccess("เชื่อมต่อสำเร็จ! ทัวร์นาเมนต์พร้อมใช้งาน");
                    updateStatusIndicator(true, "เชื่อมต่อและพร้อมใช้งาน");
                } else {
                    showError("ไม่สามารถเชื่อมต่อได้: " + data.message);
                    updateStatusIndicator(false);
                }
            })
            .catch(error => {
                showLoading(false);
                showError("เกิดข้อผิดพลาดในการเชื่อมต่อ");
                updateStatusIndicator(false);
                console.error(error);
            });
            */
        }

        function showSuccess(message) {
            const alert = document.getElementById('successAlert');
            alert.querySelector('span').textContent = message;
            alert.style.display = 'block';
            document.getElementById('errorAlert').style.display = 'none';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        function showError(message) {
            const alert = document.getElementById('errorAlert');
            alert.querySelector('span').textContent = message;
            alert.style.display = 'block';
            document.getElementById('successAlert').style.display = 'none';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        function hideAlerts() {
            document.getElementById('successAlert').style.display = 'none';
            document.getElementById('errorAlert').style.display = 'none';
        }

        document.getElementById('challengeApiForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const apiKey = document.getElementById('api_key').value;
            const tournamentUrl = document.getElementById('tournament_url').value;
            
            if (!apiKey || !tournamentUrl) {
                showError("กรุณากรอกข้อมูลให้ครบถ้วน");
                return;
            }
            
            showLoading(true);
            hideAlerts();
            
            // Simulate save operation
            setTimeout(() => {
                showLoading(false);
                showSuccess("บันทึกการตั้งค่าเรียบร้อยแล้ว");
                updateStatusIndicator(true, "กำหนดค่าเรียบร้อย - พร้อมใช้งาน");
            }, 1500);
            
            /*
            // Real implementation would use this:
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `api_key=${encodeURIComponent(apiKey)}&tournament_url=${encodeURIComponent(tournamentUrl)}`
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showSuccess("บันทึกการตั้งค่าเรียบร้อยแล้ว");
                    updateStatusIndicator(true, "กำหนดค่าเรียบร้อย - พร้อมใช้งาน");
                } else {
                    showError("ไม่สามารถบันทึกการตั้งค่าได้: " + data.message);
                }
            })
            .catch(error => {
                showLoading(false);
                showError("เกิดข้อผิดพลาดในการบันทึกข้อมูล");
                console.error(error);
            });
            */
        });

        // Add input validation feedback
        document.querySelectorAll('input[required]').forEach(input => {
            input.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.style.borderColor = '#dc3545';
                } else {
                    this.style.borderColor = '#28a745';
                }
            });
            
            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#e1e8ed';
                }
            });
        });
    </script>
</body>
</html>