<?php
require 'db.php';
session_start();

if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header("Location: forgot_password.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "รหัสผ่านไม่ตรงกัน";
    } else {
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $_SESSION['reset_user_id']]);

        // เคลียร์ session ที่เกี่ยวข้อง
        unset($_SESSION['otp']);
        unset($_SESSION['otp_verified']);
        unset($_SESSION['otp_expires']);
        unset($_SESSION['reset_user_id']);

        $success = "เปลี่ยนรหัสผ่านเรียบร้อยแล้ว <a href='login.php'>เข้าสู่ระบบ</a>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เปลี่ยนรหัสผ่าน</title>
</head>
<body>
    <h2>ตั้งรหัสผ่านใหม่</h2>
    <?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>
    <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post">
        <input type="password" name="password" placeholder="รหัสผ่านใหม่" required><br>
        <input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่านใหม่" required><br>
        <button type="submit">เปลี่ยนรหัสผ่าน</button>
    </form>
</body>
</html>
