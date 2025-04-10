<?php
// ตั้งค่า timezone เป็นประเทศไทย
date_default_timezone_set('Asia/Bangkok');

// เปิดใช้งาน error reporting สำหรับการดีบัก
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// เริ่ม session ก่อนมีการส่งข้อมูลใดๆ
session_start(); 

// เชื่อมต่อฐานข้อมูล
include '../db_connect.php';

// กำหนดที่อัปโหลดไฟล์
$upload_dir = 'uploads/';
$message = "";
$status = "";

// ตรวจสอบว่ามีการส่ง team_id มาหรือไม่
if (isset($_GET['team_id']) && !empty($_GET['team_id'])) {
    $team_id = intval($_GET['team_id']);
    
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // ดึงข้อมูลทีมเพื่อเก็บชื่อสำหรับแสดงข้อความ
        $stmt = $conn->prepare("SELECT team_name FROM teams WHERE team_id = :team_id");
        $stmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
        $stmt->execute();
        $team = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($team) {
            $team_name = $team['team_name'];
            
            // ดึงรายการสมาชิกเพื่อลบไฟล์รูปที่เกี่ยวข้อง
            $stmt = $conn->prepare("SELECT id_card_image FROM team_members WHERE team_id = :team_id");
            $stmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
            $stmt->execute();
            $members_with_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ลบไฟล์รูปของสมาชิกทีม (ถ้ามี)
            foreach ($members_with_images as $member) {
                if (!empty($member['id_card_image'])) {
                    $image_path = $upload_dir . $member['id_card_image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
            }
            
            // ลบข้อมูลสมาชิกทีมจากฐานข้อมูล
            $stmt = $conn->prepare("DELETE FROM team_members WHERE team_id = :team_id");
            $stmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // ลบข้อมูลทีม
            $stmt = $conn->prepare("DELETE FROM teams WHERE team_id = :team_id");
            $stmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // บันทึก transaction
            $conn->commit();
            
            $message = "ลบทีม \"" . htmlspecialchars($team_name) . "\" และสมาชิกทั้งหมดเรียบร้อยแล้ว";
            $status = "success";
        } else {
            $message = "ไม่พบข้อมูลทีมที่ต้องการลบ";
            $status = "error";
        }
    } catch (PDOException $e) {
        // ยกเลิก transaction ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        $message = "เกิดข้อผิดพลาดในการลบทีม: " . $e->getMessage();
        $status = "error";
        error_log("Delete Team Error: " . $e->getMessage());
    }
} else {
    $message = "ไม่ระบุรหัสทีมที่ต้องการลบ";
    $status = "error";
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn = null;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลบทีม - ระบบข้อมูลทีม</title>
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/view_the_teams.css">
</head>
<body>
    <div class="result-container">
        <h1><i class="fas fa-trash"></i> ผลการลบทีม</h1>
        
        <?php if ($status == "success"): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php else: ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <a href="view_the_teams.php" class="back-button">
            <i class="fas fa-arrow-left"></i> กลับไปหน้ารายชื่อทีม
        </a>
    </div>
</body>
</html>