<?php
// เปิด error เพื่อดูปัญหา
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบว่ามีการส่งฟอร์มหรือยัง
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn = new mysqli("localhost", "root", "", "tournament_registration");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $name = $conn->real_escape_string($_POST['tournament_name']);
    $url = $conn->real_escape_string($_POST['tournament_url']);

    if (!empty($name) && !empty($url)) {
        $sql = "INSERT INTO tournaments (tournament_name, tournament_url) VALUES ('$name', '$url')";
        if ($conn->query($sql) === TRUE) {
            $message = "เพิ่มทัวร์นาเมนต์สำเร็จ!";
            $message_type = "success";
        } else {
            $message = "เกิดข้อผิดพลาด: " . $conn->error;
            $message_type = "error";
        }
    } else {
        $message = "กรุณากรอกข้อมูลให้ครบ";
        $message_type = "warning";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มทัวร์นาเมนต์ | Tournament Manager</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
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
            background: linear-gradient(135deg, #2196F3, #21CBF3);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .content {
            padding: 40px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            font-weight: 500;
            animation: fadeIn 0.5s ease-in;
        }

        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .alert.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert.warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2196F3;
            background: white;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
            transform: translateY(-2px);
        }

        .form-help {
            margin-top: 8px;
            font-size: 0.9rem;
            color: #6c757d;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            border-left: 4px solid #2196F3;
        }

        .form-help code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }

        .btn {
            background: linear-gradient(135deg, #2196F3, #21CBF3);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            justify-content: center;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(33, 150, 243, 0.3);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #6c757d;
            text-decoration: none;
            margin-top: 25px;
            padding: 10px 0;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .back-link:hover {
            color: #2196F3;
            transform: translateX(-5px);
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 1.1rem;
        }

        .input-icon input {
            padding-left: 50px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header {
                padding: 25px 20px;
            }
            
            .header h1 {
                font-size: 1.6rem;
            }
            
            .content {
                padding: 30px 25px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-trophy"></i> เพิ่มทัวร์นาเมนต์</h1>
            <p>สร้างรายการทัวร์นาเมนต์ใหม่สำหรับการแข่งขัน</p>
        </div>

        <div class="content">
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php if ($message_type === 'success'): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php elseif ($message_type === 'error'): ?>
                        <i class="fas fa-exclamation-triangle"></i>
                    <?php else: ?>
                        <i class="fas fa-info-circle"></i>
                    <?php endif; ?>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="tournament_name">
                        <i class="fas fa-gamepad"></i> ชื่อทัวร์นาเมนต์
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-trophy"></i>
                        <input type="text" 
                               name="tournament_name" 
                               id="tournament_name" 
                               required 
                               placeholder="ใส่ชื่อทัวร์นาเมนต์..."
                               value="<?php echo isset($_POST['tournament_name']) ? htmlspecialchars($_POST['tournament_name']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="tournament_url">
                        <i class="fas fa-link"></i> URL ของ Challonge
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-external-link-alt"></i>
                        <input type="text" 
                               name="tournament_url" 
                               id="tournament_url" 
                               required 
                               placeholder="ROV_RMUTI5"
                               value="<?php echo isset($_POST['tournament_url']) ? htmlspecialchars($_POST['tournament_url']) : ''; ?>">
                    </div>
                    <div class="form-help">
                        <i class="fas fa-info-circle"></i>
                        <strong>ตัวอย่าง:</strong> ถ้า URL คือ https://challonge.com/ROV_RMUTI5 ให้ใส่ <code>ROV_RMUTI5</code>
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-save"></i>
                    บันทึกทัวร์นาเมนต์
                </button>
            </form>

            <a href="select_tournament.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                กลับไปหน้าเลือกทัวร์นาเมนต์
            </a>
        </div>
    </div>

    <script>
        // เพิ่ม animation เมื่อส่งฟอร์ม
        document.querySelector('form').addEventListener('submit', function(e) {
            const button = this.querySelector('.btn');
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
            button.style.background = '#6c757d';
        });

        // Auto-hide alert หลังจาก 5 วินาที
        const alert = document.querySelector('.alert');
        if (alert && alert.classList.contains('success')) {
            setTimeout(() => {
                alert.style.animation = 'fadeOut 0.5s ease-out forwards';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        }

        // เพิ่ม CSS animation สำหรับ fadeOut
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(-20px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>