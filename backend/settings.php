<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; 

// --- 🔒 การจัดการ Session และการตรวจสอบสิทธิ์ ---
if (!isset($_SESSION['conn']) || $_SESSION['conn']['role'] !== 'participant') {
    header('Location: ../login.php');
    exit;
}

// --- ⚙️ ส่วนจัดการข้อมูล (Backend Logic) ---
$user_id = $_SESSION['conn']['id'];
$message = '';
$message_type = '';

// --- ดึงข้อมูลผู้ใช้ปัจจุบัน ---
try {
    $stmt_user = $conn->prepare("SELECT username, email, password FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        // หากไม่พบผู้ใช้ ให้ logout เพื่อความปลอดภัย
        session_destroy();
        header('Location: ../login.php');
        exit;
    }
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้: " . $e->getMessage());
}


// --- จัดการการอัปเดตข้อมูล (เมื่อมีการส่งฟอร์ม) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'CSRF token ไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
        $message_type = 'danger';
    } else {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // 1. ตรวจสอบรหัสผ่านปัจจุบัน
        if (!password_verify($current_password, $user['password'])) {
            $message = 'รหัสผ่านปัจจุบันไม่ถูกต้อง!';
            $message_type = 'danger';
        } else {
            // 2. เตรียมข้อมูลที่จะอัปเดต
            $params = [':username' => $username, ':email' => $email, ':id' => $user_id];
            $sql_update = "UPDATE users SET username = :username, email = :email";

            // 3. ตรวจสอบการเปลี่ยนรหัสผ่านใหม่
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $message = 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน';
                    $message_type = 'danger';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql_update .= ", password = :password";
                    $params[':password'] = $hashed_password;
                }
            }

            // 4. ถ้าไม่มี error ให้ทำการอัปเดตฐานข้อมูล
            if (empty($message)) {
                try {
                    $sql_update .= " WHERE id = :id";
                    $stmt = $conn->prepare($sql_update);
                    $stmt->execute($params);

                    // อัปเดตข้อมูลใน Session ด้วย
                    $_SESSION['conn']['username'] = $username;

                    $message = 'บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว!';
                    $message_type = 'success';

                    // ดึงข้อมูลใหม่หลังจากอัปเดต
                    $stmt_user->execute([$user_id]);
                    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

                } catch (PDOException $e) {
                    $message = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
        }
    }
}

// สร้าง CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าบัญชี | ระบบจัดการแข่งขัน ROV</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .settings-container { max-width: 700px; margin: auto; }
        .form-section { background: white; border-radius: 8px; padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .form-section h3 { font-size: 1.25rem; font-weight: 600; padding-bottom: 15px; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 10px; color: #111827; }
        .form-field { margin-bottom: 20px; }
        .form-field label { display: block; margin-bottom: 8px; font-weight: 500; color: #374151; }
        .form-field input { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; transition: all 0.2s; font-family: 'Kanit', sans-serif; box-sizing: border-box; }
        .form-field input:focus { border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.4); outline: none; }
        .password-note { font-size: 0.85rem; color: #6b7280; margin-top: 8px; }
        .submit-button { background: var(--primary-color); color: white; width: 100%; padding: 12px 25px; border-radius: 6px; font-size: 1rem; font-weight: 600; text-align: center; border: none; cursor: pointer; transition: background 0.2s; }
        .submit-button:hover { background: #1e40af; }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header"><div class="logo"><i class="fas fa-trophy"></i><span>ROV Tournament</span></div></div>
        <div class="sidebar-menu">
            <ul>
                <li><a href="participant_dashboard.php"><i class="fas fa-home"></i><span>หน้าหลัก</span></a></li>
                <li><a href="Certificate/index.php"><i class="fas fa-ranking-star"></i><span>เกียรติบัตร</span></a></li>
                <li><a href="settings.php" class="active"><i class="fas fa-cog"></i><span>ตั้งค่า</span></a></li>
                <li><a href="?logout=1"><i class="fas fa-sign-out-alt"></i><span>ออกจากระบบ</span></a></li>
            </ul>
        </div>
    </div>
    <div class="main-content">
        <div class="top-navbar">
            <button class="mobile-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="user-menu"><div class="user-info"><span><?php echo htmlspecialchars($_SESSION['conn']['username']); ?></span><div class="user-avatar"><i class="fas fa-user"></i></div></div></div>
        </div>
        <div class="dashboard-container">
            <div class="welcome-header">
                <h2><i class="fas fa-user-cog"></i> ตั้งค่าบัญชี</h2>
                <p>จัดการข้อมูลส่วนตัวและรหัสผ่านของคุณ</p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="settings-container">
                <form action="settings.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-section">
                        <h3><i class="fas fa-user-edit"></i> ข้อมูลทั่วไป</h3>
                        <div class="form-field">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน</h3>
                        <p class="password-note">หากไม่ต้องการเปลี่ยนรหัสผ่าน ให้เว้นว่างในส่วนนี้</p>
                        <div class="form-field">
                            <label for="new_password">รหัสผ่านใหม่:</label>
                            <input type="password" id="new_password" name="new_password">
                        </div>
                        <div class="form-field">
                            <label for="confirm_password">ยืนยันรหัสผ่านใหม่:</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-lock"></i> ยืนยันตัวตน</h3>
                         <div class="form-field">
                            <label for="current_password">กรอกรหัสผ่านปัจจุบันเพื่อบันทึก:</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <button type="submit" name="update_settings" class="submit-button"><i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="dashboard-footer"><p>&copy; <?php echo date('Y'); ?> ระบบจัดการการแข่งขัน ROV.</p></div>
    </div>
    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mainContent = document.querySelector('.main-content');
        sidebarToggle.addEventListener('click', (e) => { e.stopPropagation(); sidebar.classList.toggle('sidebar-active'); });
        mainContent.addEventListener('click', () => { if (sidebar.classList.contains('sidebar-active')) { sidebar.classList.remove('sidebar-active'); } });
    </script>
</body>
</html>