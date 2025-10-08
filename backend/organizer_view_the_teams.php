<?php

// ตั้งค่า timezone เป็นประเทศไทย
date_default_timezone_set('Asia/Bangkok');

// เปิดใช้งาน error reporting สำหรับการดีบัก (แนะนำให้ปิดใน production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 🔒 ตรวจสอบการ logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require '../db_connect.php';

// --- ส่วนจัดการการอนุมัติทีม ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_team_id'])) {
    $teamIdToApprove = intval($_POST['approve_team_id']);
    // ใช้ Null Coalescing Operator เพื่อความปลอดภัย
    $organizerName = $_SESSION['userData']['username'] ?? 'Organizer';

    if ($teamIdToApprove > 0) {
        try {
            $statusText = "approved_by:" . $organizerName;
            $stmt = $conn->prepare("UPDATE teams SET status = :status WHERE team_id = :team_id");
            $stmt->bindParam(':status', $statusText, PDO::PARAM_STR);
            $stmt->bindParam(':team_id', $teamIdToApprove, PDO::PARAM_INT);
            $stmt->execute();
            // ลบ query string เพื่อไม่ให้ฟอร์มถูกส่งซ้ำเมื่อรีเฟรช
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        } catch (PDOException $e) {
            // ควรแสดงข้อความที่เป็นมิตรกับผู้ใช้
            $log_message = "เกิดข้อผิดพลาดในการอนุมัติทีม: " . $e->getMessage();
        }
    }
}


// --- ส่วนการดึงข้อมูล ---
$log_message = "";
$selected_team_id = isset($_GET['team']) ? intval($_GET['team']) : null;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';

// ประกาศตัวแปรสำหรับ view
$structured_teams = [];
$teams_by_category = [];
$team_data = null;
$members = [];
$competition_types = [];
$total_teams_found = 0;

try {
    // 1. ดึงประเภทการแข่งขัน (จากชื่อทัวร์นาเมนต์)
    $stmt_types = $conn->prepare("
        SELECT DISTINCT tour.tournament_name 
        FROM tournaments tour 
        JOIN teams t ON tour.id = t.tournament_id 
        WHERE tour.tournament_name IS NOT NULL AND tour.tournament_name != '' 
        ORDER BY tour.tournament_name ASC
    ");
    $stmt_types->execute();
    $competition_types = $stmt_types->fetchAll(PDO::FETCH_COLUMN);

    // 2. สร้าง SQL Query หลัก
    // ⬇️ FIX: แก้ไข LEFT JOIN users โดยใช้ t.user_id = u.id (สมมติฐาน)
    $sql = "
        SELECT
            t.team_id, t.team_name, t.coach_name, t.coach_phone, t.leader_school,
            t.status AS team_status, t.created_at,
            tour.tournament_name AS competition_type,
            u.username AS registered_by_username,
            tm.member_id, tm.member_name, tm.game_name, tm.age, tm.birthdate, tm.phone AS member_phone, tm.position
        FROM teams AS t
        LEFT JOIN tournaments AS tour ON t.tournament_id = tour.id
        LEFT JOIN users AS u ON t.user_id = u.id -- <--- ❗️ จุดที่แก้ไข: สมมติว่า teams มี user_id และ users มี id
        LEFT JOIN team_members AS tm ON t.team_id = tm.team_id
    ";

    $conditions = [];
    $params = [];

    if (!empty($search_query)) {
        $conditions[] = "t.team_name LIKE :search_query";
        $params[':search_query'] = '%' . $search_query . '%';
    }
    if (!empty($selected_category)) {
        $conditions[] = "tour.tournament_name = :category";
        $params[':category'] = $selected_category;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY tour.tournament_name ASC, t.team_name ASC, tm.member_id ASC";

    $stmt_main = $conn->prepare($sql);
    $stmt_main->execute($params);
    $all_results = $stmt_main->fetchAll(PDO::FETCH_ASSOC);

    // 3. จัดโครงสร้างข้อมูล
    foreach ($all_results as $row) {
        $teamId = $row['team_id'];
        if (!isset($structured_teams[$teamId])) {
            $structured_teams[$teamId] = [
                'team_info' => [
                    'team_id' => $row['team_id'],
                    'team_name' => $row['team_name'],
                    'coach_name' => $row['coach_name'],
                    'coach_phone' => $row['coach_phone'],
                    'leader_school' => $row['leader_school'],
                    'status' => $row['team_status'],
                    'created_at' => $row['created_at'],
                    'competition_type' => $row['competition_type']
                ],
                'members' => []
            ];
        }

        if ($row['member_id'] !== null) {
            $structured_teams[$teamId]['members'][] = [
                'member_id' => $row['member_id'],
                'member_name' => $row['member_name'],
                'game_name' => $row['game_name'],
                'age' => $row['age'],
                'birthdate' => $row['birthdate'],
                'phone' => $row['member_phone'],
                'position' => $row['position']
            ];
        }
    }
    
    $total_teams_found = count($structured_teams);

    // 4. เตรียมข้อมูลสำหรับ View
    if ($selected_team_id && isset($structured_teams[$selected_team_id])) {
        $team_data = $structured_teams[$selected_team_id]['team_info'];
        $members = $structured_teams[$selected_team_id]['members'];
    } else {
        foreach ($structured_teams as $team) {
            $category = $team['team_info']['competition_type'] ?? 'ไม่ระบุประเภท';
            if (!isset($teams_by_category[$category])) {
                $teams_by_category[$category] = [];
            }
            $teams_by_category[$category][] = $team['team_info'];
        }
    }

} catch (PDOException $e) {
    // แสดงข้อผิดพลาดที่หน้าจอ (สำหรับ debug) และบันทึก log
    $log_message = "⚠️ ข้อผิดพลาดฐานข้อมูล: " . $e->getMessage();
    error_log("Database Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo !empty($team_data) ? htmlspecialchars($team_data['team_name']) . ' - ' : ''; ?>ข้อมูลทีม</title>
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/view_the_teams.css">
</head>
<body>
    <a href="organizer_dashboard.php" class="back-button">กลับไปหน้าแดชบอร์ด</a>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> ระบบข้อมูลทีม</h1>
            <p>ระบบแสดงข้อมูลทีมและเอกสารประจำตัวของสมาชิก</p>
        </div>

        <?php if (!empty($log_message)): ?>
        <div class="log-container error"> <code><?php echo $log_message; ?></code>
        </div>
        <?php endif; ?>

        

        <?php if ($selected_team_id && $team_data): ?>
            <a href="?" class="back-to-teams-btn"><i class="fas fa-chevron-left"></i> กลับไปหน้ารายชื่อทีม</a>
        <?php endif; ?>
        
        <?php if (empty($selected_team_id)): ?>
            <div class="card filter-card">
                <div class="filter-controls">
                    <form action="" method="GET" class="search-form">
                        <div class="search-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" class="search-input" placeholder="ค้นหาชื่อทีม..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <?php if (!empty($selected_category)): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
                        <?php endif; ?>
                        <button type="submit" class="search-button">ค้นหา</button>
                         <?php if (!empty($search_query)): ?>
                            <a href="?category=<?php echo urlencode($selected_category);?>" class="reset-button" title="ล้างการค้นหา">&times;</a>
                        <?php endif; ?>
                    </form>
                    
                    <div class="category-filter">
                        <a href="?" class="category-btn <?php echo empty($selected_category) ? 'active' : ''; ?>">
                            <i class="fas fa-globe"></i> ทั้งหมด
                        </a>
                        <?php foreach ($competition_types as $type): ?>
                        <a href="?category=<?php echo urlencode($type); ?>" 
                           class="category-btn <?php echo ($selected_category === $type) ? 'active' : ''; ?>">
                            <i class="fas fa-trophy"></i> <?php echo htmlspecialchars($type); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (!empty($search_query) || !empty($selected_category)): ?>
                <div class="search-results-info">
                    <i class="fas fa-info-circle"></i> 
                    ผลการกรอง: พบทั้งหมด <?php echo $total_teams_found; ?> ทีม
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>


        <?php if ($selected_team_id && $team_data): ?>
            <div class="card team-detail-card">
                <div class="team-detail-header">
                    <h2><?php echo htmlspecialchars($team_data['team_name']); ?></h2>
                    <span class="team-category-badge"><?php echo htmlspecialchars($team_data['competition_type']); ?></span>
                </div>
                
                <div class="team-details-grid">
                    <p><i class="fas fa-user-tie"></i> <strong>ผู้ควบคุมทีม:</strong> <?php echo htmlspecialchars($team_data['coach_name']); ?></p>
                    <p><i class="fas fa-phone"></i> <strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($team_data['coach_phone']); ?></p>
                    <p><i class="fas fa-school"></i> <strong>สังกัด:</strong> <?php echo htmlspecialchars($team_data['leader_school']); ?></p>
                    <p><i class="fas fa-calendar-alt"></i> <strong>วันที่ลงทะเบียน:</strong> <?php echo date('d/m/Y H:i', strtotime($team_data['created_at'])); ?></p>
                </div>
                
                <h3><i class="fas fa-user-friends"></i> สมาชิกในทีม (<?php echo count($members); ?> คน)</h3>
                <div class="members-grid">
                    <?php if (count($members) > 0): ?>
                        <?php foreach ($members as $member): ?>
                        <div class="member-card">
                            <h4 class="member-name">
                                <i class="fas fa-user-circle"></i> 
                                <?php echo htmlspecialchars($member['member_name']); ?>
                                <span class="game-name">(<?php echo htmlspecialchars($member['game_name']); ?>)</span>
                            </h4>
                            <div class="member-info">
                                <p><i class="fas fa-briefcase"></i> <strong>ตำแหน่ง:</strong> <?php echo htmlspecialchars($member['position'] ?? '-'); ?></p>
                                <p><i class="fas fa-birthday-cake"></i> <strong>อายุ:</strong> <?php echo htmlspecialchars($member['age'] ?? '-'); ?> ปี</p>
                                <p><i class="fas fa-calendar"></i> <strong>วันเกิด:</strong> <?php echo ($member['birthdate'] && $member['birthdate'] != '0000-00-00') ? date('d/m/Y', strtotime($member['birthdate'])) : '-'; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="notice-message"><i class="fas fa-exclamation-circle"></i> ไม่พบข้อมูลสมาชิกในทีมนี้</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($selected_team_id && !$team_data): ?>
            <div class="card"><p class="error-message"><i class="fas fa-exclamation-triangle"></i> ไม่พบข้อมูลทีมที่ระบุ</p></div>
        <?php else: ?>
            <div class="team-list-container">
                <?php if ($total_teams_found > 0): ?>
                    <?php if (empty($search_query) && empty($selected_category)): ?>
                         <?php foreach ($teams_by_category as $category => $category_teams): ?>
                            <div class="team-category-section">
                                <h3 class="category-heading"><i class="fas fa-trophy"></i> <?php echo htmlspecialchars($category); ?> <span class="team-count">(<?php echo count($category_teams); ?> ทีม)</span></h3>
                                <div class="team-grid">
                                <?php foreach ($category_teams as $team): ?>
                                    <div class="team-item">
                                        <a href="?team=<?php echo $team['team_id']; ?>" class="team-link">
                                            <i class="fas fa-users"></i>
                                            <span class="team-name"><?php echo htmlspecialchars($team['team_name']); ?></span>
                                        </a>
                                        <div class="team-actions">
                                            <?php if (strpos($team['status'], 'approved_by:') === false): ?>
                                                <form method="post" action=""><input type="hidden" name="approve_team_id" value="<?php echo $team['team_id']; ?>"><button type="submit" class="approve-btn"><i class="fas fa-check"></i> อนุมัติ</button></form>
                                            <?php else: ?>
                                                <span class="approved-by"><i class="fas fa-check-circle"></i> อนุมัติโดย <?php echo htmlspecialchars(str_replace('approved_by:', '', $team['status'])); ?></span>
                                            <?php endif; ?>
                                            <button class="delete-team-btn" data-team-id="<?php echo $team['team_id']; ?>" data-team-name="<?php echo htmlspecialchars($team['team_name']); ?>"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            </div>
                         <?php endforeach; ?>
                    <?php else: ?>
                        <div class="team-grid search-results-grid">
                        <?php foreach ($structured_teams as $team_details): $team = $team_details['team_info']; ?>
                            <div class="team-item">
                                <a href="?team=<?php echo $team['team_id']; ?>&<?php echo http_build_query(['category' => $selected_category, 'search' => $search_query]); ?>" class="team-link">
                                    <i class="fas fa-users"></i>
                                    <span class="team-name"><?php echo htmlspecialchars($team['team_name']); ?></span>
                                    <small class="team-category-badge-small"><?php echo htmlspecialchars($team['competition_type']); ?></small>
                                </a>
                                <div class="team-actions">
                                     <?php if (strpos($team['status'], 'approved_by:') === false): ?>
                                        <form method="post" action=""><input type="hidden" name="approve_team_id" value="<?php echo $team['team_id']; ?>"><button type="submit" class="approve-btn"><i class="fas fa-check"></i> อนุมัติ</button></form>
                                    <?php else: ?>
                                        <span class="approved-by"><i class="fas fa-check-circle"></i> อนุมัติโดย <?php echo htmlspecialchars(str_replace('approved_by:', '', $team['status'])); ?></span>
                                    <?php endif; ?>
                                    <button class="delete-team-btn" data-team-id="<?php echo $team['team_id']; ?>" data-team-name="<?php echo htmlspecialchars($team['team_name']); ?>"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="card no-teams-found">
                        <i class="fas fa-ghost"></i>
                        <h2>ไม่พบข้อมูลทีม</h2>
                        <p>ไม่พบทีมที่ตรงกับการค้นหาหรือการกรองของคุณ</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div> 

    <div id="deleteConfirmDialog" class="confirm-dialog">
        <div class="confirm-content">
            <h3><i class="fas fa-exclamation-triangle"></i> ยืนยันการลบทีม</h3>
            <p>คุณต้องการลบทีม <strong id="teamNameToDelete"></strong> ใช่หรือไม่?</p>
            <p class="warning">การดำเนินการนี้ไม่สามารถยกเลิกได้ และจะลบข้อมูลสมาชิกทั้งหมดที่เกี่ยวข้องกับทีมนี้</p>
            <div class="confirm-actions">
                <button id="cancelDelete" class="confirm-cancel"><i class="fas fa-times"></i> ยกเลิก</button>
                <button id="confirmDelete" class="confirm-delete"><i class="fas fa-trash"></i> ยืนยันการลบ</button>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p><i class="fas fa-shield-alt"></i> ระบบจัดการข้อมูลทีม - เวอร์ชัน 2.2.0 (ปรับปรุง)</p>
        <small>© <?php echo date('Y'); ?> All rights reserved.</small>
    </footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-team-btn');
    const deleteDialog = document.getElementById('deleteConfirmDialog');
    if (!deleteDialog) return;

    const teamNameSpan = document.getElementById('teamNameToDelete');
    const cancelButton = document.getElementById('cancelDelete');
    const confirmButton = document.getElementById('confirmDelete');
    let teamIdToDelete = null;

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            teamIdToDelete = this.getAttribute('data-team-id');
            const teamName = this.getAttribute('data-team-name');
            teamNameSpan.textContent = '"' + teamName + '"';
            deleteDialog.style.display = 'flex';
        });
    });

    const closeDialog = () => {
        deleteDialog.style.display = 'none';
    };

    cancelButton.addEventListener('click', closeDialog);
    confirmButton.addEventListener('click', function() {
        if (teamIdToDelete) {
            // ส่งไปที่ไฟล์สำหรับลบข้อมูล
            window.location.href = 'delete_team.php?team_id=' + teamIdToDelete;
        }
    });

    // ปิด dialog เมื่อคลิกนอกพื้นที่ content
    window.addEventListener('click', function(e) {
        if (e.target == deleteDialog) {
            closeDialog();
        }
    });
});
</script>
</body>
</html>
<?php
// ปิดการเชื่อมต่อฐานข้อมูล
$conn = null;
?>