<?php

require 'db.php';
/** @var mysqli $conn */
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE verify_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $updateStmt = $conn->prepare("UPDATE users SET verified = 1, verify_token = NULL WHERE verify_token = ?");
        $updateStmt->bind_param("s", $token);
        $updateStmt->execute();
        echo "ยืนยันอีเมลเรียบร้อยแล้ว! <a href='login.php'>เข้าสู่ระบบ</a>";
    } else {
        echo "ลิงก์ไม่ถูกต้องหรือหมดอายุ";
    }
} else {
    echo "ไม่มี token ยืนยัน";
}
?>