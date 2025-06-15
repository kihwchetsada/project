<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_otp = trim($_POST['otp'] ?? '');

    if (empty($input_otp)) {
        $error = "กรุณากรอกรหัส OTP";
    } elseif (!isset($_SESSION['reset_otp'], $_SESSION['reset_otp_expire']) || time() > $_SESSION['reset_otp_expire']) {
        $error = "OTP หมดอายุหรือไม่มีการขอ OTP";
    } elseif ($input_otp != $_SESSION['reset_otp']) {
        $error = "รหัส OTP ไม่ถูกต้อง";
    } else {
        // OTP ถูกต้อง
        // ล้าง OTP ออกจาก session (เพื่อความปลอดภัย)
        unset($_SESSION['reset_otp']);
        unset($_SESSION['reset_otp_expire']);
        
        // กำหนด flag ว่า OTP ผ่านแล้ว เพื่อให้เข้าหน้ารีเซ็ตรหัสผ่านได้
        $_SESSION['otp_verified'] = true;

        header("Location: reset_password.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>ยืนยัน OTP</title></head>
<body>
<h2>กรอก OTP ที่ส่งไปยังอีเมล</h2>
<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
<form method="post" action="">
    OTP: <input type="text" name="otp" maxlength="6" required><br>
    <button type="submit">ยืนยัน</button>
</form>
</body>
</html>
