<?php
session_start();

// ตรวจสอบการ logout
if (isset($_GET['logout'])) {
    if (isset($_SESSION['userData']['id'])) {
        require_once '../db.php';
        $userId = $_SESSION['userData']['id'];
        $stmt = $userDb->prepare("UPDATE users SET last_activity = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    }
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['userData']) || $_SESSION['userData']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$userData = $_SESSION['userData'];
$message = '';
$error = '';

// จัดการการบันทึกการตั้งค่า
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../db.php';
    
    try {
        if (isset($_POST['update_site_settings'])) {
            // อัพเดตการตั้งค่าเว็บไซต์
            $site_name = $_POST['site_name'] ?? '';
            $site_description = $_POST['site_description'] ?? '';
            $contact_email = $_POST['contact_email'] ?? '';
            $max_team_members = $_POST['max_team_members'] ?? 5;
            $registration_deadline = $_POST['registration_deadline'] ?? '';
            
            // บันทึกลงฐานข้อมูล (สมมติว่ามีตาราง site_settings)
            $stmt = $userDb->prepare("INSERT INTO site_settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $settings = [
                'site_name' => $site_name,
                'site_description' => $site_description,
                'contact_email' => $contact_email,
                'max_team_members' => $max_team_members,
                'registration_deadline' => $registration_deadline
            ];
            
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, $value, $value]);
            }
            
            $message = 'บันทึกการตั้งค่าเว็บไซต์เรียบร้อยแล้ว';
        }
        
        if (isset($_POST['update_tournament_settings'])) {
            // อัพเดตการตั้งค่าทัวร์นาเมนต์
            $tournament_name = $_POST['tournament_name'] ?? '';
            $tournament_format = $_POST['tournament_format'] ?? '';
            $match_duration = $_POST['match_duration'] ?? 30;
            $prize_pool = $_POST['prize_pool'] ?? '';
            $tournament_rules = $_POST['tournament_rules'] ?? '';
            
            $stmt = $userDb->prepare("INSERT INTO site_settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $tournament_settings = [
                'tournament_name' => $tournament_name,
                'tournament_format' => $tournament_format,
                'match_duration' => $match_duration,
                'prize_pool' => $prize_pool,
                'tournament_rules' => $tournament_rules
            ];
            
            foreach ($tournament_settings as $key => $value) {
                $stmt->execute([$key, $value, $value]);
            }
            
            $message = 'บันทึกการตั้งค่าทัวร์นาเมนต์เรียบร้อยแล้ว';
        }
        
        if (isset($_POST['update_system_settings'])) {
            // อัพเดตการตั้งค่าระบบ
            $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
            $auto_backup = isset($_POST['auto_backup']) ? 1 : 0;
            $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
            $max_file_size = $_POST['max_file_size'] ?? 5;
            
            $stmt = $userDb->prepare("INSERT INTO site_settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $system_settings = [
                'maintenance_mode' => $maintenance_mode,
                'auto_backup' => $auto_backup,
                'email_notifications' => $email_notifications,
                'max_file_size' => $max_file_size
            ];
            
            foreach ($system_settings as $key => $value) {
                $stmt->execute([$key, $value, $value]);
            }
            
            $message = 'บันทึกการตั้งค่าระบบเรียบร้อยแล้ว';
        }
        
    } catch (Exception $e) {
        $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}

// ดึงการตั้งค่าปัจจุบัน
$current_settings = [];
try {
    require_once '../db.php';
    $stmt = $userDb->prepare("SELECT setting_name, setting_value FROM site_settings");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $current_settings[$row['setting_name']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // ถ้าไม่มีตาราง site_settings ให้ใช้ค่าเริ่มต้น
    $current_settings = [
        'site_name' => 'ROV Tournament',
        'site_description' => 'ระบบจัดการการแข่งขัน ROV',
        'contact_email' => 'admin@rovtournament.com',
        'max_team_members' => 5,
        'registration_deadline' => '',
        'tournament_name' => 'ROV Championship 2025',
        'tournament_format' => 'Single Elimination',
        'match_duration' => 30,
        'prize_pool' => '100,000',
        'tournament_rules' => '',
        'maintenance_mode' => 0,
        'auto_backup' => 1,
        'email_notifications' => 1,
        'max_file_size' => 5
    ];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>ตั้งค่า | ระบบจัดการการแข่งขัน ROV</title>
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .settings-tabs {
            display: flex;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .tab-button {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-family: 'Kanit', sans-serif;
            font-size: 16px;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            color: #2196F3;
            border-bottom-color: #2196F3;
        }
        
        .tab-button:hover {
            color: #2196F3;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .settings-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Kanit', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2196F3;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
            transform: scale(1.2);
        }
        
        .save-button {
            background: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Kanit', sans-serif;
            font-size: 16px;
            transition: background 0.3s ease;
        }
        
        .save-button:hover {
            background: #45a049;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-family: 'Kanit', sans-serif;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .form-row .form-group {
            flex: 1;
            min-width: 250px;
        }
        
        .danger-zone {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 6px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .danger-zone h3 {
            color: #e53e3e;
            margin-bottom: 15px;
        }
        
        .danger-button {
            background: #e53e3e;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Kanit', sans-serif;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .danger-button:hover {
            background: #c53030;
        }
        
        @media (max-width: 768px) {
            .settings-tabs {
                flex-direction: column;
            }
            
            .tab-button {
                text-align: left;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .form-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-trophy"></i>
                <span>ROV Tournament</span>
            </div>
            <button class="mobile-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="sidebar-menu">
            <ul>
                <li>
                    <a href="admin_dashboard.php"><i class="fas fa-home"></i><span>หน้าหลัก</span></a>
                </li>
                <li>
                    <a href="manage_user.php"><i class="fas fa-chart-pie"></i><span>จัดการสิทธิ์ผู้ใช้งาน</span></a>
                </li>
                <li>
                    <a href="admin_view_the_teams.php"><i class="fas fa-users"></i><span>จัดการทีม</span></a>
                </li>
                <li>
                    <a href="https://challonge.com/th/dashboard"><i class="fas fa-calendar-days"></i><span>ตารางการแข่งขัน</span></a>
                </li>
                <li>
                    <a href="results.php"><i class="fas fa-ranking-star"></i><span>ผลการแข่งขัน</span></a>
                </li>
                <li>
                    <a href="stats.php"><i class="fas fa-chart-bar"></i><span>สถิติ</span></a>
                </li>
                <li class="active">
                    <a href="settings.php"><i class="fas fa-cog"></i><span>ตั้งค่า</span></a>
                </li>
                <li>
                    <a href="?logout=1"><i class="fas fa-sign-out-alt"></i><span>ออกจากระบบ</span></a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <div class="top-navbar">
            <div class="user-menu">
                <div class="user-info">
                    <?php include 'header.php'; ?>
                    <span><?php echo htmlspecialchars($userData['username']); ?></span>
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="settings-container">
            <div class="welcome-header">
                <h2><i class="fas fa-cog"></i> ตั้งค่าระบบ</h2>
                <p>จัดการการตั้งค่าต่างๆ ของระบบ ROV Tournament</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Settings Tabs -->
            <div class="settings-tabs">
                <button class="tab-button active" data-tab="site">
                    <i class="fas fa-globe"></i> ตั้งค่าเว็บไซต์
                </button>
                <button class="tab-button" data-tab="tournament">
                    <i class="fas fa-trophy"></i> ตั้งค่าทัวร์นาเมนต์
                </button>
                <button class="tab-button" data-tab="system">
                    <i class="fas fa-server"></i> ตั้งค่าระบบ
                </button>
                <button class="tab-button" data-tab="backup">
                    <i class="fas fa-database"></i> สำรองข้อมูล
                </button>
            </div>

            <!-- Site Settings Tab -->
            <div class="tab-content active" id="site">
                <div class="settings-section">
                    <h3><i class="fas fa-globe"></i> ตั้งค่าเว็บไซต์</h3>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="site_name">ชื่อเว็บไซต์</label>
                                <input type="text" id="site_name" name="site_name" 
                                       value="<?php echo htmlspecialchars($current_settings['site_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="contact_email">อีเมลติดต่อ</label>
                                <input type="email" id="contact_email" name="contact_email" 
                                       value="<?php echo htmlspecialchars($current_settings['contact_email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_description">คำอธิบายเว็บไซต์</label>
                            <textarea id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($current_settings['site_description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="max_team_members">จำนวนสมาชิกสูงสุดต่อทีม</label>
                                <input type="number" id="max_team_members" name="max_team_members" 
                                       value="<?php echo htmlspecialchars($current_settings['max_team_members'] ?? '5'); ?>" min="1" max="10">
                            </div>
                            <div class="form-group">
                                <label for="registration_deadline">วันสิ้นสุดการลงทะเบียน</label>
                                <input type="datetime-local" id="registration_deadline" name="registration_deadline" 
                                       value="<?php echo htmlspecialchars($current_settings['registration_deadline'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" name="update_site_settings" class="save-button">
                            <i class="fas fa-save"></i> บันทึกการตั้งค่า
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tournament Settings Tab -->
            <div class="tab-content" id="tournament">
                <div class="settings-section">
                    <h3><i class="fas fa-trophy"></i> ตั้งค่าทัวร์นาเมนต์</h3>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="tournament_name">ชื่อทัวร์นาเมนต์</label>
                                <input type="text" id="tournament_name" name="tournament_name" 
                                       value="<?php echo htmlspecialchars($current_settings['tournament_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="tournament_format">รูปแบบการแข่งขัน</label>
                                <select id="tournament_format" name="tournament_format">
                                    <option value="Single Elimination" <?php echo ($current_settings['tournament_format'] ?? '') === 'Single Elimination' ? 'selected' : ''; ?>>Single Elimination</option>
                                    <option value="Double Elimination" <?php echo ($current_settings['tournament_format'] ?? '') === 'Double Elimination' ? 'selected' : ''; ?>>Double Elimination</option>
                                    <option value="Round Robin" <?php echo ($current_settings['tournament_format'] ?? '') === 'Round Robin' ? 'selected' : ''; ?>>Round Robin</option>
                                    <option value="Swiss" <?php echo ($current_settings['tournament_format'] ?? '') === 'Swiss' ? 'selected' : ''; ?>>Swiss</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="match_duration">ระยะเวลาการแข่งขัน (นาที)</label>
                                <input type="number" id="match_duration" name="match_duration" 
                                       value="<?php echo htmlspecialchars($current_settings['match_duration'] ?? '30'); ?>" min="10" max="120">
                            </div>
                            <div class="form-group">
                                <label for="prize_pool">เงินรางวัล (บาท)</label>
                                <input type="text" id="prize_pool" name="prize_pool" 
                                       value="<?php echo htmlspecialchars($current_settings['prize_pool'] ?? ''); ?>" placeholder="เช่น 100,000">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="tournament_rules">กฎการแข่งขัน</label>
                            <textarea id="tournament_rules" name="tournament_rules" rows="5"><?php echo htmlspecialchars($current_settings['tournament_rules'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_tournament_settings" class="save-button">
                            <i class="fas fa-save"></i> บันทึกการตั้งค่า
                        </button>
                    </form>
                </div>
            </div>

            <!-- System Settings Tab -->
            <div class="tab-content" id="system">
                <div class="settings-section">
                    <h3><i class="fas fa-server"></i> ตั้งค่าระบบ</h3>
                    <form method="POST">
                        <div class="checkbox-group">
                            <input type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                   <?php echo ($current_settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                            <label for="maintenance_mode">เปิดโหมดปรับปรุงระบบ</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="auto_backup" name="auto_backup" 
                                   <?php echo ($current_settings['auto_backup'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="auto_backup">สำรองข้อมูลอัตโนมัติ</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="email_notifications" name="email_notifications" 
                                   <?php echo ($current_settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="email_notifications">ส่งการแจ้งเตือนทางอีเมล</label>
                        </div>
                        
                        <div class="form-group">
                            <label for="max_file_size">ขนาดไฟล์สูงสุด (MB)</label>
                            <input type="number" id="max_file_size" name="max_file_size" 
                                   value="<?php echo htmlspecialchars($current_settings['max_file_size'] ?? '5'); ?>" min="1" max="100">
                        </div>
                        
                        <button type="submit" name="update_system_settings" class="save-button">
                            <i class="fas fa-save"></i> บันทึกการตั้งค่า
                        </button>
                    </form>
                </div>
            </div>

            <!-- Backup Tab -->
            <div class="tab-content" id="backup">
                <div class="settings-section">
                    <h3><i class="fas fa-database"></i> การสำรองข้อมูล</h3>
                    <p>จัดการการสำรองข้อมูลและการกู้คืนข้อมูล</p>
                    
                    <div class="form-group">
                        <button type="button" class="save-button" onclick="createBackup()">
                            <i class="fas fa-download"></i> สำรองข้อมูลทันที
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label>ไฟล์สำรองข้อมูลล่าสุด</label>
                        <div style="background: #f5f5f5; padding: 15px; border-radius: 6px;">
                            <p><strong>ไฟล์:</strong> backup_<?php echo date('Y-m-d_H-i-s'); ?>.sql</p>
                            <p><strong>วันที่:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                            <p><strong>ขนาด:</strong> 2.5 MB</p>
                        </div>
                    </div>
                    
                    <div class="danger-zone">
                        <h3><i class="fas fa-exclamation-triangle"></i> พื้นที่อันตราย</h3>
                        <p>การดำเนินการต่อไปนี้จะส่งผลกระทบอย่างมากต่อระบบ กรุณาใช้ความระมัดระวัง</p>
                        
                        <button type="button" class="danger-button" onclick="confirmAction('clear_logs')">
                            <i class="fas fa-trash"></i> ล้างไฟล์ Log
                        </button>
                        
                        <button type="button" class="danger-button" onclick="confirmAction('reset_stats')">
                            <i class="fas fa-chart-bar"></i> รีเซ็ตสถิติ
                        </button>
                        
                        <button type="button" class="danger-button" onclick="confirmAction('factory_reset')">
                            <i class="fas fa-undo"></i> รีเซ็ตระบบ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all tabs and buttons
                document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                
                // Add active class to clicked button and corresponding content
                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });

        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('sidebar-active');
        });

        // Close sidebar when clicking main content on mobile
        document.querySelector('.main-content').addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                document.querySelector('.sidebar').classList.remove('sidebar-active');
            }
        });

        // Backup function
        function createBackup() {
            if (confirm('คุณต้องการสำรองข้อมูลทันทีหรือไม่?')) {
                // แสดงการโหลด
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังสำรอง...';
                button.disabled = true;
                
                // จำลองการสำรองข้อมูล
                setTimeout(() => {
                    alert('สำรองข้อมูลเรียบร้อยแล้ว!');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 2000);
            }
        }

        // Confirm dangerous actions
        function confirmAction(action) {
            let message = '';
            switch(action) {
                case 'clear_logs':
                    message = 'คุณต้องการลบไฟล์ Log ทั้งหมดหรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้';
                    break;
                case 'reset_stats':
                    message = 'คุณต้องการรีเซ็ตสถิติทั้งหมดหรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้';
                    break;
                case 'factory_reset':
                    message = 'คุณต้องการรีเซ็ตระบบกลับสู่สถานะเริ่มต้นหรือไม่? ข้อมูลทั้งหมดจะถูกลบ!';
                    break;
            }
            
            if (confirm(message)) {
                // แสดงการโหลด
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังดำเนินการ...';
                button.disabled = true;
                
                // จำลองการดำเนินการ
                setTimeout(() => {
                    alert('ดำเนินการเรียบร้อยแล้ว!');
                    button.innerHTML = originalText;
                    button.disabled = false;
                    
                    // รีเฟรชหน้าสำหรับบางการดำเนินการ
                    if (action === 'factory_reset') {
                        location.reload();
                    }
                }, 2000);
            }
        }

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.style.borderColor = '#e53e3e';
                    } else {
                        field.style.borderColor = '#ddd';
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                }
            });
        });

        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            }, 5000);
        });

        // Character counter for textarea
        document.querySelectorAll('textarea').forEach(textarea => {
            const maxLength = textarea.getAttribute('maxlength');
            if (maxLength) {
                const counter = document.createElement('div');
                counter.className = 'char-counter';
                counter.style.cssText = 'text-align: right; color: #666; font-size: 12px; margin-top: 5px;';
                textarea.parentNode.appendChild(counter);
                
                function updateCounter() {
                    const remaining = maxLength - textarea.value.length;
                    counter.textContent = `${textarea.value.length}/${maxLength} ตัวอักษร`;
                    counter.style.color = remaining < 50 ? '#e53e3e' : '#666';
                }
                
                textarea.addEventListener('input', updateCounter);
                updateCounter();
            }
        });

        // Maintenance mode warning
        document.getElementById('maintenance_mode').addEventListener('change', function() {
            if (this.checked) {
                alert('⚠️ เมื่อเปิดโหมดปรับปรุงระบบ ผู้ใช้ทั่วไปจะไม่สามารถเข้าถึงเว็บไซต์ได้');
            }
        });

        // Prize pool formatting
        document.getElementById('prize_pool').addEventListener('input', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value) {
                value = parseInt(value).toLocaleString('th-TH');
                this.value = value;
            }
        });

        // Real-time settings preview
        document.getElementById('site_name').addEventListener('input', function() {
            document.title = this.value + ' | ระบบจัดการการแข่งขัน ROV';
        });

        // File size validation
        document.getElementById('max_file_size').addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value > 50) {
                alert('ขนาดไฟล์สูงสุดไม่ควรเกิน 50 MB เพื่อประสิทธิภาพของระบบ');
                this.value = 50;
            }
        });

        // Auto-save draft (สำหรับฟอร์มที่ใหญ่)
        let autoSaveTimeout;
        document.querySelectorAll('textarea, input[type="text"]').forEach(field => {
            field.addEventListener('input', function() {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(() => {
                    // บันทึกลง localStorage (ถ้าไม่ได้ใช้ใน artifact)
                    console.log('Auto-saving draft...');
                }, 2000);
            });
        });
    </script>
</body>
</html>