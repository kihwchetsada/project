<?php
// ตั้งค่า timezone เป็นประเทศไทย
date_default_timezone_set('Asia/Bangkok');

session_start(); 

// เชื่อมต่อฐานข้อมูล
include 'db_connect.php';

$upload_dir = __DIR__ . '/uploads/';
// $keys_dir = __DIR__ . '/keys/';

// ฟังก์ชันถอดรหัสรูปภาพ
function decryptImage($encrypted_file, $encryption_key, $iv, $tag) {
    if (!file_exists($encrypted_file)) {
        return false;
    }

    // แปลง key, iv, tag กลับจาก base64
    $encryption_key = base64_decode($encryption_key);
    $iv = base64_decode($iv);
    $tag = base64_decode($tag);

    if (!$encryption_key || !$iv || !$tag) {
        return false;
    }
 
    // โหลดข้อมูลไฟล์ที่เข้ารหัส
    $encrypted_data = file_get_contents($encrypted_file);

    // ถอดรหัสข้อมูล
    $decrypted_data = openssl_decrypt(
        $encrypted_data,
        'aes-256-gcm',
        $encryption_key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    return $decrypted_data;
}

// สร้างตัวแปรเก็บข้อความ Log
$log_message = "";
$selected_team = isset($_GET['team']) ? intval($_GET['team']) : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // ดึงข้อมูลทีมทั้งหมด
    $teams_data = [];
    $filtered_teams = [];
    
    if (!empty($search_query)) {
        // ค้นหาตามชื่อทีม
        $stmt = $conn->prepare("SELECT * FROM teams WHERE team_name LIKE :search_query ORDER BY team_name ASC");
        $stmt->bindValue(':search_query', '%' . $search_query . '%', PDO::PARAM_STR);
    } else {
        // ดึงทีมทั้งหมด
        $stmt = $conn->prepare("SELECT * FROM teams ORDER BY team_name ASC");
    }
    
    $stmt->execute();
    $teams_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $filtered_teams = $teams_data;
    
    $log_message .= "📂 โหลดข้อมูลทีมทั้งหมด " . count($teams_data) . " ทีม<br>";
    
} catch (PDOException $e) {
    $log_message = "⚠️ ข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลทีมและรูปภาพ</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/view_img.css">
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
            <h2 class="card-title"><i class="fas fa-search"></i> ค้นหาทีม</h2>
            <form action="" method="GET" class="search-container">
                <input type="text" name="search" class="search-input" placeholder="พิมพ์ชื่อทีมที่ต้องการค้นหา..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-button"><i class="fas fa-search"></i> ค้นหา</button>
                <?php if (!empty($search_query)): ?>
                <a href="?" class="reset-button"><i class="fas fa-times"></i> ล้าง</a>
                <?php endif; ?>
            </form>

            <?php if (!empty($search_query)): ?>
            <div class="search-results">
                <i class="fas fa-info-circle"></i> ผลการค้นหา "<?php echo htmlspecialchars($search_query); ?>": พบ <?php echo count($filtered_teams); ?> ทีม
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($selected_team)): ?>
            <?php
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
                }
            } catch (PDOException $e) {
                $log_message .= "⚠️ ข้อผิดพลาดในการดึงข้อมูลทีม: " . $e->getMessage() . "<br>";
            }
            ?>

            <?php if ($team_data): ?>
            <div class="card">
                <a href="?" class="back-to-teams"><i class="fas fa-arrow-left"></i> กลับไปหน้ารายชื่อทีมทั้งหมด</a>
                
                <h2 class="card-title">ข้อมูลทีม: <?php echo htmlspecialchars($team_data['team_name']); ?></h2>
                
                <h3><i class="fas fa-user-friends"></i> สมาชิกทีม (<?php echo count($members); ?> คน)</h3>
                
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
                            
                            <?php if (!empty($member['phone'])): ?>
                                <p><i class="fas fa-phone"></i> เบอร์โทร: <?php echo htmlspecialchars($member['phone']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($member['id_card_image'])): ?>
                            <?php
                            $file_path = $upload_dir . $member['id_card_image'];
                            
                            // ใช้ข้อมูลการเข้ารหัสจากฐานข้อมูลโดยตรง
                            $decrypted_image = decryptImage(
                                $file_path, 
                                $member['encryption_key'], 
                                $member['iv'], 
                                $member['tag']
                            );
                            ?>
                            
                            <?php if ($decrypted_image): ?>
                                <div class="image-container">
                                    <h5><i class="fas fa-id-card"></i> เอกสารประจำตัว</h5>
                                    <img class="id-card-image" src="data:image/jpeg;base64,<?php echo base64_encode($decrypted_image); ?>" alt="รูปบัตรประจำตัวของ <?php echo htmlspecialchars($member['member_name']); ?>">
                                </div>
                            <?php else: ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-triangle"></i> ไม่สามารถถอดรหัสภาพได้
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="error-message">
                                <i class="fas fa-image"></i> ไม่มีไฟล์รูปภาพสำหรับสมาชิกคนนี้
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="card">
                    <div class="empty-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <p class="error-message">ไม่พบข้อมูลทีมที่เลือก</p>
                        <a href="?" class="back-to-teams"><i class="fas fa-arrow-left"></i> กลับไปหน้ารายชื่อทีมทั้งหมด</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card">
                <h2 class="card-title"><i class="fas fa-list"></i> รายชื่อทีมทั้งหมด (<?php echo count($filtered_teams); ?> ทีม)</h2>
                
                <?php if (!empty($filtered_teams)): ?>
                    <div class="team-list">
                        <?php foreach($filtered_teams as $team): ?>
                            <?php 
                            try {
                                // นับจำนวนสมาชิกในทีม
                                $stmt = $conn->prepare("SELECT COUNT(*) as member_count FROM team_members WHERE team_id = :team_id");
                                $stmt->bindParam(':team_id', $team['team_id'], PDO::PARAM_INT);
                                $stmt->execute();
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $member_count = $result['member_count'];
                            } catch (PDOException $e) {
                                $member_count = 0;
                                $log_message .= "⚠️ ข้อผิดพลาดในการนับสมาชิก: " . $e->getMessage() . "<br>";
                            }
                            ?>
                            <a href="?team=<?php echo $team['team_id']; ?>" class="team-card">
                                <div class="team-name">
                                    <i class="fas fa-flag"></i> <?php echo htmlspecialchars($team['team_name']); ?>
                                </div>
                                <div class="member-count">
                                    <i class="fas fa-user-friends"></i> จำนวนสมาชิก: <?php echo $member_count; ?> คน
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p class="error-message">ไม่พบข้อมูลทีมที่ตรงกับการค้นหา</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>© <?php echo date('Y'); ?> ระบบดูข้อมูลทีมและรูปภาพ | ปรับปรุงล่าสุดเมื่อ: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>