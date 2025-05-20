<?php

// ตั้งค่า timezone เป็นประเทศไทย
date_default_timezone_set('Asia/Bangkok');

// เปิดใช้งาน error reporting สำหรับการดีบัก
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// เริ่ม session ก่อนมีการส่งข้อมูลใดๆ
session_start(); 

// 🔒 ตรวจสอบการ logout
if (isset($_GET['logout'])) {
    session_destroy(); // เคลียร์ session ทั้งหมด
    header('Location: ../login.php'); // กลับไปหน้า login
    exit;
}

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
include '../db_connect.php';

// กำหนดที่อัปโหลดไฟล์
$upload_dir = 'uploads/';

// ฟังก์ชันถอดรหัสรูปภาพ
function decryptImage($encrypted_file, $encryption_key, $iv, $tag) {
    // เพิ่มการบันทึก log เพื่อดีบัก
    error_log("Attempting to decrypt: " . $encrypted_file);
    error_log("Key exists: " . (!empty($encryption_key) ? "Yes" : "No"));
    error_log("IV exists: " . (!empty($iv) ? "Yes" : "No"));
    error_log("Tag exists: " . (!empty($tag) ? "Yes" : "No"));

    // เพิ่มการตรวจสอบพื้นฐาน
    if (!file_exists($encrypted_file)) {
        error_log("ไฟล์ " . $encrypted_file . " ไม่มีอยู่");
        return false;
    }

    // ตรวจสอบว่าคีย์เข้ารหัสไม่ว่าง
    if (empty($encryption_key) || empty($iv)) {
        error_log("คีย์การเข้ารหัสไม่ครบถ้วน");
        return false;
    }

    try {
        // แปลง key, iv กลับจาก base64
        $decoded_key = base64_decode($encryption_key);
        $decoded_iv = base64_decode($iv);
        $decoded_tag = !empty($tag) ? base64_decode($tag) : null;

        // ตรวจสอบการแปลง base64
        if ($decoded_key === false || $decoded_iv === false) {
            error_log("การแปลง base64 ล้มเหลว");
            return false;
        }

        // โหลดข้อมูลไฟล์ที่เข้ารหัส
        $encrypted_data = file_get_contents($encrypted_file);
        
        if ($encrypted_data === false) {
            error_log("ไม่สามารถอ่านไฟล์ที่เข้ารหัส: " . $encrypted_file);
            return false;
        }

        // กำหนดไซเฟอร์ตามการมีอยู่ของ tag
        $cipher = !empty($decoded_tag) ? 'aes-256-gcm' : 'aes-256-cbc';
        error_log("ใช้ไซเฟอร์: " . $cipher);

        // ถอดรหัสข้อมูล
        if ($cipher == 'aes-256-gcm') {
            $decrypted_data = openssl_decrypt(
                $encrypted_data,
                $cipher,
                $decoded_key,
                OPENSSL_RAW_DATA,
                $decoded_iv,
                $decoded_tag
            );
        } else {
            $decrypted_data = openssl_decrypt(
                $encrypted_data,
                $cipher,
                $decoded_key,
                OPENSSL_RAW_DATA,
                $decoded_iv
            );
        }

        // ตรวจสอบการถอดรหัส
        if ($decrypted_data === false) {
            error_log("การถอดรหัสล้มเหลว: " . openssl_error_string());
            return false;
        }

        error_log("Decryption successful");
        return $decrypted_data;

    } catch (Exception $e) {
        error_log("เกิดข้อผิดพลาดในการถอดรหัสภาพ: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันถอดรหัสเบอร์โทรศัพท์
function decryptPhone($encrypted_phone, $phone_key, $phone_iv) {
    if (empty($encrypted_phone) || empty($phone_key) || empty($phone_iv)) {
        return "N/A";
    }
    
    try {
        $key = base64_decode($phone_key);
        $iv = base64_decode($phone_iv);
        
        $decrypted_phone = openssl_decrypt(
            $encrypted_phone,
            'aes-256-cbc',
            $key,
            0,
            $iv
        );
        
        if ($decrypted_phone === false) {
            error_log("การถอดรหัสเบอร์โทรล้มเหลว: " . openssl_error_string());
            return "เบอร์โทรไม่สามารถแสดงได้";
        }
        
        return $decrypted_phone;
    } catch (Exception $e) {
        error_log("เกิดข้อผิดพลาดในการถอดรหัสเบอร์โทร: " . $e->getMessage());
        return "เบอร์โทรไม่สามารถแสดงได้";
    }
}

// สร้างตัวแปรเก็บข้อความ Log
$log_message = "";
$selected_team = isset($_GET['team']) ? intval($_GET['team']) : null;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';

try {
    // ดึงข้อมูลทีมทั้งหมด
    $teams_data = [];
    $filtered_teams = [];
    $competition_types = [];
    
    // ดึงประเภทการแข่งขันทั้งหมดที่มีในระบบ
    $stmt = $conn->prepare("SELECT DISTINCT competition_type FROM teams ORDER BY competition_type ASC");
    $stmt->execute();
    $competition_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // สร้าง SQL query ตามเงื่อนไขการค้นหา
    if (!empty($search_query)) {
        // ค้นหาตามชื่อทีม
        $sql = "SELECT * FROM teams WHERE team_name LIKE :search_query";
        $params = [':search_query' => '%' . $search_query . '%'];
        
        // เพิ่มเงื่อนไขหมวดหมู่ถ้ามีการเลือก
        if (!empty($selected_category)) {
            $sql .= " AND competition_type = :category";
            $params[':category'] = $selected_category;
        }
        
        $sql .= " ORDER BY competition_type ASC, team_name ASC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    } elseif (!empty($selected_category)) {
        // กรองตามประเภทการแข่งขัน
        $stmt = $conn->prepare("SELECT * FROM teams WHERE competition_type = :category ORDER BY team_name ASC");
        $stmt->bindValue(':category', $selected_category, PDO::PARAM_STR);
    } else {
        // ดึงทีมทั้งหมด
        $stmt = $conn->prepare("SELECT * FROM teams ORDER BY competition_type ASC, team_name ASC");
    }
    
    $stmt->execute();
    $teams_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดกลุ่มทีมตามประเภทการแข่งขัน
    $teams_by_category = [];
    foreach ($teams_data as $team) {
        $category = $team['competition_type'];
        if (!isset($teams_by_category[$category])) {
            $teams_by_category[$category] = [];
        }
        $teams_by_category[$category][] = $team;
    }
    
    $log_message .= "📂 โหลดข้อมูลทีมทั้งหมด " . count($teams_data) . " ทีม<br>";
    
} catch (PDOException $e) {
    $log_message = "⚠️ ข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage();
    error_log("Database Error: " . $e->getMessage());
}

// ตัวแปรสำหรับเก็บข้อมูลทีมที่เลือกและสมาชิก
$team_data = null;
$members = [];

// ดึงข้อมูลทีมที่เลือกถ้ามี team_id
if (!empty($selected_team)) {
    try {
        // ดึงข้อมูลทีมที่เลือกตาม team_id
        $stmt = $conn->prepare("SELECT * FROM teams WHERE team_id = :team_id");
        $stmt->bindParam(':team_id', $selected_team, PDO::PARAM_INT);
        $stmt->execute();
        $team_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($team_data) {
            // ดึงข้อมูลสมาชิกของทีม
            $stmt = $conn->prepare("SELECT * FROM team_members WHERE team_id = :team_id ORDER BY member_id ASC");
            $stmt->bindParam(':team_id', $selected_team, PDO::PARAM_INT);
            $stmt->execute();
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $log_message .= "🧑‍🤝‍🧑 โหลดข้อมูลสมาชิกทีม " . htmlspecialchars($team_data['team_name']) . " จำนวน " . count($members) . " คน<br>";
        } else {
            $log_message .= "⚠️ ไม่พบข้อมูลทีมที่เลือก (ID: " . $selected_team . ")<br>";
        }
    } catch (PDOException $e) {
        $log_message .= "⚠️ ข้อผิดพลาดในการดึงข้อมูลทีม: " . $e->getMessage() . "<br>";
        error_log("Team Fetch Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo !empty($team_data) ? htmlspecialchars($team_data['team_name']) . ' - ' : ''; ?>ข้อมูลทีมและรูปภาพ</title>
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/view_the_teams.css">
</head>
<body>
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> ระบบข้อมูลทีม</h1>
            <p>ระบบแสดงข้อมูลทีมและเอกสารประจำตัวของสมาชิก</p>
        </div>

        <?php if (!empty($log_message)): ?>
        <div class="log-container">
            <code><?php echo $log_message; ?></code>
        </div>
        <?php endif; ?>

        <div class="card">
            <a href="admin_dashboard.php" class="card-title"><i class="fas fa-sign-out-alt"></i> กลับไปหน้าหลัก</a>
        </div>

        <div class="card">
            <h2 class="card-title"><i class="fas fa-search"></i> ค้นหาทีม</h2>
            <form action="" method="GET" class="search-container">
                <input type="text" name="search" class="search-input" placeholder="พิมพ์ชื่อทีมที่ต้องการค้นหา..." value="<?php echo htmlspecialchars($search_query); ?>">
                <?php if (!empty($selected_category)): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
                <?php endif; ?>
                
                <button type="submit" class="search-button"><i class="fas fa-search"></i> ค้นหา</button>
                <?php if (!empty($search_query) || !empty($selected_category)): ?>
                <a href="?" class="reset-button"><i class="fas fa-times"></i> ล้าง</a>
                <?php endif; ?>
            </form>

            <?php if (!empty($search_query)): ?>
            <div class="search-results">
                <i class="fas fa-info-circle"></i> ผลการค้นหา "<?php echo htmlspecialchars($search_query); ?>": พบ <?php echo count($teams_data); ?> ทีม
                <?php if (!empty($selected_category)): ?>
                (ในประเภท: <?php echo htmlspecialchars($selected_category); ?>)
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- ตัวกรองประเภทการแข่งขัน -->
        <div class="card">
            <h2 class="card-title"><i class="fas fa-filter"></i> กรองตามประเภทการแข่งขัน</h2>
            <div class="category-filter">
                <a href="?" class="category-btn <?php echo empty($selected_category) ? 'active' : ''; ?>">
                    <i class="fas fa-globe"></i> ทั้งหมด
                </a>
                
                <?php foreach ($competition_types as $type): ?>
                <a href="?category=<?php echo urlencode($type); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                   class="category-btn <?php echo ($selected_category === $type) ? 'active' : ''; ?>">
                    <i class="fas fa-trophy"></i> <?php echo htmlspecialchars($type); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($selected_team) && $team_data): ?>
            <div class="card">
                <a href="?<?php echo !empty($selected_category) ? 'category=' . urlencode($selected_category) : ''; ?><?php echo !empty($search_query) ? (!empty($selected_category) ? '&' : '') . 'search=' . urlencode($search_query) : ''; ?>" class="back-to-teams">
                    <i class="fas fa-arrow-left"></i> กลับไปหน้ารายชื่อทีม
                </a>
                
                <h2 class="card-title">ข้อมูลทีม: <?php echo htmlspecialchars($team_data['team_name']); ?></h2>
                
                <div class="team-details">
                    <p><i class="fas fa-trophy"></i> ประเภทการแข่งขัน: <?php echo htmlspecialchars($team_data['competition_type']); ?></p>
                    <p><i class="fas fa-user-tie"></i> ผู้ควบคุมทีม: <?php echo htmlspecialchars($team_data['coach_name']); ?></p>
                    <p><i class="fas fa-phone"></i> เบอร์โทรผู้ควบคุม: <?php echo htmlspecialchars($team_data['coach_phone']); ?></p>
                    <p><i class="fas fa-school"></i> สังกัด/โรงเรียน: <?php echo htmlspecialchars($team_data['leader_school']); ?></p>
                    <?php if (!empty($team_data['created_at'])): ?>
                        <p><i class="fas fa-calendar-alt"></i> วันที่ลงทะเบียน: <?php echo date('d/m/Y H:i:s', strtotime($team_data['created_at'])); ?></p>
                    <?php endif; ?>
                </div>
                
                <h3><i class="fas fa-user-friends"></i> สมาชิกทีม (<?php echo count($members); ?> คน)</h3>
                
                <?php if (count($members) > 0): ?>
                    <?php foreach ($members as $index => $member): ?>
                        <div class="member-card">
                            <h4 class="member-name">
                                <i class="fas fa-user-circle"></i> 
                                <?php echo htmlspecialchars($member['member_name']); ?> 
                                <?php if (!empty($member['game_name'])): ?>
                                    <span class="game-name">(<?php echo htmlspecialchars($member['game_name']); ?>)</span>
                                <?php endif; ?>
                                <small>(สมาชิกคนที่ <?php echo $index + 1; ?>)</small>
                            </h4>
                            
                            <div class="member-info">
                                <?php if (!empty($member['position'])): ?>
                                    <p><i class="fas fa-briefcase"></i> ตำแหน่ง: <?php echo htmlspecialchars($member['position']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($member['age'])): ?>
                                    <p><i class="fas fa-birthday-cake"></i> อายุ: <?php echo htmlspecialchars($member['age']); ?> ปี</p>
                                <?php endif; ?>
                                
                                <?php if (!empty($member['birthdate'])): ?>
                                    <p><i class="fas fa-calendar"></i> วันเกิด: <?php echo date('d/m/Y', strtotime($member['birthdate'])); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($member['phone'])): ?>
                                    <?php 
                                    $phone_display = decryptPhone(
                                        $member['phone'], 
                                        $member['phone_key'] ?? '', 
                                        $member['phone_iv'] ?? ''
                                    ); 
                                    ?>
                                    <p><i class="fas fa-phone"></i> เบอร์โทร: <?php echo htmlspecialchars($phone_display); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($member['id_card_image'])): ?>
                                <?php
                                $file_path = $upload_dir . $member['id_card_image'];
                                
                                // ตรวจสอบว่าไฟล์มีอยู่จริง
                                if (file_exists($file_path)) {
                                    // ใช้ข้อมูลการเข้ารหัสจากฐานข้อมูลโดยตรง
                                    $decrypted_image = decryptImage(
                                        $file_path, 
                                        $member['encryption_key'], 
                                        $member['iv'], 
                                        $member['tag']
                                    );
                                } else {
                                    $decrypted_image = false;
                                    error_log("ไม่พบไฟล์: " . $file_path);
                                }
                                ?>
                                
                                <?php if ($decrypted_image !== false): ?>
                                    <div class="image-container">
                                        <h5><i class="fas fa-id-card"></i> เอกสารประจำตัว</h5>
                                        <div class="decrypted-image">
                                            <img src="data:image/jpeg;base64,<?php echo base64_encode($decrypted_image); ?>" 
                                                alt="เอกสารประจำตัว <?php echo htmlspecialchars($member['member_name']); ?>" 
                                                class="id-card-image">
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="error-container">
                                        <p class="error-message">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            ไม่สามารถแสดงเอกสารประจำตัวได้ <?php echo (!file_exists($file_path)) ? "ไม่พบไฟล์" : "โปรดตรวจสอบการเข้ารหัสข้อมูล"; ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="notice-container">
                                    <p class="notice-message">
                                        <i class="fas fa-info-circle"></i> 
                                        ไม่มีข้อมูลเอกสารประจำตัว
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="notice-container">
                        <p class="notice-message">
                            <i class="fas fa-exclamation-circle"></i> 
                            ไม่พบข้อมูลสมาชิกในทีมนี้
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif (!empty($selected_team) && !$team_data): ?>
            <div class="card">
                <a href="?<?php echo !empty($selected_category) ? 'category=' . urlencode($selected_category) : ''; ?><?php echo !empty($search_query) ? (!empty($selected_category) ? '&' : '') . 'search=' . urlencode($search_query) : ''; ?>" class="back-to-teams">
                    <i class="fas fa-arrow-left"></i> กลับไปหน้ารายชื่อทีม
                </a>
                <div class="error-container">
                    <p class="error-message">
                        <i class="fas fa-exclamation-triangle"></i> 
                        ไม่พบข้อมูลทีมที่ระบุ กรุณาเลือกทีมจากรายการ
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($selected_team)): ?>
            <div class="team-list">
                <h2 class="card-title"><i class="fas fa-list"></i> รายชื่อทีม<?php echo !empty($selected_category) ? ' - ประเภท: ' . htmlspecialchars($selected_category) : 'ทั้งหมด'; ?></h2>
                
                <?php if (!empty($teams_data)): ?>
                    <?php if (empty($search_query) && empty($selected_category)): ?>
                        <!-- แสดงทีมแยกตามประเภทการแข่งขัน -->
                        <?php foreach ($teams_by_category as $category => $category_teams): ?>
                            <div class="team-category-section">
                                <h3 class="category-heading">
                                    <i class="fas fa-trophy"></i> <?php echo htmlspecialchars($category); ?>
                                    <span class="team-count">(<?php echo count($category_teams); ?> ทีม)</span>
                                </h3>
                                
                                <div class="team-grid">
                                    <?php foreach ($category_teams as $team): ?>
                                        <div class="team-item">
                                            <div class="team-actions">
                                                <a href="?team=<?php echo $team['team_id']; ?>" class="team-link <?php echo ($selected_team == $team['team_id']) ? 'active' : ''; ?>">
                                                    <i class="fas fa-users"></i> 
                                                    <?php echo htmlspecialchars($team['team_name']); ?>
                                                </a>
                                                <button class="delete-team-btn" data-team-id="<?php echo $team['team_id']; ?>" data-team-name="<?php echo htmlspecialchars($team['team_name']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- แสดงผลลัพธ์การค้นหาหรือการกรองในรูปแบบปกติ -->
                        <div class="team-grid">
                            <?php foreach ($teams_data as $team): ?>
                                <div class="team-item">
                                    <div class="team-actions">
                                        <a href="?team=<?php echo $team['team_id']; ?><?php echo !empty($selected_category) ? '&category=' . urlencode($selected_category) : ''; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="team-link <?php echo ($selected_team == $team['team_id']) ? 'active' : ''; ?>">
                                            <i class="fas fa-users"></i> 
                                            <?php echo htmlspecialchars($team['team_name']); ?>
                                            <small>(<?php echo htmlspecialchars($team['competition_type']); ?>)</small>
                                        </a>
                                        <button class="delete-team-btn" data-team-id="<?php echo $team['team_id']; ?>" data-team-name="<?php echo htmlspecialchars($team['team_name']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="no-teams">
                        <i class="fas fa-info-circle"></i> 
                        ไม่พบข้อมูลทีม
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div> 

      <!-- หน้าต่างยืนยันการลบทีม -->
      <div id="deleteConfirmDialog" class="confirm-dialog">
        <div class="confirm-content">
            <h3><i class="fas fa-exclamation-triangle"></i> ยืนยันการลบทีม</h3>
            <p>คุณต้องการลบทีม <span id="teamNameToDelete"></span> ใช่หรือไม่?</p>
            <p><strong>คำเตือน:</strong> การดำเนินการนี้ไม่สามารถยกเลิกได้ และจะลบข้อมูลสมาชิกทีมทั้งหมด</p>
            <div class="confirm-actions">
                <button id="cancelDelete" class="confirm-cancel"><i class="fas fa-times"></i> ยกเลิก</button>
                <button id="confirmDelete" class="confirm-delete"><i class="fas fa-trash"></i> ยืนยันการลบ</button>
            </div>
        </div>
    </div>

  

</body>
  <footer class="footer">
        <p>
            <i class="fas fa-shield-alt"></i> 
            ระบบจัดการข้อมูลทีม - เวอร์ชัน 1.9.2<br> 
            <br>
            <small>© <?php echo date('Y'); ?> สงวนลิขสิทธิ์</small>
        </p>
    </footer>
<script>
   
    document.addEventListener('DOMContentLoaded', function() {
    // เพิ่มการซูมรูปภาพเมื่อคลิก
    const idCardImages = document.querySelectorAll('.id-card-image');
    idCardImages.forEach(function(img) {
        img.addEventListener('click', function() {
            this.classList.toggle('zoomed');
        });
    });
    
            // จัดการปุ่มลบทีม
            const deleteButtons = document.querySelectorAll('.delete-team-btn');
            const deleteDialog = document.getElementById('deleteConfirmDialog');
            const teamNameSpan = document.getElementById('teamNameToDelete');
            const cancelButton = document.getElementById('cancelDelete');
            const confirmButton = document.getElementById('confirmDelete');
            
            let teamIdToDelete = null;
            
            // เมื่อคลิกปุ่มลบ
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    teamIdToDelete = this.getAttribute('data-team-id');
                    const teamName = this.getAttribute('data-team-name');
                    teamNameSpan.textContent = '"' + teamName + '"';  // เพิ่มเครื่องหมายคำพูดรอบชื่อทีม
                    deleteDialog.style.display = 'block';
                });
            });
            
            // ปุ่มยกเลิกการลบ
            cancelButton.addEventListener('click', function() {
                deleteDialog.style.display = 'none';
            });
            
            // ปุ่มยืนยันการลบ
            confirmButton.addEventListener('click', function() {
                if (teamIdToDelete) {
                    window.location.href = 'delete_team.php?team_id=' + teamIdToDelete;
                }
            });
            
            // ปิดหน้าต่างยืนยันเมื่อคลิกนอกหน้าต่าง
            window.addEventListener('click', function(e) {
                if (e.target == deleteDialog) {
                    deleteDialog.style.display = 'none';
                }
            });
        });
    </script>
</html>

<?php
// ปิดการเชื่อมต่อฐานข้อมูล
$conn = null;
?>