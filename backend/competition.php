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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/r.css">
</head>
<body>
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

            <form method="POST" action="update_competition.php">
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
                    </label>
                    <label for="is_open"><i class="fas fa-door-open"></i> เปิดรับสมัคร</label>
                </div>

                <input type="hidden" name="id" value="<?= $competitions_1['id'] ?>">
                
                <div class="button-container">
                    <button type="submit" class="button">
                        <i class="fas fa-save"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>

        <div class="footer">
            <p>ระบบจัดการการแข่งขัน - เวอร์ชัน 1.9.0</p>
            <p>© <?php echo date('Y'); ?> สงวนลิขสิทธิ์</p>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // ตรวจสอบวันที่
        document.getElementById('end_date').addEventListener('change', function() {
            const startDate = document.getElementById('start_date').value;
            const endDate = this.value;
            
            if(startDate && endDate < startDate) {
                alert('วันที่สิ้นสุดต้องมากกว่าวันที่เริ่ม');
                this.value = '';
            }
        });

        // ตรวจสอบวันที่เริ่มด้วย
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = this.value;
            const endDate = document.getElementById('end_date').value;
            
            if(endDate && startDate > endDate) {
                alert('วันที่เริ่มต้องน้อยกว่าวันที่สิ้นสุด');
                document.getElementById('end_date').value = '';
            }
        });
    });
    </script>
</body>
</html>