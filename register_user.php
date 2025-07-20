<?php
require 'db.php';
session_start();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role']; // รับค่าจากฟอร์ม

    try {
        // ตรวจสอบว่า username ซ้ำหรือไม่
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($checkResult) {
            // ตรวจสอบว่าซ้ำเพราะ username หรือ email
            $checkUsernameStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $checkUsernameStmt->execute([$username]);
            $usernameExists = $checkUsernameStmt->fetch(PDO::FETCH_ASSOC);
            
            $checkEmailStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmailStmt->execute([$email]);
            $emailExists = $checkEmailStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usernameExists && $emailExists) {
                $error = "ชื่อผู้ใช้และอีเมลนี้ถูกใช้ไปแล้ว";
            } elseif ($usernameExists) {
                $error = "ชื่อผู้ใช้นี้ถูกใช้ไปแล้ว";
            } else {
                $error = "อีเมลนี้ถูกใช้ไปแล้ว";
            }
        } else {
            // ตรวจสอบรูปแบบอีเมล
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "รูปแบบอีเมลไม่ถูกต้อง";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $hashedPassword, $role])) {
                    $user_id = $conn->lastInsertId();

                    $_SESSION['loggedin'] = true;
                    $_SESSION['userData'] = [
                        'id' => $user_id,
                        'username' => $username,
                        'email' => $email,
                        'role' => $role
                    ];

                    // เปลี่ยนเส้นทางตาม role
                    if ($role === 'organizer') {
                        header("Location: backend/organizer_dashboard.php");
                    } else {
                        header("Location: backend/participant_dashboard.php");
                    }
                    exit;
                } else {
                    $error = "เกิดข้อผิดพลาดขณะสมัครสมาชิก";
                }
            }
        }
    } catch (PDOException $e) {
        $error = "เกิดข้อผิดพลาดในการดำเนินการ: " . $e->getMessage();
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
    <style>
        .role-selection {
            margin-bottom: 20px;
        }
        
        .role-options {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        
        .role-option {
            flex: 1;
            position: relative;
        }
        
        .role-option input[type="radio"] {
            display: none;
        }
        
        .role-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
            transform: scale(1);
        }
        
        .role-card:hover {
            background: #e8f4f8;
            border-color: #007bff;
            transform: scale(1.02);
        }
        
        .role-card.selected {
            background: #007bff;
            border-color: #007bff;
            color: white;
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
        }
        
        .role-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #007bff;
            transition: all 0.3s ease;
        }
        
        .role-card.selected .role-icon {
            color: white;
            animation: pulse 1.5s infinite;
        }
        
        .role-title {
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .role-description {
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .form-validation-error {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 5px;
            display: none;
        }
        
        .input-error {
            border-color: #dc3545 !important;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.875em;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        
        .password-requirements {
            margin-top: 8px;
            font-size: 0.8em;
            color: #6c757d;
        }
        
        .password-requirements ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        
        .password-requirements li {
            margin: 3px 0;
            transition: color 0.3s ease;
        }
        
        .password-requirements li.valid {
            color: #28a745;
        }
        
        .password-requirements li.valid::before {
            content: "✓ ";
            font-weight: bold;
        }
        
        .password-requirements li.invalid::before {
            content: "✗ ";
            font-weight: bold;
        }
        
        .password-toggle {
            position: absolute;
            right: 40px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
            padding: 5px;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #007bff;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .confirm-password-match {
            margin-top: 5px;
            font-size: 0.875em;
            display: none;
        }
        
        .match-success {
            color: #28a745;
        }
        
        .match-error {
            color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .role-options {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
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
            <div class="role-selection">
                <label>ประเภทสมาชิก</label>
                <div class="role-options">
                    <div class="role-option">
                        <input type="radio" id="participant" name="role" value="participant" 
                               <?php echo (!isset($_POST['role']) || $_POST['role'] === 'participant') ? 'checked' : ''; ?>>
                        <div class="role-card" data-role="participant">
                            <div class="role-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="role-title">ผู้เข้าร่วมแข่งขัน</div>
                            <div class="role-description">เข้าร่วมการแข่งขัน<br>ส่งผลงาน</div>
                        </div>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="organizer" name="role" value="organizer" 
                               <?php echo (isset($_POST['role']) && $_POST['role'] === 'organizer') ? 'checked' : ''; ?>>
                        <div class="role-card" data-role="organizer">
                            <div class="role-icon">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="role-title">ผู้จัดการแข่งขัน</div>
                            <div class="role-description">สร้างการแข่งขัน<br>จัดการรายการ</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="username">ชื่อผู้ใช้</label>
                <div class="input-with-icon">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="กรอกชื่อผู้ใช้" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required maxlength="50">
                </div>
                <div class="form-validation-error" id="username-error"></div>
            </div>

            <div class="form-group">
                <label for="email">อีเมล</label>
                <div class="input-with-icon">
                    <i class="input-icon fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="กรอกอีเมล" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                <div class="form-validation-error" id="email-error"></div>
            </div>

            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <div class="input-with-icon">
                    <i class="input-icon fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="กรอกรหัสผ่าน" required>
                    <i class="password-toggle fas fa-eye" id="togglePassword"></i>
                </div>
                <div class="password-strength" id="password-strength"></div>
                <div class="password-requirements" id="password-requirements">
                    <small>รหัสผ่านต้องมี:</small>
                    <ul>
                        <li id="length-req" class="invalid">อย่างน้อย 8 ตัวอักษร</li>
                        <li id="lowercase-req" class="invalid">ตัวอักษรพิมพ์เล็ก (a-z)</li>
                        <li id="uppercase-req" class="invalid">ตัวอักษรพิมพ์ใหญ่ (A-Z)</li>
                        <li id="number-req" class="invalid">ตัวเลข (0-9)</li>
                        <li id="special-req" class="invalid">สัญลักษณ์พิเศษ (!@#$%^&*)</li>
                    </ul>
                </div>
                <div class="form-validation-error" id="password-error"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">ยืนยันรหัสผ่าน</label>
                <div class="input-with-icon">
                    <i class="input-icon fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="กรอกรหัสผ่านอีกครั้ง" required>
                    <i class="password-toggle fas fa-eye" id="toggleConfirmPassword"></i>
                </div>
                <div class="confirm-password-match" id="confirm-password-match"></div>
                <div class="form-validation-error" id="confirm_password-error"></div>
            </div>

            <button type="submit" class="login-button">สมัครสมาชิก <i class="fas fa-user-check"></i></button>
        </form>

        <div class="register-link">
            มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a>
        </div>
    </div>

    <script>
        // เริ่มต้นการทำงาน
        document.addEventListener('DOMContentLoaded', function() {
            initializeRoleSelection();
            initializeFormValidation();
            initializePasswordStrength();
            initializePasswordToggle();
            initializePasswordConfirmation();
        });

        // จัดการการเลือก Role
        function initializeRoleSelection() {
            const roleCards = document.querySelectorAll('.role-card');
            const roleInputs = document.querySelectorAll('input[name="role"]');
            
            // ตั้งค่าเริ่มต้น
            updateRoleSelection();
            
            roleCards.forEach(card => {
                card.addEventListener('click', function() {
                    const role = this.getAttribute('data-role');
                    const radioInput = document.getElementById(role);
                    
                    // เปลี่ยนค่า radio
                    radioInput.checked = true;
                    
                    // อัพเดตสไตล์
                    updateRoleSelection();
                });
            });
        }

        function updateRoleSelection() {
            const selectedRole = document.querySelector('input[name="role"]:checked').value;
            const roleCards = document.querySelectorAll('.role-card');
            
            roleCards.forEach(card => {
                const cardRole = card.getAttribute('data-role');
                if (cardRole === selectedRole) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            });
        }

        // การตรวจสอบฟอร์ม
        function initializeFormValidation() {
            const form = document.querySelector('.login-form');
            const inputs = form.querySelectorAll('input[required]');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                
                input.addEventListener('input', function() {
                    clearError(this);
                });
            });
            
            form.addEventListener('submit', function(e) {
                let hasError = false;
                
                inputs.forEach(input => {
                    if (!validateField(input)) {
                        hasError = true;
                    }
                });
                
                if (hasError) {
                    e.preventDefault();
                }
            });
        }

        function validateField(field) {
            const fieldId = field.id;
            const value = field.value.trim();
            let isValid = true;
            let errorMessage = '';
            
            // ตรวจสอบความยาวขั้นต่ำ
            if (value.length === 0) {
                isValid = false;
                errorMessage = 'กรุณากรอกข้อมูล';
            } else {
                switch (fieldId) {
                    case 'username':
                        if (value.length < 3) {
                            isValid = false;
                            errorMessage = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร';
                        } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                            isValid = false;
                            errorMessage = 'ชื่อผู้ใช้ใช้ได้เฉพาะตัวอักษร ตัวเลข และ _';
                        }
                        break;
                    case 'email':
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(value)) {
                            isValid = false;
                            errorMessage = 'รูปแบบอีเมลไม่ถูกต้อง';
                        }
                        break;
                    case 'password':
                        if (value.length < 8) {
                            isValid = false;
                            errorMessage = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
                        } else if (!isPasswordStrong(value)) {
                            isValid = false;
                            errorMessage = 'รหัสผ่านไม่ผ่านเงื่อนไขที่กำหนด';
                        }
                        break;
                    case 'confirm_password':
                        const passwordValue = document.getElementById('password').value;
                        if (value !== passwordValue) {
                            isValid = false;
                            errorMessage = 'รหัสผ่านไม่ตรงกัน';
                        }
                        break;
                }
            }
            
            if (!isValid) {
                showError(field, errorMessage);
            } else {
                clearError(field);
            }
            
            return isValid;
        }

        function showError(field, message) {
            const errorElement = document.getElementById(field.id + '-error');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
                field.classList.add('input-error');
            }
        }

        function clearError(field) {
            const errorElement = document.getElementById(field.id + '-error');
            if (errorElement) {
                errorElement.style.display = 'none';
                field.classList.remove('input-error');
            }
        }

        // ตรวจสอบความแข็งแรงของรหัสผ่าน
        function initializePasswordStrength() {
            const passwordInput = document.getElementById('password');
            const strengthElement = document.getElementById('password-strength');
            const requirementsElement = document.getElementById('password-requirements');
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strength = calculatePasswordStrength(password);
                updatePasswordStrength(strengthElement, strength);
                updatePasswordRequirements(password);
            });
            
            // แสดงคำแนะนำเมื่อ focus
            passwordInput.addEventListener('focus', function() {
                requirementsElement.style.display = 'block';
            });
        }

        function calculatePasswordStrength(password) {
            let score = 0;
            
            if (password.length >= 8) score += 2;
            if (password.length >= 12) score += 1;
            if (/[a-z]/.test(password)) score += 1;
            if (/[A-Z]/.test(password)) score += 1;
            if (/[0-9]/.test(password)) score += 1;
            if (/[^A-Za-z0-9]/.test(password)) score += 1;
            
            if (score <= 3) return 'weak';
            if (score <= 5) return 'medium';
            return 'strong';
        }

        function updatePasswordStrength(element, strength) {
            element.className = 'password-strength';
            
            switch (strength) {
                case 'weak':
                    element.classList.add('strength-weak');
                    element.textContent = '🔴 ความปลอดภัย: อ่อน';
                    break;
                case 'medium':
                    element.classList.add('strength-medium');
                    element.textContent = '🟡 ความปลอดภัย: ปานกลาง';
                    break;
                case 'strong':
                    element.classList.add('strength-strong');
                    element.textContent = '🟢 ความปลอดภัย: แข็งแรง';
                    break;
            }
        }

        function updatePasswordRequirements(password) {
            const requirements = [
                { id: 'length-req', test: password.length >= 8 },
                { id: 'lowercase-req', test: /[a-z]/.test(password) },
                { id: 'uppercase-req', test: /[A-Z]/.test(password) },
                { id: 'number-req', test: /[0-9]/.test(password) },
                { id: 'special-req', test: /[^A-Za-z0-9]/.test(password) }
            ];
            
            requirements.forEach(req => {
                const element = document.getElementById(req.id);
                if (req.test) {
                    element.classList.remove('invalid');
                    element.classList.add('valid');
                } else {
                    element.classList.remove('valid');
                    element.classList.add('invalid');
                }
            });
        }

        function isPasswordStrong(password) {
            return password.length >= 8 &&
                   /[a-z]/.test(password) &&
                   /[A-Z]/.test(password) &&
                   /[0-9]/.test(password) &&
                   /[^A-Za-z0-9]/.test(password);
        }

        // การแสดง/ซ่อน รหัสผ่าน
        function initializePasswordToggle() {
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            togglePassword.addEventListener('click', function() {
                togglePasswordVisibility(passwordInput, this);
            });
            
            toggleConfirmPassword.addEventListener('click', function() {
                togglePasswordVisibility(confirmPasswordInput, this);
            });
        }

        function togglePasswordVisibility(input, toggleIcon) {
            if (input.type === 'password') {
                input.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // การยืนยันรหัสผ่าน
        function initializePasswordConfirmation() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const matchElement = document.getElementById('confirm-password-match');
            
            confirmPasswordInput.addEventListener('input', function() {
                checkPasswordMatch();
            });
            
            passwordInput.addEventListener('input', function() {
                if (confirmPasswordInput.value.length > 0) {
                    checkPasswordMatch();
                }
            });
            
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword.length === 0) {
                    matchElement.style.display = 'none';
                    return;
                }
                
                matchElement.style.display = 'block';
                
                if (password === confirmPassword) {
                    matchElement.className = 'confirm-password-match match-success';
                    matchElement.innerHTML = '<i class="fas fa-check"></i> รหัสผ่านตรงกัน';
                } else {
                    matchElement.className = 'confirm-password-match match-error';
                    matchElement.innerHTML = '<i class="fas fa-times"></i> รหัสผ่านไม่ตรงกัน';
                }
            }
        }
    </script>
</body>
</html>