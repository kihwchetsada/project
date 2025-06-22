<?php

session_start();

require 'db.php'; // เชื่อมต่อฐานข้อมูล

// สร้าง CSRF Token ถ้ายังไม่มี
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ตรวจสอบ CSRF Token ก่อน
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token ไม่ถูกต้อง');
    }

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password'])) {

            // รีเซ็ต token ใหม่หลัง login สำเร็จ (ป้องกัน reuse)
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            $_SESSION['loggedin'] = true;
            $_SESSION['userData'] = [
                'username' => $user['username'],
                'role' => $user['role'],
                'id' => $user['id']
            ];

            switch ($user['role']) {
                case 'admin':
                    header('Location: backend/admin_dashboard.php');
                    break;
                case 'organizer':
                    header('Location: backend/organizer_dashboard.php');
                    break;
                case 'participant':
                    header('Location: backend/participant_dashboard.php');
                    break;
                default:
                    $error = "ไม่พบสิทธิ์ผู้ใช้ที่เกี่ยวข้อง";
                    break;
            }

            exit;
        } else {
            $error = 'รหัสผ่านไม่ถูกต้อง';
        }
    } else {
        $error = 'ไม่พบบัญชีผู้ใช้';
    }
}
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>เข้าสู่ระบบ</title>
</head>
<body>
    
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-user-shield"></i>
        </div>
        <h2>เข้าสู่ระบบ</h2>
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <form method="post" action="" class="login-form">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้</label>
                <div class="input-with-icon">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="กรุณาใส่ชื่อผู้ใช้" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <div class="input-with-icon">
                    <i class="input-icon fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="กรุณาใส่รหัสผ่าน" required>
                    <i class="toggle-password fas fa-eye-slash" tabindex="0"></i>
                </div>
            </div>
            
            <div class="form-options">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">จดจำฉัน</label>
                </div>
                <a href="forgot_password.php" class="forgot-password">ลืมรหัสผ่าน?</a>
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <button type="submit" class="login-button">เข้าสู่ระบบ <i class="fas fa-sign-in-alt"></i></button><br>
            
            <button type="button" onclick="window.location.href='index.php'" class="login-button">กลับไปที่หน้าหลัก<i class="fas fa-sign-out-alt"></i></button>
        </form>
        <div class="register-link">
            ยังไม่มีบัญชี? <a href="register_user.php">สมัครสมาชิก</a>
        </div>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const passwordInput = this.previousElementSibling; 
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>