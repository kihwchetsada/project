<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '../db_connect.php'; 

// --- 🔒 การจัดการ Session และการตรวจสอบสิทธิ์ ---
if (isset($_GET['logout'])) {
    if (isset($_SESSION['conn']['id'])) {
        $userId = $_SESSION['conn']['id'];
        $stmt = $conn->prepare("UPDATE users SET last_activity = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    }
    session_destroy();
    header('Location: ../login.php');
    exit;
}
if (!isset($_SESSION['conn']) || $_SESSION['conn']['role'] !== 'participant') {
    header('Location: ../login.php');
    exit;
}

// --- ⚙️ ส่วนจัดการข้อมูล (Backend Logic) ---
$user_id = $_SESSION['conn']['id'];
$update_success = null;
$update_error = '';

// ส่วนจัดการการอัปเดตข้อมูลทีม (เมื่อมีการส่งฟอร์มแก้ไข)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_team'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $update_error = 'CSRF token ไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
    } else {
        $team_id = $_POST['team_id'];
        $team_name = trim($_POST['team_name'] ?? '');
        $coach_name = trim($_POST['coach_name'] ?? '');
        $coach_phone = trim($_POST['coach_phone'] ?? '');
        $leader_school = trim($_POST['leader_school'] ?? '');
        if (empty($team_id) || empty($team_name) || empty($coach_name) || empty($coach_phone)) {
            $update_error = 'กรุณากรอกข้อมูลทีมให้ครบถ้วน (ชื่อทีม, ผู้ควบคุม, เบอร์โทร)';
        } else {
             try {
                $conn->beginTransaction();
                // ✅ FIX: แก้ไข WHERE id เป็น WHERE team_id
                $stmt = $conn->prepare("UPDATE teams SET team_name = :team_name, coach_name = :coach_name, coach_phone = :coach_phone, leader_school = :leader_school, updated_at = NOW(), is_approved = 0 WHERE team_id = :team_id AND user_id = :user_id");
                $stmt->execute([':team_name' => $team_name, ':coach_name' => $coach_name, ':coach_phone' => $coach_phone, ':leader_school' => $leader_school, ':team_id' => $team_id, ':user_id' => $user_id]);
                
                for ($i = 1; $i <= 8; $i++) {
                    $member_id = $_POST["member_id_$i"] ?? null;
                    $member_name = trim($_POST["member_name_$i"] ?? '');
                    if (!empty($member_name)) {
                        $member_data = [trim($_POST["member_game_name_$i"] ?? ''), $_POST["member_age_$i"] ?? null, trim($_POST["member_phone_$i"] ?? ''), trim($_POST["member_position_$i"] ?? ''), $_POST["member_birthdate_$i"] ?? null];
                        if (!empty($member_id)) {
                            $stmt_member = $conn->prepare("UPDATE team_members SET member_name = ?, game_name = ?, age = ?, phone = ?, position = ?, birthdate = ? WHERE member_id = ? AND team_id = ?");
                            $stmt_member->execute([$member_name, ...$member_data, $member_id, $team_id]);
                        } else {
                             $stmt_member = $conn->prepare("INSERT INTO team_members (team_id, member_name, game_name, age, phone, position, birthdate) VALUES (?, ?, ?, ?, ?, ?, ?)");
                             $stmt_member->execute([$team_id, $member_name, ...$member_data]);
                        }
                    } elseif (!empty($member_id)) {
                        $stmt_delete = $conn->prepare("DELETE FROM team_members WHERE member_id = ? AND team_id = ?");
                        $stmt_delete->execute([$member_id, $team_id]);
                    }
                }
                $conn->commit();
                $update_success = true;
            } catch (PDOException $e) {
                $conn->rollBack();
                $update_error = 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage();
            }
        }
    }
}


// ดึงข้อมูล
$stmt_iframe = $conn->query("SELECT category, iframe_url FROM tournament_links");
$iframes = $stmt_iframe->fetchAll(PDO::FETCH_KEY_PAIR);

$team = null;
$members = [];
// ✅ FIX: เพิ่ม is_approved, rejection_reason ใน SELECT
$sql = "SELECT 
    t.team_id, t.team_name, t.coach_name, t.coach_phone, t.leader_school, t.status, t.tournament_id, t.user_id, t.is_approved, t.rejection_reason,
    tn.tournament_name,
    tm.member_id, tm.member_name, tm.game_name, tm.age, tm.phone, tm.position, tm.birthdate
FROM teams t
JOIN tournaments tn ON t.tournament_id = tn.id
LEFT JOIN team_members tm ON t.team_id = tm.team_id
WHERE t.user_id = ?
ORDER BY tm.member_id ASC";
        
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($results) {
    $first_row = $results[0];
    // ✅ FIX: สร้าง Array $team ให้ครบถ้วน
    $team = [
        'team_id' => $first_row['team_id'],
        'team_name' => $first_row['team_name'],
        'coach_name' => $first_row['coach_name'],
        'coach_phone' => $first_row['coach_phone'],
        'leader_school' => $first_row['leader_school'],
        'status' => $first_row['status'],
        'tournament_id' => $first_row['tournament_id'],
        'user_id' => $first_row['user_id'],
        'tournament_name' => $first_row['tournament_name'],
        'is_approved' => $first_row['is_approved'],
        'rejection_reason' => $first_row['rejection_reason']
    ];

    foreach ($results as $row) {
        if ($row['member_id'] !== null) {
            $members[] = [
                'member_id' => $row['member_id'],
                'team_id' => $row['team_id'],
                'member_name' => $row['member_name'],
                'game_name' => $row['game_name'],
                'age' => $row['age'],
                'phone' => $row['phone'],
                'position' => $row['position'],
                'birthdate' => $row['birthdate']
            ];
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function formatStatus($is_approved) {
    $status_map = [
        '0' => '<span class="status-badge status-pending">รอการยืนยัน</span>',
        '1' => '<span class="status-badge status-approved">อนุมัติ</span>',
        '2' => '<span class="status-badge status-rejected">ไม่อนุมัติ</span>',
    ];
    return $status_map[$is_approved] ?? '<span class="status-badge">' . htmlspecialchars($is_approved) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผู้เข้าร่วมการแข่งขัน | ระบบจัดการแข่งขัน ROV</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .form-edit-container { max-width: 900px; margin: auto; }
        .form-section { background: white; border-radius: 8px; padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .form-section h3 { font-size: 1.25rem; font-weight: 600; padding-bottom: 15px; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 10px; color: #111827; }
        .form-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 20px; }
        @media (min-width: 768px) { .form-grid { grid-template-columns: repeat(2, 1fr); } }
        .form-field { display: flex; flex-direction: column; }
        .form-field label { margin-bottom: 8px; font-weight: 500; color: #374151; }
        .form-field input, .form-field select { padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; transition: all 0.2s; font-family: 'Kanit', sans-serif; }
        .form-field input:focus, .form-field select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.4); outline: none; }
        .form-field .readonly-field { background-color: #f3f4f6; color: #4b5563; cursor: not-allowed; }
        .member-card { background: #f9fafb; border: 1px solid #e5e7eb; padding: 20px; border-radius: 8px; margin-bottom: 15px; }
        .member-card h4 { font-weight: 600; margin-bottom: 15px; color: #1f2937; }
        .submit-button { background: var(--primary-color); color: white; padding: 12px 25px; border-radius: 6px; font-size: 1rem; font-weight: 600; text-align: center; border: none; cursor: pointer; transition: background 0.2s; }
        .submit-button:hover { background: #1e40af; }
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; line-height: 1.5; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
        .status-completed { background-color: #dbeafe; color: #1e40af; }
        
        /* ✅ CSS สำหรับกล่องแสดงเหตุผล */
        .placeholder-content.rejected { border-color: #ef4444; background-color: #fef2f2; }
        .rejection-reason-box { text-align: left; max-width: 600px; margin: 20px auto; padding: 15px; background-color: #ffcdd2; border: 1px solid #ef9a9a; border-radius: 8px; color: #c62828; }
        .rejection-reason-box p { margin-top: 5px; word-wrap: break-word; }
        .call-to-action { margin-top: 20px; font-weight: 500; }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header"><div class="logo"><i class="fas fa-trophy"></i><span>ROV Tournament</span></div></div>
        <div class="sidebar-menu">
            <ul>
                <li><a href="participant_dashboard.php" class="active"><i class="fas fa-home"></i><span>หน้าหลัก</span></a></li>
                <li><a href="Certificate/index.php"><i class="fas fa-ranking-star"></i><span>เกียรติบัตร</span></a></li>
                <li><a href="#"><i class="fas fa-cog"></i><span>ตั้งค่า</span></a></li>
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
                <h2>ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['conn']['username']); ?></h2>
                <p>จัดการทีมและดูข้อมูลการแข่งขันของคุณได้ที่นี่</p>
            </div>
             <?php if ($update_success): ?>
                <div class="alert alert-success">อัปเดตข้อมูลทีมเรียบร้อยแล้ว! ข้อมูลของท่านจะถูกส่งให้ผู้ดูแลตรวจสอบอีกครั้ง</div>
            <?php elseif (!empty($update_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($update_error); ?></div>
            <?php endif; ?>
            <div class="schedule-filters">
                <button class="filter-button active" data-filter="team_status" onclick="showContent('team_status')"><i class="fas fa-users"></i> ทีมของฉัน</button>
                <button class="filter-button" data-filter="tournaments" onclick="showContent('tournaments')"><i class="fas fa-calendar-alt"></i> ตารางการแข่งขัน</button>
                <button class="filter-button" data-filter="pending" onclick="showContent('pending')"><i class="fas fa-hourglass-half"></i> ข้อมูลที่ต้องแก้ไข</button>
            </div>
            <div id="content-team_status" class="content-section active">
                <?php if ($team): ?>
                    <form action="" method="post" class="form-edit-container">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                        <div class="form-section">
                            <h3><i class="fas fa-info-circle"></i> ข้อมูลทีมทั่วไป</h3>
                            <div class="form-grid">
                                <div class="form-field"><label>สถานะทีม:</label><div><?php echo formatStatus($team['is_approved']); ?></div></div>
                                <div class="form-field"><label>การแข่งขัน:</label><input type="text" value="<?php echo htmlspecialchars($team['tournament_name']); ?>" class="readonly-field" readonly></div>
                                <div class="form-field"><label for="team_name">ชื่อทีม:</label><input type="text" id="team_name" name="team_name" value="<?php echo htmlspecialchars($team['team_name']); ?>" required></div>
                                <div class="form-field"><label for="coach_name">ผู้ควบคุมทีม:</label><input type="text" id="coach_name" name="coach_name" value="<?php echo htmlspecialchars($team['coach_name']); ?>" required></div>
                                <div class="form-field"><label for="coach_phone">เบอร์โทรผู้ควบคุม:</label><input type="tel" id="coach_phone" name="coach_phone" value="<?php echo htmlspecialchars($team['coach_phone']); ?>" required pattern="[0-9]{9,10}"></div>
                                <div class="form-field"><label for="leader_school">สังกัด/โรงเรียน:</label><input type="text" id="leader_school" name="leader_school" value="<?php echo htmlspecialchars($team['leader_school']); ?>"></div>
                            </div>
                        </div>
                        <div class="form-section">
                             <h3><i class="fas fa-user-friends"></i> รายชื่อสมาชิกในทีม</h3>
                             <?php for ($i = 1; $i <= 8; $i++): $member = $members[$i - 1] ?? null; ?>
                                <div class="member-card">
                                    <h4>สมาชิกคนที่ <?php echo $i; ?></h4>
                                    <input type="hidden" name="member_id_<?php echo $i; ?>" value="<?php echo $member['member_id'] ?? ''; ?>">
                                    <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                                        <div class="form-field"><label for="member_name_<?php echo $i; ?>">ชื่อ-นามสกุล:</label><input type="text" id="member_name_<?php echo $i; ?>" name="member_name_<?php echo $i; ?>" value="<?php echo htmlspecialchars($member['member_name'] ?? ''); ?>"></div>
                                        <div class="form-field"><label for="member_game_name_<?php echo $i; ?>">ชื่อในเกม:</label><input type="text" id="member_game_name_<?php echo $i; ?>" name="member_game_name_<?php echo $i; ?>" value="<?php echo htmlspecialchars($member['game_name'] ?? ''); ?>"></div>
                                        <div class="form-field"><label for="member_age_<?php echo $i; ?>">อายุ:</label><input type="number" id="member_age_<?php echo $i; ?>" name="member_age_<?php echo $i; ?>" value="<?php echo htmlspecialchars($member['age'] ?? ''); ?>"></div>
                                        <div class="form-field"><label for="member_phone_<?php echo $i; ?>">เบอร์โทร:</label><input type="tel" id="member_phone_<?php echo $i; ?>" name="member_phone_<?php echo $i; ?>" value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>"></div>
                                        <div class="form-field"><label for="member_birthdate_<?php echo $i; ?>">วันเกิด:</label><input type="date" id="member_birthdate_<?php echo $i; ?>" name="member_birthdate_<?php echo $i; ?>" value="<?php echo htmlspecialchars($member['birthdate'] ?? ''); ?>"></div>
                                        <div class="form-field"><label for="member_position_<?php echo $i; ?>">ตำแหน่ง:</label>
                                            <select name="member_position_<?php echo $i; ?>" id="member_position_<?php echo $i; ?>">
                                                 <option value="">เลือกตำแหน่ง</option>
                                                <?php $positions = ["เลน Dark Slayer / ออฟเลน", "เลนกลาง / เมท", "เลน Abyssal Dragon / แครี่", "ซัพพอร์ต / แทงค์", "ฟาร์มป่า / แอสซาซิน"];
                                                foreach ($positions as $pos) {
                                                    $selected = (isset($member['position']) && $member['position'] == $pos) ? 'selected' : '';
                                                    echo "<option value=\"$pos\" $selected>$pos</option>";
                                                }?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                             <?php endfor; ?>
                        </div>
                        <div style="text-align: center;">
                            <button type="submit" name="update_team" class="submit-button"><i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="placeholder-content">
                        <h3><i class="fas fa-file-alt"></i> ยังไม่ได้ลงทะเบียนทีม</h3>
                        <p>คุณยังไม่ได้ลงทะเบียนทีมสำหรับการแข่งขัน สามารถสมัครได้ที่นี่</p>
                        <a href="../register.php" class="button-primary"><i class="fas fa-plus-circle"></i> คลิกที่นี่เพื่อสมัคร</a>
                    </div>
                <?php endif; ?>
            </div>
            <div id="content-tournaments" class="content-section">
                <div class="tournament-container">
                    <div class="tournament-tabs"><button class="tournament-tab active" onclick="switchTournament(event, 'above_18')"><i class="fas fa-users"></i> รุ่น 18 ปีขึ้นไป</button><button class="tournament-tab" onclick="switchTournament(event, 'under_18')"><i class="fas fa-user-friends"></i> รุ่นต่ำกว่า 18 ปี</button></div>
                    <div id="tournament-above_18" class="tournament-page active">
                        <h3>ตารางการแข่งขันรุ่นอายุ 18 ปีขึ้นไป</h3>
                        <?php if (!empty($iframes['above_18'])): ?>
                            <div class="loading-spinner" id="loading-above-18"><i class="fas fa-spinner"></i><p>กำลังโหลด...</p></div>
                            <iframe class="tournament-iframe" src="<?= htmlspecialchars($iframes['above_18']) ?>" onload="this.previousElementSibling.style.display='none'"></iframe>
                        <?php else: ?>
                            <div class="no-iframe-message"><i class="fas fa-exclamation-triangle"></i><p>ยังไม่มีตารางการแข่งขันสำหรับรุ่นนี้</p></div>
                        <?php endif; ?>
                    </div>
                    <div id="tournament-under_18" class="tournament-page">
                        <h3>ตารางการแข่งขันรุ่นอายุต่ำกว่า 18 ปี</h3>
                        <?php if (!empty($iframes['under_18'])): ?>
                             <div class="loading-spinner" id="loading-under-18"><i class="fas fa-spinner"></i><p>กำลังโหลด...</p></div>
                            <iframe class="tournament-iframe" src="<?= htmlspecialchars($iframes['under_18']) ?>" onload="this.previousElementSibling.style.display='none'"></iframe>
                        <?php else: ?>
                            <div class="no-iframe-message"><i class="fas fa-exclamation-triangle"></i><p>ยังไม่มีตารางการแข่งขันสำหรับรุ่นนี้</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div id="content-pending" class="content-section">
                <?php if ($team && $team['is_approved'] == 2): ?>
                    <div class="placeholder-content rejected">
                        <h3><i class="fas fa-times-circle"></i> ข้อมูลของคุณต้องได้รับการแก้ไข</h3>
                        <p>ผู้ดูแลระบบได้ตรวจสอบข้อมูลของทีม "<strong><?php echo htmlspecialchars($team['team_name']); ?></strong>" และพบว่าข้อมูลบางส่วนยังไม่ถูกต้อง</p>
                        <div class="rejection-reason-box">
                            <strong>เหตุผลจากผู้ดูแลระบบ:</strong>
                            <p><?php echo nl2br(htmlspecialchars($team['rejection_reason'])); ?></p>
                        </div>
                        <p class="call-to-action">
                            กรุณากลับไปที่แท็บ "<strong>ทีมของฉัน</strong>" เพื่อแก้ไขข้อมูลให้ถูกต้อง 
                            <br>จากนั้นกด "บันทึกการเปลี่ยนแปลง" อีกครั้งเพื่อส่งข้อมูลให้ผู้ดูแลตรวจสอบใหม่
                        </p>
                    </div>
                <?php elseif ($team && $team['is_approved'] == 0): ?>
                    <div class="placeholder-content">
                        <h3><i class="fas fa-hourglass-half"></i> ทีมที่รออนุมัติ</h3>
                        <p>ทีม "<strong><?php echo htmlspecialchars($team['team_name']); ?></strong>" ของคุณถูกส่งข้อมูลเรียบร้อยแล้ว และกำลังรอการตรวจสอบจากผู้จัด</p>
                    </div>
                <?php else: ?>
                    <div class="placeholder-content">
                        <h3><i class="fas fa-check-circle"></i> ไม่มีข้อมูลที่รอการแก้ไข</h3>
                        <p>ขณะนี้ทีมของคุณผ่านการอนุมัติแล้ว หรือไม่มีรายการที่ต้องดำเนินการแก้ไข</p>
                    </div>
                <?php endif; ?>
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
        function showContent(contentType) {
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            document.getElementById('content-' + contentType).classList.add('active');
            document.querySelectorAll('.filter-button').forEach(b => b.classList.remove('active'));
            document.querySelector(`[data-filter="${contentType}"]`).classList.add('active');
        }
        function switchTournament(event, category) {
            document.querySelectorAll('.tournament-page').forEach(p => p.classList.remove('active'));
            document.getElementById('tournament-' + category).classList.add('active');
            document.querySelectorAll('.tournament-tab').forEach(t => t.classList.remove('active'));
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>