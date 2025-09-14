<?php

// ตั้งค่า timezone เป็นประเทศไทย
date_default_timezone_set('Asia/Bangkok');

// เปิดใช้งาน error reporting สำหรับการดีบัก
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
    $organizerName = $_SESSION['userData']['username'] ?? 'unknown';

    if ($teamIdToApprove > 0) {
        try {
            $statusText = "approved_by:" . $organizerName;
            $stmt = $conn->prepare("UPDATE teams SET status = :status WHERE team_id = :team_id");
            $stmt->bindParam(':status', $statusText, PDO::PARAM_STR);
            $stmt->bindParam(':team_id', $teamIdToApprove, PDO::PARAM_INT);
            $stmt->execute();
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } catch (PDOException $e) {
            echo "Error updating team status: " . $e->getMessage();
        }
    }
}

// --- ส่วนการดึงข้อมูลที่ปรับปรุงใหม่ทั้งหมด ---

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
    // 1. ดึงประเภทการแข่งขัน (จากชื่อทัวร์นาเมนต์) ที่มีทีมเข้าร่วม
    $stmt_types = $conn->prepare("
        SELECT DISTINCT tour.tournament_name 
        FROM tournaments tour 
        JOIN teams t ON tour.id = t.tournament_id 
        WHERE tour.tournament_name IS NOT NULL AND tour.tournament_name != '' 
        ORDER BY tour.tournament_name ASC
    ");
    $stmt_types->execute();
    $competition_types = $stmt_types->fetchAll(PDO::FETCH_COLUMN);

    // 2. สร้าง SQL Query หลักพร้อม JOIN และใช้ tournament_name เป็น competition_type
    $sql = "
        SELECT
            t.team_id, t.team_name, t.coach_name, t.coach_phone, t.leader_school,
            t.status AS team_status, t.created_at,
            tour.tournament_name AS competition_type, -- ใช้ชื่อทัวร์นาเมนต์แทน
            u.username AS registered_by_username,
            tm.member_id, tm.member_name, tm.game_name, tm.age, tm.birthdate, tm.phone AS member_phone, tm.position
        FROM teams AS t
        LEFT JOIN tournaments AS tour ON t.tournament_id = tour.id
        LEFT JOIN users AS u ON t.team_id = u.team_id
        LEFT JOIN team_members AS tm ON t.team_id = tm.team_id
    ";

    $conditions = [];
    $params = [];

    if (!empty($search_query)) {
        $conditions[] = "t.team_name LIKE :search_query";
        $params[':search_query'] = '%' . $search_query . '%';
    }
    if (!empty($selected_category)) {
        $conditions[] = "tour.tournament_name = :category"; // แก้ไขเงื่อนไข
        $params[':category'] = $selected_category;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY tour.tournament_name ASC, t.team_name ASC, tm.member_id ASC"; // แก้ไขการเรียงลำดับ

    $stmt_main = $conn->prepare($sql);
    $stmt_main->execute($params);
    $all_results = $stmt_main->fetchAll(PDO::FETCH_ASSOC);

    // 3. จัดโครงสร้างข้อมูลจากผลลัพธ์ Query
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
    $log_message .= "📂 โหลดข้อมูลทีมทั้งหมด " . $total_teams_found . " ทีม<br>";

    // 4. เตรียมข้อมูลสำหรับ View (HTML)
    if ($selected_team_id && isset($structured_teams[$selected_team_id])) {
        $team_data = $structured_teams[$selected_team_id]['team_info'];
        $members = $structured_teams[$selected_team_id]['members'];
        $log_message .= "🧑‍🤝‍🧑 แสดงข้อมูลทีม " . htmlspecialchars($team_data['team_name']) . " (สมาชิก " . count($members) . " คน)<br>";
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
    $log_message = "⚠️ ข้อผิดพลาด: " . $e->getMessage();
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
            <a href="organizer_dashboard.php" class="card-title"><i class="fas fa-sign-out-alt"></i> กลับไปหน้าหลัก</a>
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
                <i class="fas fa-info-circle"></i> ผลการค้นหา "<?php echo htmlspecialchars($search_query); ?>": พบ <?php echo $total_teams_found; ?> ทีม
                <?php if (!empty($selected_category)): ?>
                (ในประเภท: <?php echo htmlspecialchars($selected_category); ?>)
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
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

        <?php if ($selected_team_id && $team_data): ?>
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
                                <?php if (!empty($member['birthdate']) && $member['birthdate'] != '0000-00-00'): ?>
                                    <p><i class="fas fa-calendar"></i> วันเกิด: <?php echo date('d/m/Y', strtotime($member['birthdate'])); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($member['phone'])): ?>
                                    <p><i class="fas fa-phone"></i> เบอร์โทร: <?php echo htmlspecialchars($member['phone']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="notice-container"><p class="notice-message"><i class="fas fa-exclamation-circle"></i> ไม่พบข้อมูลสมาชิกในทีมนี้</p></div>
                <?php endif; ?>
            </div>
        <?php elseif ($selected_team_id && !$team_data): ?>
            <div class="card"><p class="error-message"><i class="fas fa-exclamation-triangle"></i> ไม่พบข้อมูลทีมที่ระบุ</p></div>
        <?php endif; ?>

        <?php if (empty($selected_team_id)): ?>
            <div class="team-list">
                <h2 class="card-title"><i class="fas fa-list"></i> รายชื่อทีม<?php echo !empty($selected_category) ? ' - ประเภท: ' . htmlspecialchars($selected_category) : 'ทั้งหมด'; ?></h2>
                
                <?php if ($total_teams_found > 0): ?>
                    <?php if (empty($search_query) && empty($selected_category)): ?>
                        <?php foreach ($teams_by_category as $category => $category_teams): ?>
                            <div class="team-category-section">
                                <h3 class="category-heading"><i class="fas fa-trophy"></i> <?php echo htmlspecialchars($category); ?> <span class="team-count">(<?php echo count($category_teams); ?> ทีม)</span></h3>
                                <div class="team-grid">
                                <?php foreach ($category_teams as $team): ?>
                                    <div class="team-item">
                                        <a href="?team=<?php echo $team['team_id']; ?>" class="team-link"><i class="fas fa-users"></i> <?php echo htmlspecialchars($team['team_name']); ?></a>
                                        <div class="team-actions">
                                        <?php if (strpos($team['status'], 'approved_by:') === false): ?>
                                            <form method="post" action=""><input type="hidden" name="approve_team_id" value="<?php echo $team['team_id']; ?>"><button type="submit" class="approve-btn"><i class="fas fa-check"></i> อนุมัติ</button></form>
                                        <?php else: ?>
                                            <span class="approved-by">✅ อนุมัติโดย <?php echo htmlspecialchars(str_replace('approved_by:', '', $team['status'])); ?></span>
                                        <?php endif; ?>
                                        <button class="delete-team-btn" data-team-id="<?php echo $team['team_id']; ?>" data-team-name="<?php echo htmlspecialchars($team['team_name']); ?>"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="team-grid-search">
                        <?php foreach ($structured_teams as $team_id => $team_details): $team = $team_details['team_info']; ?>
                             <div class="team-item">
                                <a href="?team=<?php echo $team['team_id']; ?>&<?php echo http_build_query(['category' => $selected_category, 'search' => $search_query]); ?>" class="team-link"><i class="fas fa-users"></i> <?php echo htmlspecialchars($team['team_name']); ?> <small>(<?php echo htmlspecialchars($team['competition_type']); ?>)</small></a>
                                <div class="team-actions">
                                  <?php if (strpos($team['status'], 'approved_by:') === false): ?>
                                    <form method="post" action=""><input type="hidden" name="approve_team_id" value="<?php echo $team['team_id']; ?>"><button type="submit" class="approve-btn"><i class="fas fa-check"></i> อนุมัติ</button></form>
                                  <?php else: ?>
                                    <span class="approved-by">✅ อนุมัติโดย <?php echo htmlspecialchars(str_replace('approved_by:', '', $team['status'])); ?></span>
                                  <?php endif; ?>
                                <button class="delete-team-btn" data-team-id="<?php echo $team['team_id']; ?>" data-team-name="<?php echo htmlspecialchars($team['team_name']); ?>"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="no-teams"><i class="fas fa-info-circle"></i> ไม่พบข้อมูลทีม</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div> 

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
    <p><i class="fas fa-shield-alt"></i> ระบบจัดการข้อมูลทีม - เวอร์ชัน 2.1.0<br><br><small>© <?php echo date('Y'); ?> สงวนลิขสิทธิ์</small></p>
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
            window.location.href = 'delete_team.php?team_id=' + teamIdToDelete;
        }
    });

    window.addEventListener('click', function(e) {
        if (e.target == deleteDialog) {
            closeDialog();
        }
    });
});
</script>
</html>

<?php
// ปิดการเชื่อมต่อฐานข้อมูล
$conn = null;
?>