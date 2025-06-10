<?php
require 'db.php';
require 'vendor/autoload.php'; // ถ้าใช้ PHPMailer ติดตั้งผ่าน Composer

use PHPMailer\PHPMailer\PHPMailer;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);
    $role = "participant";

    // ตรวจสอบว่าชื่อผู้ใช้หรืออีเมลซ้ำหรือไม่
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);

    if ($stmt->rowCount() > 0) {
        $error = "ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้ไปแล้ว";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $verify_token = bin2hex(random_bytes(16)); // สร้าง token

        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, verify_token)
                                VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $hashedPassword, $email, $role, $verify_token])) {

            // ส่งอีเมลยืนยัน
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = 'smtp.example.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'your_email@example.com';
            $mail->Password = 'your_password';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('your_email@example.com', 'ระบบลงทะเบียน');
            $mail->addAddress($email);
            $mail->Subject = 'ยืนยันอีเมล';
            $mail->isHTML(true);
            $mail->Body = "คลิกที่ลิงก์นี้เพื่อยืนยันการสมัคร: 
            <a href='http://localhost/project/verify.php?token=$verify_token'>ยืนยันอีเมล</a>";

            if ($mail->send()) {
                $success = "สมัครสำเร็จ! กรุณาตรวจสอบอีเมลเพื่อยืนยันก่อนเข้าสู่ระบบ";
            } else {
                $error = "สมัครสำเร็จ แต่ไม่สามารถส่งอีเมลยืนยันได้: " . $mail->ErrorInfo;
            }

        } else {
            $error = "เกิดข้อผิดพลาดขณะบันทึกข้อมูล";
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
                <label for="email">อีเมล</label>
                <div class="input-with-icon">
                    <i class="input-icon fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="กรอกอีเมล" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <div class="input-with-icon">
                    <i class="input-icon fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="กรอกรหัสผ่าน" required>
                </div>
            </div>

            <button type="submit" class="login-button">สมัครสมาชิก <i class="fas fa-user-check"></i></button>
            <br>
            
            <button type="button" onclick="window.location.href='login.php'" class="login-button">กลับไปที่หน้า login<i class="fas fa-sign-out-alt"></i></button>
        </form>

        <div class="register-link">
            มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a>
        </div>

    </div>
</body>
</html>