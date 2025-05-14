<?php
// เรียก session_start() เฉพาะเมื่อยังไม่เริ่ม session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../db.php'; // ปรับ path ให้ตรงกับโครงสร้างโปรเจกต์ของคุณ

// ตรวจสอบการ login
if (!isset($_SESSION['userData'])) {
    header('Location: ../login.php');
    exit;
}

// อัปเดต last_activity
$userId = $_SESSION['userData']['id'] ?? null;
if ($userId) {
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}
?>
