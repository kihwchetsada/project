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
    <title>ตั้งค่า Challonge API</title>
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #3a00b0;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn {
            background-color: #3a00b0;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: inline-block;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #2a0080;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .alert {
            padding: 10px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }
        .alert-error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .help-text {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
        .hide-password {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ตั้งค่า Challonge API</h1>
        
        <div id="successAlert" class="alert">
            บันทึกการตั้งค่าเรียบร้อยแล้ว
        </div>
        
        <div id="errorAlert" class="alert alert-error">
            เกิดข้อผิดพลาด กรุณาตรวจสอบข้อมูลอีกครั้ง
        </div>
        
        <form id="challengeApiForm" action="save_challonge_config.php" method="post">
            <div class="form-group">
                <label for="api_key">API Key:</label>
                <div class="hide-password">
                    <input type="password" id="api_key" name="api_key" value="<?php echo $config ? htmlspecialchars($config['api_key']) : ''; ?>" required>
                    <span class="toggle-password" onclick="togglePasswordVisibility('api_key')">แสดง</span>
                </div>
                <p class="help-text">ค้นหา API Key ได้ที่เว็บ Challonge ในส่วน Settings > Developer API</p>
            </div>
            
            <div class="form-group">
                <label for="tournament_url">Tournament URL:</label>
                <input type="text" id="tournament_url" name="tournament_url" value="<?php echo $config ? htmlspecialchars($config['tournament_url']) : ''; ?>" required>
                <p class="help-text">ตัวอย่างเช่น https://challonge.com/th/ROV_RMUTI5</p>
                <p class="help-text">ระบุเฉพาะชื่อ URL ของทัวร์นาเมนต์ เช่น "ROV_RMUTI5"</p>
                
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">บันทึกการตั้งค่า</button>
                <button type="button" class="btn btn-secondary" onclick="testConnection()">ทดสอบการเชื่อมต่อ</button>
                <a href="admin_dashboard.php" class="btn btn-secondary">กลับหน้าหลัก</a>
            </div>
        </form>
    </div>

    <script>
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const toggleBtn = field.nextElementSibling;
            
            if (field.type === "password") {
                field.type = "text";
                toggleBtn.textContent = "ซ่อน";
            } else {
                field.type = "password";
                toggleBtn.textContent = "แสดง";
            }
        }

        function testConnection() {
            const apiKey = document.getElementById('api_key').value;
            const tournamentUrl = document.getElementById('tournament_url').value;
            
            if (!apiKey || !tournamentUrl) {
                showError("กรุณากรอกข้อมูลให้ครบถ้วน");
                return;
            }
            
            // ส่งข้อมูลไปทดสอบการเชื่อมต่อ
            fetch('test_challonge_connection.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `api_key=${encodeURIComponent(apiKey)}&tournament_url=${encodeURIComponent(tournamentUrl)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess("เชื่อมต่อสำเร็จ! ทัวร์นาเมนต์พร้อมใช้งาน");
                } else {
                    showError("ไม่สามารถเชื่อมต่อได้: " + data.message);
                }
            })
            .catch(error => {
                showError("เกิดข้อผิดพลาดในการเชื่อมต่อ");
                console.error(error);
            });
        }

        function showSuccess(message) {
            const alert = document.getElementById('successAlert');
            alert.textContent = message;
            alert.style.display = 'block';
            document.getElementById('errorAlert').style.display = 'none';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        function showError(message) {
            const alert = document.getElementById('errorAlert');
            alert.textContent = message;
            alert.style.display = 'block';
            document.getElementById('successAlert').style.display = 'none';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        document.getElementById('challengeApiForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const apiKey = document.getElementById('api_key').value;
            const tournamentUrl = document.getElementById('tournament_url').value;
            
            if (!apiKey || !tournamentUrl) {
                showError("กรุณากรอกข้อมูลให้ครบถ้วน");
                return;
            }
            
            // ส่งข้อมูลไปบันทึก
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `api_key=${encodeURIComponent(apiKey)}&tournament_url=${encodeURIComponent(tournamentUrl)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess("บันทึกการตั้งค่าเรียบร้อยแล้ว");
                } else {
                    showError("ไม่สามารถบันทึกการตั้งค่าได้: " + data.message);
                }
            })
            .catch(error => {
                showError("เกิดข้อผิดพลาดในการบันทึกข้อมูล");
                console.error(error);
            });
        });
    </script>
</body>
</html>