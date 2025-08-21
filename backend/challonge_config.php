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
<title>ตั้งค่า Challonge API</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
/* CSS เดิมของคุณ */
.info-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
    font-size: 0.9em;
    color: #555;
}
.info-box strong {
    color: #333;
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
        <div class="info-box">
            <p><strong>สร้างเมื่อ:</strong> <?php echo $config['created_at']; ?></p>
            <p><strong>แก้ไขล่าสุด:</strong> <?php echo $config['updated_at']; ?></p>
        </div>
        <?php endif; ?>

        <form id="challengeApiForm" action="save_challonge_config.php" method="post">
            <div class="form-group">
                <label for="api_key">
                    <i class="fas fa-key"></i> API Key:
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="api_key" name="api_key" 
                           value="<?php echo htmlspecialchars($config['api_key'] ?? ''); ?>" required>
                    <span class="toggle-password" onclick="togglePasswordVisibility('api_key')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <p class="help-text">
                    <i class="fas fa-lightbulb"></i>
                    ค้นหา API Key ได้ที่เว็บ Challonge ในส่วน Settings > Developer API
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
</script>
</body>
</html>
