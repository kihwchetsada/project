<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Bangkok');
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require '../db_connect.php';

$team_id = null;
$team = null;
$members = [];
$all_tournaments = [];
$user_name = "N/A"; 

// ตัวเลือกสถานะจาก ENUM
$status_options = ['registered', 'confirmed', 'cancelled', 'completed'];

// --- (เพิ่ม) ตัวเลือกตำแหน่ง ---
$position_options = [
    "เลน Dark Slayer / ออฟเลน",
    "เลนกลาง / เมท",
    "เลน Abyssal Dragon / แครี่",
    "ซัพพอร์ต / แทงค์",
    "ฟาร์มป่า / แอสซาซิน"
];
// ------------------------------

if (isset($_GET['team_id']) && is_numeric($_GET['team_id'])) {
    $team_id = intval($_GET['team_id']);
    
    // 1. ดึงข้อมูลทีม
    $stmt = $conn->prepare("SELECT * FROM teams WHERE team_id = :team_id");
    $stmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
    $stmt->execute();
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$team) {
        die("ไม่พบทีมนี้ (ID: $team_id)");
    }

    // 2. ดึงชื่อผู้ใช้ (เจ้าของทีม)
    if (!empty($team['user_id'])) {
        $stmt_user = $conn->prepare("SELECT username FROM users WHERE id = :user_id");
        $stmt_user->execute([':user_id' => $team['user_id']]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $user_name = $user['username'];
        }
    }

    // 3. ดึงข้อมูลสมาชิกในทีม
    $stmt_members = $conn->prepare("SELECT * FROM team_members WHERE team_id = :team_id ORDER BY member_id ASC");
    $stmt_members->execute([':team_id' => $team_id]);
    $members = $stmt_members->fetchAll(PDO::FETCH_ASSOC);

    // 4. ดึงข้อมูล Tournaments ทั้งหมด
    $stmt_tournaments = $conn->prepare("SELECT id, tournament_name FROM tournaments ORDER BY tournament_name ASC");
    $stmt_tournaments->execute();
    $all_tournaments = $stmt_tournaments->fetchAll(PDO::FETCH_ASSOC);

} else {
    die("ไม่ได้ระบุ team_id หรือ team_id ไม่ถูกต้อง");
}

// --- จัดการการบันทึกข้อมูล (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $conn->beginTransaction();
    try {
        
        // 1. อัปเดตข้อมูลทีมหลัก (เฉพาะส่วนที่แก้ไขได้)
        $sql_update_team = "UPDATE teams SET 
            team_name = :team_name, 
            coach_name = :coach_name, 
            coach_phone = :coach_phone,
            leader_school = :leader_school,
            tournament_id = :tournament_id
        WHERE team_id = :team_id";

        $stmt_team = $conn->prepare($sql_update_team);
        
        $stmt_team->execute([
            ':team_name' => trim($_POST['team_name']),
            ':coach_name' => trim($_POST['coach_name']),
            ':coach_phone' => trim($_POST['coach_phone']),
            ':leader_school' => trim($_POST['leader_school']),
            ':tournament_id' => (int)$_POST['tournament_id'],
            ':team_id' => $team_id
        ]);

        // 2. อัปเดตข้อมูลสมาชิก (ไม่ต้องแก้ไขส่วนนี้)
        // (PHP ส่วนนี้ยังคงทำงานได้ถูกต้อง แม้จะเปลี่ยนเป็น select)
        if (isset($_POST['member_name'])) {
            
            $stmt_update_member = $conn->prepare(
                "UPDATE team_members SET 
                    member_name = :member_name, 
                    game_name = :game_name, 
                    position = :position,
                    phone = :phone,
                    age = :age,
                    birthdate = :birthdate 
                WHERE member_id = :member_id AND team_id = :team_id" 
            );
            
            foreach ($_POST['member_name'] as $member_pk => $name) {
                $age_val = trim($_POST['age'][$member_pk]);
                $bday_val = trim($_POST['birthdate'][$member_pk]);
                
                $stmt_update_member->execute([
                    ':member_name' => trim($name),
                    ':game_name' => trim($_POST['game_name'][$member_pk]),
                    ':position' => trim($_POST['position'][$member_pk]), // รับค่าจาก select
                    ':phone' => trim($_POST['phone'][$member_pk]),
                    ':age' => empty($age_val) ? null : (int)$age_val,
                    ':birthdate' => empty($bday_val) ? null : $bday_val,
                    ':member_id' => $member_pk,
                    ':team_id' => $team_id 
                ]);
            }
        }

        $conn->commit(); 
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?team_id=$team_id&success=1");
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        die("เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลทีม</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        /* === Modern & Clean Theme === (ไม่เปลี่ยนแปลง) */
        :root {
            --bg-light: #f8f9fa;
            --bg-white: #ffffff;
            --border-color: #dee2e6;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --accent-primary: #007bff;
            --accent-success-bg: #d4edda;
            --accent-success-text: #155724;
            --accent-success-border: #c3e6cb;
            --status-registered: #fd7e14; /* ส้ม */
            --status-confirmed: #28a745; /* เขียว */
            --status-cancelled: #dc3545; /* แดง */
            --status-completed: #6c757d; /* เทา */
        }

        body { 
            font-family: 'Kanit', sans-serif; 
            background-color: var(--bg-light); 
            color: var(--text-primary);
            margin: 0; 
            padding: 20px; 
        }
        .container { 
            max-width: 900px; 
            margin: 20px auto; 
            background-color: var(--bg-white); 
            padding: 25px 35px; 
            border-radius: 12px; 
            border: 1px solid var(--border-color);
            box-shadow: 0 8px 24px rgba(0,0,0,0.05);
        }
        h1 { 
            color: var(--text-primary); 
            border-bottom: 2px solid #f1f1f1; 
            padding-bottom: 15px; 
            font-size: 1.8em; 
            font-weight: 700;
        }
        h1 .team-name { color: var(--accent-primary); }
        h2 { 
            color: var(--text-primary); 
            border-bottom: 1px solid #f1f1f1; 
            padding-bottom: 10px; 
            margin-top: 40px; 
            font-weight: 500;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 500; 
            color: var(--text-secondary); 
            font-size: 0.9em;
        }

        input[type="text"],
        input[type="date"],
        textarea,
        select { 
            width: 100%; 
            padding: 12px; 
            box-sizing: border-box; 
            background-color: #ffffff;
            color: var(--text-primary);
            border: 1px solid var(--border-color); 
            border-radius: 6px;
            transition: all 0.3s ease;
            font-family: 'Kanit', sans-serif;
        }
        textarea { min-height: 100px; }
        select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22%236c757d%22%3E%3Cpath%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%2D1%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1.5em;
        }
        input[type="text"]:focus,
        input[type="date"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
        }

        .form-display-value {
            width: 100%; 
            padding: 12px; 
            box-sizing: border-box; 
            background-color: #f4f4f4; /* สีเทาอ่อน */
            color: var(--text-primary);
            border: 1px solid var(--border-color); 
            border-radius: 6px;
            min-height: 48.5px; /* ให้ความสูงเท่า input */
            display: flex;
            align-items: center;
        }
        
        .status-confirmed, .status-completed { color: var(--status-confirmed); font-weight: 500; }
        .status-registered { color: var(--status-registered); font-weight: 500; }
        .status-cancelled { color: var(--status-cancelled); font-weight: 500; }
        .status-default { color: var(--text-secondary); font-weight: 500; }


        .btn { 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            text-decoration: none; 
            font-size: 1em; 
            font-weight: 500;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        .btn-submit { 
            background-color: var(--accent-primary); 
            color: white; 
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.2);
        }
        .btn-submit:hover { 
            background-color: #0069d9;
            transform: translateY(-2px);
        }
        .btn-back { 
            background-color: var(--text-secondary); 
            color: white; 
            margin-right: 10px; 
        }
        .btn-back:hover { background-color: #5a6268; }

        .alert-success { 
            padding: 15px; 
            background-color: var(--accent-success-bg); 
            color: var(--accent-success-text); 
            border: 1px solid var(--accent-success-border); 
            border-radius: 6px; 
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .member-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .member-table th, .member-table td { 
            padding: 12px; 
            border: 1px solid #f1f1f1; 
            text-align: left; 
            vertical-align: middle; 
        }
        .member-table th { background-color: #f9f9f9; font-weight: 500; color: var(--text-secondary); }
        
        .member-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            grid-gap: 20px; 
        }
        hr { border: none; border-top: 1px solid #f1f1f1; margin: 40px 0; }
    </style>
</head>
<body>
    <div class="container">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle fa-lg"></i> 
                <strong>สำเร็จ!</strong> บันทึกข้อมูลเรียบร้อยแล้ว
            </div>
        <?php endif; ?>

        <h1>
            <i class="fas fa-edit"></i> แก้ไขข้อมูลทีม: 
            <span class="team-name"><?php echo htmlspecialchars($team['team_name']); ?></span>
        </h1>
        
        <form method="post">
        
            <h2><i class="fas fa-shield-alt"></i> ข้อมูลทีม (แก้ไขได้)</h2>
            <div class="form-group">
                <label for="team_name">ชื่อทีม</label>
                <input type="text" id="team_name" name="team_name" value="<?php echo htmlspecialchars($team['team_name']); ?>" required>
            </div>
            <div class="member-grid">
                <div class="form-group">
                    <label for="coach_name">ชื่อโค้ช</label>
                    <input type="text" id="coach_name" name="coach_name" value="<?php echo htmlspecialchars($team['coach_name']); ?>">
                </div>
                <div class="form-group">
                    <label for="coach_phone">เบอร์โทรโค้ช</label>
                    <input type="text" id="coach_phone" name="coach_phone" value="<?php echo htmlspecialchars($team['coach_phone']); ?>">
                </div>
                <div class="form-group">
                    <label for="leader_school">โรงเรียน/สถาบัน</label>
                    <input type="text" id="leader_school" name="leader_school" value="<?php echo htmlspecialchars($team['leader_school']); ?>">
                </div>
            </div>

            <h2><i class="fas fa-cogs"></i> ข้อมูลระบบ (แก้ไขได้)</h2>
            <div class="form-group">
                <label for="tournament_id">การแข่งขัน</label>
                <select id="tournament_id" name="tournament_id">
                    <option value="">-- เลือกการแข่งขัน --</option>
                    <?php foreach ($all_tournaments as $tournament): ?>
                        <option value="<?php echo $tournament['id']; ?>" <?php echo ($team['tournament_id'] == $tournament['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tournament['tournament_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <h2><i class="fas fa-user-check"></i> ข้อมูลผู้สมัคร (แสดงผลเท่านั้น)</h2>
            <div class="member-grid">
                <div class="form-group">
                    <label>ผู้สมัคร (เจ้าของทีม)</label>
                    <div class="form-display-value">
                        <i class="fas fa-user" style="margin-right: 8px; color: var(--text-secondary);"></i>
                        <?php echo htmlspecialchars($user_name); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>สถานะทีม</label>
                    <div class="form-display-value">
                        <?php
                            $status_class = 'status-default';
                            if ($team['status'] == 'confirmed') $status_class = 'status-confirmed';
                            if ($team['status'] == 'registered') $status_class = 'status-registered';
                            if ($team['status'] == 'cancelled') $status_class = 'status-cancelled';
                            if ($team['status'] == 'completed') $status_class = 'status-completed';
                        ?>
                        <span class="<?php echo $status_class; ?>">
                            <?php echo htmlspecialchars(ucfirst($team['status'])); ?>
                        </span>
                    </div>
                </div>
            </div>

            <h2><i class="fas fa-check-square"></i> การอนุมัติ (แสดงผลเท่านั้น)</h2>
            <div class="member-grid">
                <div class="form-group">
                    <label>สถานะการอนุมัติ</label>
                    <div class="form-display-value">
                        <?php echo ($team['is_approved'] == 1) 
                            ? '<span class="status-confirmed"><i class="fas fa-check-circle" style="margin-right: 5px;"></i> อนุมัติแล้ว</span>' 
                            : '<span class="status-registered"><i class="fas fa-clock" style="margin-right: 5px;"></i> รอดำเนินการ</span>'; 
                        ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>อนุมัติโดย (Admin)</label>
                    <div class="form-display-value">
                        <?php echo htmlspecialchars(empty($team['approved_by']) ? 'N/A' : $team['approved_by']); ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>เหตุผลการปฏิเสธ (หากไม่อนุมัติ)</label>
                <div class="form-display-value" style="min-height: 100px; white-space: pre-wrap; align-items: flex-start;">
                    <?php echo htmlspecialchars(empty($team['rejection_reason']) ? 'N/A' : $team['rejection_reason']); ?>
                </div>
            </div>


            <h2><i class="fas fa-users"></i> จัดการสมาชิกในทีม (แก้ไขได้)</h2>
            <div style="overflow-x:auto;">
                <table class="member-table">
                    <thead>
                        <tr>
                            <th>ชื่อ-สกุล</th>
                            <th>ชื่อในเกม</th>
                            <th>ตำแหน่ง</th>
                            <th>เบอร์โทร</th>
                            <th>อายุ</th>
                            <th>วันเกิด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($members)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px;">ยังไม่มีสมาชิกในทีมนี้</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><input type="text" name="member_name[<?php echo $member['member_id']; ?>]" value="<?php echo htmlspecialchars($member['member_name']); ?>"></td>
                                    <td><input type="text" name="game_name[<?php echo $member['member_id']; ?>]" value="<?php echo htmlspecialchars($member['game_name']); ?>"></td>
                                    
                                    <td>
                                        <select name="position[<?php echo $member['member_id']; ?>]">
                                            <option value="">เลือกตำแหน่งที่เล่น</option>
                                            <?php foreach ($position_options as $option): ?>
                                                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($member['position'] == $option) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($option); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="phone[<?php echo $member['member_id']; ?>]" value="<?php echo htmlspecialchars($member['phone']); ?>"></td>
                                    <td><input type="text" name="age[<?php echo $member['member_id']; ?>]" value="<?php echo htmlspecialchars($member['age']); ?>" style="width: 70px;"></td>
                                    <td>
                                        <input type="date" name="birthdate[<?php echo $member['member_id']; ?>]" value="<?php echo htmlspecialchars($member['birthdate']); ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <hr>
            
            <a href="admin_view_the_teams.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> กลับไปหน้าหลัก
            </a>
            <button type="submit" class="btn btn-submit">
                <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
            </button>
        </form>
    </div>
</body>
</html>