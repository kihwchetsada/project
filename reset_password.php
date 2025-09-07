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
        $stmt = $userDb->prepare("UPDATE users SET password = ? WHERE id = ?");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งรหัสผ่านใหม่ - Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 480px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .icon-container {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .icon-container i {
            font-size: 35px;
            color: white;
        }

        h1 {
            text-align: center;
            color: #333;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .input-container {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px 15px 45px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            font-family: 'Kanit', sans-serif;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 16px;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: #667eea;
        }

        .password-strength {
            margin-top: 10px;
            padding: 10px;
            border-radius: 8px;
            font-size: 12px;
            display: none;
        }

        .password-strength.weak {
            background: #fee;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
            display: block;
        }

        .password-strength.medium {
            background: #fff8e1;
            color: #f39c12;
            border-left: 4px solid #f39c12;
            display: block;
        }

        .password-strength.strong {
            background: #eafaf1;
            color: #27ae60;
            border-left: 4px solid #27ae60;
            display: block;
        }

        .password-requirements {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }

        .requirement {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }

        .requirement i {
            margin-right: 8px;
            width: 12px;
        }

        .requirement.met {
            color: #27ae60;
        }

        .requirement.unmet {
            color: #e74c3c;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Kanit', sans-serif;
            margin-top: 10px;
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .error-message {
            background: #fee;
            color: #e74c3c;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
            font-size: 14px;
        }

        .success-message {
            background: #eafaf1;
            color: #27ae60;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
            font-size: 14px;
            text-align: center;
        }

        .success-message a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-left: 10px;
            padding: 8px 16px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .success-message a:hover {
            background: #667eea;
            color: white;
        }

        .info-box {
            background: #f0f8ff;
            color: #2980b9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
            font-size: 14px;
        }

        .info-box i {
            margin-right: 8px;
        }

        .match-indicator {
            position: absolute;
            right: 45px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            display: none;
        }

        .match-indicator.match {
            color: #27ae60;
            display: block;
        }

        .match-indicator.no-match {
            color: #e74c3c;
            display: block;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .form-input {
                padding: 12px 15px 12px 40px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-container">
            <i class="fas fa-key"></i>
        </div>

        <h1>ตั้งรหัสผ่านใหม่</h1>
        <p class="subtitle">กรุณาสร้างรหัสผ่านใหม่ที่แข็งแกร่งและปลอดภัย</p>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php else: ?>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>ข้อแนะนำ:</strong> รหัสผ่านควรมีความยาวอย่างน้อย 8 ตัวอักษร และประกอบด้วยตัวพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข
        </div>

        <form method="post" id="resetForm">
            <div class="form-group">
                <label for="password">รหัสผ่านใหม่</label>
                <div class="input-container">
                    <i class="fas fa-lock input-icon"></i>
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        class="form-input"
                        placeholder="กรอกรหัสผ่านใหม่"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >
                    <i class="fas fa-eye toggle-password" data-target="password"></i>
                </div>
                
                <div class="password-strength" id="passwordStrength"></div>
                
                <div class="password-requirements" id="passwordRequirements">
                    <div class="requirement unmet" id="lengthReq">
                        <i class="fas fa-times"></i>
                        <span>อย่างน้อย 8 ตัวอักษร</span>
                    </div>
                    <div class="requirement unmet" id="uppercaseReq">
                        <i class="fas fa-times"></i>
                        <span>มีตัวพิมพ์ใหญ่</span>
                    </div>
                    <div class="requirement unmet" id="lowercaseReq">
                        <i class="fas fa-times"></i>
                        <span>มีตัวพิมพ์เล็ก</span>
                    </div>
                    <div class="requirement unmet" id="numberReq">
                        <i class="fas fa-times"></i>
                        <span>มีตัวเลข</span>
                    </div>

                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">ยืนยันรหัสผ่านใหม่</label>
                <div class="input-container">
                    <i class="fas fa-lock input-icon"></i>
                    <input 
                        type="password" 
                        id="confirm_password"
                        name="confirm_password" 
                        class="form-input"
                        placeholder="กรอกรหัสผ่านอีกครั้ง"
                        required
                        autocomplete="new-password"
                    >
                    <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                    <div class="match-indicator" id="matchIndicator">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn" disabled>
                <i class="fas fa-save"></i> บันทึกรหัสผ่านใหม่
            </button>
        </form>

        <?php endif; ?>
    </div>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            });
        });

        // Password strength checker
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        const matchIndicator = document.getElementById('matchIndicator');

        const requirements = {
            length: document.getElementById('lengthReq'),
            uppercase: document.getElementById('uppercaseReq'),
            lowercase: document.getElementById('lowercaseReq'),
            number: document.getElementById('numberReq')
        };

        function checkRequirement(element, condition) {
            if (condition) {
                element.classList.remove('unmet');
                element.classList.add('met');
                element.querySelector('i').classList.remove('fa-times');
                element.querySelector('i').classList.add('fa-check');
            } else {
                element.classList.remove('met');
                element.classList.add('unmet');
                element.querySelector('i').classList.remove('fa-check');
                element.querySelector('i').classList.add('fa-times');
            }
        }

        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('passwordStrength');
            
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);

            checkRequirement(requirements.length, hasLength);
            checkRequirement(requirements.uppercase, hasUppercase);
            checkRequirement(requirements.lowercase, hasLowercase);
            checkRequirement(requirements.number, hasNumber);

            const score = [hasLength, hasUppercase, hasLowercase, hasNumber].filter(Boolean).length;

            if (password.length === 0) {
                strengthDiv.style.display = 'none';
                return false;
            } else if (score < 3) {
                strengthDiv.className = 'password-strength weak';
                strengthDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> รหัสผ่านอ่อนแอ - ต้องปรับปรุง';
                return false;
            } else if (score < 4) {
                strengthDiv.className = 'password-strength medium';
                strengthDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> รหัสผ่านปานกลาง - ควรเพิ่มความแข็งแกร่ง';
                return false;
            } else {
                strengthDiv.className = 'password-strength strong';
                strengthDiv.innerHTML = '<i class="fas fa-shield-alt"></i> รหัสผ่านแข็งแกร่ง - ปลอดภัยดี';
                return true;
            }
        }

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmInput.value;

            if (confirmPassword.length === 0) {
                matchIndicator.style.display = 'none';
                return false;
            }

            if (password === confirmPassword) {
                matchIndicator.className = 'match-indicator match';
                matchIndicator.innerHTML = '<i class="fas fa-check"></i>';
                return true;
            } else {
                matchIndicator.className = 'match-indicator no-match';
                matchIndicator.innerHTML = '<i class="fas fa-times"></i>';
                return false;
            }
        }

        function updateSubmitButton() {
            const isPasswordStrong = checkPasswordStrength(passwordInput.value);
            const isPasswordMatch = checkPasswordMatch();
            
            if (isPasswordStrong && isPasswordMatch) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            } else {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
            }
        }

        passwordInput.addEventListener('input', updateSubmitButton);
        confirmInput.addEventListener('input', updateSubmitButton);

        // Form submission
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            if (submitBtn.disabled) {
                e.preventDefault();
                alert('กรุณาตรวจสอบรหัสผ่านให้ถูกต้องก่อนบันทึก');
            }
        });

        // Focus on first input when page loads
        window.addEventListener('load', function() {
            passwordInput.focus();
        });
    </script>
</body>
</html>