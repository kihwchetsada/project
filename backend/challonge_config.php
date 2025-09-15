<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// เชื่อมต่อฐานข้อมูล
require '../db_connect.php'; 

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ดึงข้อมูลการตั้งค่า Challonge API ถ้ามี
$config = null;
$config_sql = "SELECT * FROM challong_config LIMIT 1";
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
        background: whitesmoke;
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .header h1 {
        color: white;
        font-size: 2.5em;
        margin-bottom: 10px;
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }

    .form-container {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }
    
    .info-box {
        background: #f1f5f9;
        border-left: 5px solid #667eea;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .info-box p {
        margin: 8px 0;
        color: #37474f;
    }
    
    .form-group {
        margin-bottom: 30px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #333;
        margin-bottom: 12px;
        font-size: 1.1em;
    }

    .input-wrapper {
        position: relative;
    }

    .input-wrapper input {
        width: 100%;
        padding: 16px 50px 16px 20px;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    .input-wrapper input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .toggle-password {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #999;
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
        background: #6c757d;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .button-group {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

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
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .message.error {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-cogs"></i> ตั้งค่า Challonge API</h1>
    </div>
    
    <div class="form-container">
        <?php 
        if (isset($_GET['status'])) {
            if ($_GET['status'] == 'success') {
                echo '<div class="message success"><i class="fas fa-check-circle"></i> บันทึกการตั้งค่าเรียบร้อยแล้ว!</div>';
            } else {
                $error_message = $_GET['message'] ?? 'เกิดข้อผิดพลาดที่ไม่รู้จัก';
                echo '<div class="message error"><i class="fas fa-exclamation-triangle"></i> บันทึกไม่สำเร็จ: ' . htmlspecialchars($error_message) . '</div>';
            }
        }
        ?>

        <?php if ($config): ?>
        <div class="info-box">
            <p><strong><i class="fas fa-calendar-plus"></i> สร้างเมื่อ:</strong> <?php echo $config['created_at']; ?></p>
            <p><strong><i class="fas fa-edit"></i> แก้ไขล่าสุด:</strong> <?php echo $config['updated_at']; ?></p>
            <p><strong><i class="fas fa-key"></i> API Key ที่ใช้อยู่:</strong> <?php echo htmlspecialchars($config['api_key'] ?? ''); ?></p>
        </div>
        <?php endif; ?>

        <form id="challengeApiForm" action="save_challonge_config.php" method="post">
            <div class="form-group">
                <label for="api_key"><i class="fas fa-key"></i> API Key:</label>
                <div class="input-wrapper">
                    <input type="password" id="api_key" name="api_key" required placeholder="กรอก Challonge API Key ของคุณ">
                    <span class="toggle-password" onclick="togglePasswordVisibility('api_key')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> บันทึกการตั้งค่า
                </button>
                <a href="admin_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> กลับหน้าหลัก
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
</script>
</body>
</html>