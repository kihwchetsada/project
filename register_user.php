<?php
require 'db.php';
session_start();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = "participant";

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

                    header("Location: backend/participant_dashboard.php");
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
                    <input type="text" id="username" name="username" placeholder="กรอกชื่อผู้ใช้" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">อีเมล</label>
                <div class="input-with-icon">
                    <i class="input-icon fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="กรอกอีเมล" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
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
        </form>

        <div class="register-link">
            มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a>
        </div>
    </div>
</body>
</html>