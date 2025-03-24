<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Dummy credentials for demonstration purposes
    $valid_username = 'admin';
    $valid_password = 'password';

    if ($username === $valid_username && $password === $valid_password) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
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
                <a href="#" class="forgot-password">ลืมรหัสผ่าน?</a>
            </div>
            
            <button type="submit" class="login-button">เข้าสู่ระบบ <i class="fas fa-sign-in-alt"></i></button>
        </form>
        <div class="register-link">
            ยังไม่มีบัญชี? <a href="#">สมัครสมาชิก</a>
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