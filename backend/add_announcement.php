<?php
session_start();

// ✅ ตรวจสอบสิทธิ์ผู้ใช้
if (!isset($_SESSION['userData']) || $_SESSION['userData']['role'] !== 'organizer') {
    die('❌ คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// ✅ เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "announcements_db");
$conn->set_charset("utf8");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title']));
    $description = htmlspecialchars(trim($_POST['description']));
    $category = htmlspecialchars(trim($_POST['category']));
    $priority = htmlspecialchars(trim($_POST['priority']));
    $status = 'active';

    $imagePath = null;

    // ✅ จัดการรูปภาพถ้ามีอัปโหลด
    if (!empty($_FILES['image']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $fileType = $_FILES['image']['type'];
        $fileSize = $_FILES['image']['size'];
        $tmpName = $_FILES['image']['tmp_name'];
        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetDir = '../uploads/';
        $targetPath = $targetDir . $fileName;

        // ✅ ตรวจสอบประเภทไฟล์และขนาด
        if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            if (move_uploaded_file($tmpName, $targetPath)) {
                $imagePath = $fileName;
            } else {
                die("❌ ไม่สามารถอัปโหลดรูปภาพได้");
            }
        } else {
            die("❌ ประเภทไฟล์หรือขนาดไฟล์ไม่ถูกต้อง (รองรับ JPG, PNG, GIF และไม่เกิน 5MB)");
        }
    }

    // ✅ เพิ่มข้อมูลลงฐานข้อมูล
    $stmt = $conn->prepare("INSERT INTO announcements (title, description, category, priority, image_path, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssss", $title, $description, $category, $priority, $imagePath, $status);

    if ($stmt->execute()) {
        header("Location: annunciate.php?added=1");
        exit;
    } else {
        echo "❌ เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error;
    }
}
?>
