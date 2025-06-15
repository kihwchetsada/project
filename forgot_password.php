<?php
session_start();
require 'db.php'; // เชื่อม DB
require 'vendor/autoload.php'; // PHPMailer (ใช้ Composer โหลด)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!$username || !$email) {
        $error = "กรุณากรอก Username และ Email ให้ครบ";
    } else {
        // ตรวจสอบว่ามี username+email ตรงกันใน DB ไหม
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND email = ?");
        $stmt->execute([$username, $email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "ไม่พบข้อมูลผู้ใช้หรืออีเมลไม่ตรงกับบัญชี";
        } else {
            // สร้าง OTP 6 หลัก
            $otp = random_int(100000, 999999);
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_otp_expire'] = time() + 300; // หมดอายุ 5 นาที

            // ส่งเมล OTP
            $mail = new PHPMailer(true);
            try {
                // ตั้งค่า SMTP (แก้ตามเซิร์ฟเวอร์อีเมลของคุณ)
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';  // SMTP server ของคุณ
                $mail->SMTPAuth = true;
                $mail->Username = 'thepopyth15@gmail.com'; // เมลผู้ส่ง
                $mail->Password = 'rhja tnpg agih fmcg'; // รหัสผ่านแอป (App Password) ของ Gmail
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('thepopyth15@gmail.com', 'ระบบลืมรหัสผ่าน');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'รหัส OTP สำหรับรีเซ็ตรหัสผ่าน';
                $mail->Body = "รหัส OTP ของคุณคือ <b>$otp</b> ใช้ได้ภายใน 5 นาที";

                $mail->send();

                header("Location: verify_otp.php");
                exit;
            } catch (Exception $e) {
                $error = "ส่งอีเมลไม่สำเร็จ: " . $mail->ErrorInfo;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>ลืมรหัสผ่าน</title></head>
<body>
<h2>ลืมรหัสผ่าน - กรอก Username และ Email</h2>
<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
<form method="post" action="">
    Username: <input type="text" name="username" required><br>
    Email: <input type="email" name="email" required><br>
    <button type="submit">ส่ง OTP</button>
</form>
</body>
</html>
