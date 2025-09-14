<?php
require '../db_connect.php';
session_start();

// ตรวจสอบว่าเป็นแอดมินหรือไม่
if (!isset($_SESSION['conn']) || $_SESSION['conn']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// กำหนดค่าเริ่มต้นสำหรับข้อความแจ้งเตือน
$success = '';
$error = '';

// ตรวจสอบการ logout
if (isset($_GET['logout'])) {
    session_destroy(); // เคลียร์ session ทั้งหมด
    header('Location: ../login.php'); // กลับไปหน้า login
    exit;
}

// ดำเนินการเปลี่ยนแปลงสิทธิ์
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];

    // อนุญาตเฉพาะการเปลี่ยนเป็น participant หรือ organizer เท่านั้น
    if ($new_role === 'participant' || $new_role === 'organizer') {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);

        if ($stmt->rowCount()) {
            $success = "อัปเดตสิทธิ์ผู้ใช้เรียบร้อย";
        } else {
            $error = "ไม่พบผู้ใช้หรือไม่มีการเปลี่ยนแปลง";
        }
    } else {
        $error = "สิทธิ์ไม่ถูกต้อง";
    }
}

// ดึงข้อมูลผู้ใช้ทั้งหมด (ยกเว้นแอดมิน)
$stmt = $conn->prepare("SELECT id, username, role, last_activity FROM users WHERE role != 'admin' ORDER BY username");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการผู้ใช้</title>
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .users-table th, .users-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .users-table th {
            background-color: #f7f7f7;
            font-weight: 500;
        }

        .users-table tr:hover {
            background-color: #f5f5f5;
        }

        .role-select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            width: 100%;
        }

        .update-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .update-btn:hover {
            background-color: #45a049;
        }

        .user-role {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
        }

        .role-participant {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        .role-organizer {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #333;
            text-decoration: none;
        }

        .back-link i {
            margin-right: 5px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
        }

        .role-online {
            background-color: #e0f7fa;
            color: #00796b;
        }

        .role-offline {
            background-color: #f3e5f5;
            color: #6a1b9a;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="admin_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> กลับไปยังหน้าแดชบอร์ด</a>
        <h2><i class="fas fa-users-cog"></i> จัดการผู้ใช้งาน</h2>

        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (count($users) > 0): ?>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ชื่อผู้ใช้</th>
                        <th>สิทธิ์ปัจจุบัน</th>
                        <th>เปลี่ยนสิทธิ์</th>
                        <th>การดำเนินการ</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td>
                                <span class="user-role <?php echo 'role-' . $user['role']; ?>">
                                    <?php 
                                        if ($user['role'] === 'participant') {
                                            echo 'ผู้เข้าร่วมการแข่งขัน';
                                        } elseif ($user['role'] === 'organizer') {
                                            echo 'ผู้จัดการแข่งขัน';
                                        }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" action="">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="new_role" class="role-select">
                                        <option value="participant" <?php echo ($user['role'] === 'participant') ? 'selected' : ''; ?>>ผู้เข้าร่วมการแข่งขัน</option>
                                        <option value="organizer" <?php echo ($user['role'] === 'organizer') ? 'selected' : ''; ?>>ผู้จัดการแข่งขัน</option>
                                    </select>
                            </td>
                            <td>
                                    <button type="submit" name="update_role" class="update-btn">
                                        <i class="fas fa-save"></i> บันทึก
                                    </button>
                                </form>
                            </td>
                            <td>
                                <?php
                                $isOnline = false;
                                if (!empty($user['last_activity'])) {
                                    $last = strtotime($user['last_activity']);
                                    $now = time();
                                    $diff = $now - $last;
                                    if ($diff <= 300) {
                                        $isOnline = true;
                                    }
                                }
                                ?>
                                <span class="user-role <?php echo $isOnline ? 'role-online' : 'role-offline'; ?>">
                                    <?php echo $isOnline ? '🟢 ออนไลน์' : '⚪ ออฟไลน์'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>ยังไม่มีผู้ใช้งานในระบบ</p>
        <?php endif; ?>
    </div>
</body>
</html>
