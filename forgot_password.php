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
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน - รีเซ็ตรหัสผ่าน</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .forgot-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px 30px;
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            text-align: center;
            margin-bottom: 35px;
        }

        .header i {
            font-size: 3.5rem;
            color: #667eea;
            margin-bottom: 15px;
            display: block;
        }

        .header h1 {
            color: #333;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            color: #555;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .form-control:focus + i {
            color: #667eea;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }

        .btn-submit i {
            margin-left: 8px;
            transition: transform 0.3s ease;
        }

        .btn-submit:hover i {
            transform: translateX(3px);
        }

        .error-message {
            background: linear-gradient(135deg, #ff6b6b, #ffa8a8);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            animation: shake 0.5s ease-in-out;
        }

        .error-message i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .back-link {
            text-align: center;
            margin-top: 25px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }

        .back-link a:hover {
            color: #5a67d8;
            transform: translateX(-3px);
        }

        .back-link a i {
            margin-right: 8px;
            transition: transform 0.3s ease;
        }

        .back-link a:hover i {
            transform: translateX(-2px);
        }

        .info-box {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            display: flex;
            align-items: flex-start;
        }

        .info-box i {
            margin-right: 12px;
            margin-top: 2px;
            font-size: 1.1rem;
        }

        @media (max-width: 480px) {
            .forgot-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .header h1 {
                font-size: 1.6rem;
            }

            .form-control {
                padding: 14px 14px 14px 45px;
            }

            .btn-submit {
                padding: 14px;
                font-size: 1rem;
            }
        }

        /* Loading animation */
        .btn-submit.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .btn-submit.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="header">
            <i class="fas fa-key"></i>
            <h1>ลืมรหัสผ่าน</h1>
            <p>กรอกชื่อผู้ใช้และอีเมลเพื่อรับรหัส OTP</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>ขั้นตอนการรีเซ็ตรหัสผ่าน:</strong><br>
                1. กรอกชื่อผู้ใช้และอีเมล<br>
                2. รับรหัส OTP ทางอีเมล<br>
                3. ตั้งรหัสผ่านใหม่
            </div>
        </div>

        <form method="post" action="" id="forgotForm">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้ (Username)</label>
                <div class="input-wrapper">
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           placeholder="กรอกชื่อผู้ใช้"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           required>
                    <i class="fas fa-user"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="email">อีเมล (Email)</label>
                <div class="input-wrapper">
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="กรอกอีเมลของคุณ"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           required>
                    <i class="fas fa-envelope"></i>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                ส่งรหัส OTP
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>

        <div class="back-link">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i>
                กลับไปหน้าเข้าสู่ระบบ
            </a>
        </div>
    </div>

    <script>
        // Add loading animation when form is submitted
        document.getElementById('forgotForm').addEventListener('submit', function() {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = 'กำลังส่ง...';
        });

        // Add floating label effect
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (this.value === '') {
                    this.parentElement.parentElement.classList.remove('focused');
                }
            });
            
            // Check if input has value on page load
            if (input.value !== '') {
                input.parentElement.parentElement.classList.add('focused');
            }
        });

        // Auto-hide error message after 5 seconds
        const errorMessage = document.querySelector('.error-message');
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.style.animation = 'fadeOut 0.5s ease-out forwards';
            }, 5000);
        }

        // CSS for fadeOut animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                to {
                    opacity: 0;
                    transform: translateY(-20px);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>