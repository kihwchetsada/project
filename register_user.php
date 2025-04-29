<?php
require 'db.php';
session_start();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // ตรวจสอบว่า username ซ้ำหรือไม่
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $error = "ชื่อผู้ใช้นี้ถูกใช้ไปแล้ว";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashedPassword, $role);

        if ($stmt->execute()) {
            $success = "สมัครสมาชิกสำเร็จ! <a href='login.php'>เข้าสู่ระบบ</a>";
        } else {
            $error = "เกิดข้อผิดพลาดขณะสมัครสมาชิก";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-user-plus"></i>
        </div>
        <h2>สมัครสมาชิก</h2>

        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php elseif ($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" action="" class="login-form">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้</label>
                <div class="input-with-icon">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="กรอกชื่อผู้ใช้" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <div class="input-with-icon">
                    <i class="input-icon fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="กรอกรหัสผ่าน" required>
                </div>
            </div>

            <div class="form-group">
                <label for="role">ประเภทผู้ใช้</label>
                <div class="input-with-icon">
                    <i class="input-icon fas fa-user-tag"></i>
                    <select id="role" name="role" required>
                        <option value="">-- เลือกประเภทผู้ใช้ --</option>
                        <option value="admin">ผู้ดูแลระบบ</option>
                        <option value="organizer">ผู้จัดการแข่งขัน</option>
                        <option value="participant">ผู้เข้าร่วมการแข่งขัน</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="login-button">สมัครสมาชิก <i class="fas fa-user-check"></i></button>
        </form>

        <div class="register-link">
            มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a>
        </div>
    </div>
</body>
</html>
