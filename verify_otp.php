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
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยัน OTP - รีเซ็ตรหัสผ่าน</title>
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
            max-width: 450px;
            text-align: center;
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
            color: #333;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .otp-input-container {
            position: relative;
        }

        .otp-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            letter-spacing: 8px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            font-family: 'Courier New', monospace;
        }

        .otp-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .otp-input::placeholder {
            color: #bbb;
            letter-spacing: 2px;
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

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
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

        .info-box {
            background: #f0f8ff;
            color: #2980b9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
            font-size: 14px;
            text-align: left;
        }

        .info-box i {
            margin-right: 8px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #764ba2;
        }

        .back-link i {
            margin-right: 5px;
        }

        .timer {
            color: #e74c3c;
            font-weight: 600;
            font-size: 14px;
            margin-top: 10px;
        }

        .resend-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .resend-link:hover {
            color: #764ba2;
        }

        .resend-link.disabled {
            color: #ccc;
            cursor: not-allowed;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .otp-input {
                font-size: 16px;
                letter-spacing: 6px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-container">
            <i class="fas fa-shield-alt"></i>
        </div>

        <h1>ยืนยันรหัส OTP</h1>
        <p class="subtitle">กรุณากรอกรหัส OTP 6 หลักที่ส่งไปยังอีเมลของคุณ</p>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>หมายเหตุ:</strong> รหัส OTP จะหมดอายุภายใน 10 นาที หากไม่ได้รับอีเมล กรุณาตรวจสอบในโฟลเดอร์ Spam
        </div>

        <form method="post" action="" id="otpForm">
            <div class="form-group">
                <label for="otp">รหัส OTP</label>
                <div class="otp-input-container">
                    <input 
                        type="text" 
                        id="otp"
                        name="otp" 
                        class="otp-input"
                        maxlength="6" 
                        placeholder="000000"
                        pattern="[0-9]{6}"
                        required
                        autocomplete="off"
                    >
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-check"></i> ยืนยันรหัส OTP
            </button>
        </form>

        <div class="timer" id="timer" style="display: none;">
            เวลาคงเหลือ: <span id="timeLeft">10:00</span>
        </div>

        <div style="margin-top: 15px;">
            <a href="#" class="resend-link" id="resendLink" style="display: none;">
                <i class="fas fa-redo"></i> ส่งรหัส OTP ใหม่
            </a>
        </div>

        <a href="forgot_password.php" class="back-link">
            <i class="fas fa-arrow-left"></i> กลับไปหน้าลืมรหัสผ่าน
        </a>
    </div>

    <script>
        // Auto-format OTP input
        document.getElementById('otp').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            e.target.value = value;
            
            // Auto-submit when 6 digits are entered
            if (value.length === 6) {
                setTimeout(() => {
                    document.getElementById('otpForm').submit();
                }, 500);
            }
        });

        // Prevent paste of non-numeric content
        document.getElementById('otp').addEventListener('paste', function(e) {
            e.preventDefault();
            let paste = (e.clipboardData || window.clipboardData).getData('text');
            let numericPaste = paste.replace(/\D/g, '').slice(0, 6);
            e.target.value = numericPaste;
            
            if (numericPaste.length === 6) {
                setTimeout(() => {
                    document.getElementById('otpForm').submit();
                }, 500);
            }
        });

        // Countdown timer (example - adjust based on your session timeout)
        let timeLeft = 600; // 10 minutes in seconds
        const timerElement = document.getElementById('timer');
        const timeLeftElement = document.getElementById('timeLeft');
        const resendLink = document.getElementById('resendLink');

        function updateTimer() {
            if (timeLeft > 0) {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timeLeftElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                timeLeft--;
                timerElement.style.display = 'block';
            } else {
                timerElement.style.display = 'none';
                resendLink.style.display = 'inline-block';
            }
        }

        // Start timer
        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);

        // Focus on OTP input when page loads
        window.addEventListener('load', function() {
            document.getElementById('otp').focus();
        });

        // Resend OTP functionality (you'll need to implement this)
        document.getElementById('resendLink').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('คุณต้องการส่งรหัส OTP ใหม่หรือไม่?')) {
                // Redirect to resend OTP page or make AJAX request
                window.location.href = 'resend_otp.php';
            }
        });
    </script>
</body>
</html>