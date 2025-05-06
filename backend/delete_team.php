<?php
// เริ่ม session
session_start();
// ตั้งค่า timezone เป็นประเทศไทย
date_default_timezone_set('Asia/Bangkok');
// เปิดใช้งาน error reporting สำหรับการดีบัก
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
// เชื่อมต่อฐานข้อมูล
include '../db_connect.php';
// สร้างตัวแปรสำหรับเก็บข้อความแจ้งเตือน
$message = '';
$message_type = '';
// ตรวจสอบ team_id
if (!isset($_GET['team_id']) || empty($_GET['team_id'])) {
    $message = 'ไม่พบข้อมูล team_id กรุณาระบุทีมที่ต้องการลบ';
    $message_type = 'error';
} else {
    $team_id = intval($_GET['team_id']);
    
    try {
        // เริ่มทำ Transaction
        $conn->beginTransaction();
        
        // ดึงข้อมูลทีมก่อนลบ (เพื่อเก็บชื่อทีม)
        $stmt = $conn->prepare("SELECT team_name FROM teams WHERE team_id = :team_id");
        $stmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
        $stmt->execute();
        $team = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // ถ้าไม่พบทีม
        if (!$team) {
            throw new Exception('ไม่พบข้อมูลทีมที่ต้องการลบ');
        }
        
        // ลบข้อมูลสมาชิกในทีม
        $stmt = $conn->prepare("DELETE FROM team_members WHERE team_id = :team_id");
        $stmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // ลบข้อมูลทีม
        $stmt = $conn->prepare("DELETE FROM teams WHERE team_id = :team_id");
        $stmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // ยืนยัน Transaction
        $conn->commit();
        
        $message = 'ลบทีม ' . $team['team_name'] . ' และข้อมูลที่เกี่ยวข้องสำเร็จ';
        $message_type = 'success';
        
    } catch (Exception $e) {
        // ยกเลิก Transaction เมื่อเกิดข้อผิดพลาด
        $conn->rollBack();
        
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $message_type = 'error';
        
        // บันทึก error log
        error_log('Team deletion error: ' . $e->getMessage());
    }
}

// ปิดการเชื่อมต่อ
$conn = null;

// ส่วนของ HTML และการแสดงผล
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลบข้อมูลทีม</title>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .action-buttons {
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>
    <!-- Header ถูกนำออกเนื่องจากไม่พบไฟล์ -->
    
    <div class="container">
        <h1>ลบข้อมูลทีม</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="view_the_teams.php" class="btn btn-primary">กลับไปหน้าจัดการทีม</a>
        </div>
    </div>
    
    <!-- Footer ถูกนำออกเนื่องจากไม่พบไฟล์ -->
    
    <script>
        // แสดงการแจ้งเตือนแล้วเปลี่ยนหน้าอัตโนมัติหลังจาก 3 วินาที ถ้าการลบสำเร็จ
        <?php if ($message_type == 'success'): ?>
        setTimeout(function() {
            window.location.href = 'view_the_teams.php';
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>