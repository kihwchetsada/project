<?php
// เรียก session_start() เฉพาะเมื่อยังไม่เริ่ม session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../db_connect.php'; // ปรับ path ให้ตรงกับโครงสร้างโปรเจกต์ของคุณ

// ตรวจสอบการ login
if (!isset($_SESSION['conn'])) {
    header('Location: ../login.php');
    exit;
}

// อัปเดต last_activity
$userId = $_SESSION['conn']['id'] ?? null;
if ($userId) {
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
}
?>
