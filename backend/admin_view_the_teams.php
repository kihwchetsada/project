<?php
// ตั้งค่า timezone
date_default_timezone_set('Asia/Bangkok');
session_start();

// ตรวจสอบการล็อกอิน (ปรับตามระบบจริง)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
include '../db_connect.php';

// รับค่าค้นหาและกรอง
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// ดึงประเภทการแข่งขันทั้งหมดจาก teams (ควรดึงจาก tournaments เพื่อให้ได้ชื่อมาด้วย)
// **ปรับปรุง: ดึงชื่อ Tournament แทน ID เพื่อแสดงผลใน Dropdown ได้ทันที**
$stmt = $conn->prepare("SELECT id, tournament_name FROM tournaments ORDER BY id ASC");
$stmt->execute();
$tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงทีมทั้งหมด
$sql = "SELECT t.team_id, t.team_name, t.coach_name, t.coach_phone, t.leader_school, 
             t.status, tr.tournament_name
         FROM teams t
         LEFT JOIN tournaments tr ON t.tournament_id = tr.id
         WHERE 1";

$params = [];
if (!empty($search)) {
    $sql .= " AND t.team_name LIKE :search";
    $params[':search'] = "%" . $search . "%";
}
if (!empty($category)) {
    // กรองด้วย ID
    $sql .= " AND t.tournament_id = :category";
    $params[':category'] = $category;
}
$sql .= " ORDER BY tr.tournament_name ASC, t.team_name ASC";

$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    // ต้องตรวจสอบประเภทของค่าที่ผูก หากเป็นตัวเลข (category) ควรใช้ PARAM_INT
    $param_type = ($key === ':category') ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $param_type);
}
$stmt->execute();
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// เตรียมคำสั่ง SQL สำหรับดึงสมาชิกทีม
$stmt_members = $conn->prepare("SELECT member_name, game_name, position 
                                 FROM team_members 
                                 WHERE team_id = :team_id 
                                 ORDER BY member_name ASC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการทีม</title>
    <link rel="stylesheet" href="../css/admin_view_the_teams.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* สไตล์สำหรับรายชื่อสมาชิก */
        .team-members-list {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
            font-size: 0.9em;
            text-align: left;
        }
        .team-members-list li {
            padding: 2px 0;
            border-bottom: 1px dotted #ccc;
        }
        .team-members-list li:last-child {
            border-bottom: none;
        }
        
        /* --- สไตล์สำหรับปุ่มและคอนเทนเนอร์ --- */
        .btn-toggle-members {
            background-color: #007bff; /* Blue */
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 0.9em;
            width: 120px; /* กำหนดความกว้างให้ปุ่ม */
            text-align: left;
        }
        .btn-toggle-members:hover {
            background-color: #0056b3;
        }
        .btn-toggle-members .fa-eye-slash {
            color: #ffc107; /* สีไอคอนตอนซ่อน */
        }
        .members-container {
            margin-top: 10px;
            border-top: 1px solid #eee;
            padding-top: 5px;
            /* ต้องมี CSS หลักสำหรับตารางไม่ให้ความสูงเซลล์เพี้ยนเมื่อแสดงผล */
        }
        /* ----------------------------------------- */

        /* 💥 โค้ดที่เพิ่ม: สไตล์สำหรับปุ่มย้อนกลับที่ชัดเจน (btn-dashboard) */
        .btn-dashboard {
            /* 1. ตำแหน่งคงที่ (Fixed Position) */
            position: fixed;
            top: 20px;       
            left: 20px;      
            z-index: 1000;   
        
            /* 2. สไตล์ Neon สว่างพิเศษ (เพื่อความชัดเจน) */
            display: inline-flex;
            align-items: center;
            padding: 10px 18px; 
            
            /* 💥 สีหลัก: เขียวนีออนสว่าง (เพื่อตัดกับพื้นหลังเข้ม) */
            border: 3px solid #00ffc8; 
            color: #00ffc8; 
            background-color: rgba(0, 0, 0, 0.7); 
            
            border-radius: 6px; 
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            
            /* 💥 เพิ่มเงาที่สว่างและชัดเจน */
            box-shadow: 0 0 15px rgba(0, 255, 200, 0.8), 0 0 5px rgba(255, 255, 255, 0.3); 
            transition: all 0.3s ease;
        }
        
        /* 3. สไตล์เมื่อนำเมาส์ไปวาง (Hover Effect) */
        .btn-dashboard:hover {
            background-color: #00ffc8; 
            color: #000000; 
            border-color: #ffffff;
            transform: scale(1.05); 
            box-shadow: 0 0 20px #00ffc8, 0 0 40px #00ffc8; 
        }
        
        /* 4. สไตล์ไอคอน */
        .btn-dashboard i {
            margin-right: 8px;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <a href="admin_dashboard.php" class="btn-dashboard"><i class="fas fa-tachometer-alt"></i> กลับไปหน้าแดชบอร์ด</a>
    <div class="container">
        <h1><i class="fas fa-users"></i> จัดการทีม</h1>
        
        <form method="get">
            <input type="text" name="search" placeholder="ค้นหาชื่อทีม..." value="<?= htmlspecialchars($search) ?>">
            <select name="category">
                <option value="">-- ทุกประเภทการแข่งขัน --</option>
                <?php foreach ($tournaments as $tr): ?>
                    <option value="<?= $tr['id'] ?>" <?= ($category == $tr['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tr['tournament_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit"><i class="fas fa-search"></i> ค้นหา</button>
            <a href="admin_view_the_teams.php" class="btn-clear"><i class="fas fa-times"></i> ล้าง</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ชื่อทีม</th>
                    <th>โค้ช</th>
                    <th>เบอร์โทร</th>
                    <th>โรงเรียน</th>
                    <th>การแข่งขัน</th>
                    <th>สถานะ</th>
                    <th>สมาชิก</th> <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($teams)): ?>
                    <?php foreach ($teams as $team): ?>
                        <?php
                        // ดึงสมาชิกสำหรับทีมนี้
                        $stmt_members->execute([':team_id' => $team['team_id']]);
                        $members = $stmt_members->fetchAll(PDO::FETCH_ASSOC);
                        $memberCount = count($members);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($team['team_name']) ?></td>
                            <td><?= htmlspecialchars($team['coach_name']) ?></td>
                            <td><?= htmlspecialchars($team['coach_phone']) ?></td>
                            <td><?= htmlspecialchars($team['leader_school']) ?></td>
                            <td><?= htmlspecialchars($team['tournament_name']) ?></td>
                            <td><?= htmlspecialchars($team['status']) ?></td>
                            
                            <td>
                                <?php if ($memberCount > 0): ?>
                                    <button type="button" class="btn-toggle-members" 
                                            onclick="toggleMembers(this, 'members-<?= $team['team_id'] ?>', <?= $memberCount ?>)">
                                        <i class="fas fa-eye"></i> แสดง (<?= $memberCount ?> คน)
                                    </button>
                                    
                                    <div id="members-<?= $team['team_id'] ?>" class="members-container" style="display: none;">
                                        <ul class="team-members-list">
                                            <?php foreach ($members as $member): ?>
                                                <li>
                                                    <strong><?= htmlspecialchars($member['member_name']) ?></strong><br>
                                                    (<?= htmlspecialchars($member['game_name']) ?> - <?= htmlspecialchars($member['position']) ?>)
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <em>- ไม่มีสมาชิก -</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_team.php?team_id=<?= $team['team_id'] ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i> แก้ไข
                                </a>
                                <a href="delete_team.php?team_id=<?= $team['team_id'] ?>" 
                                    class="btn-delete" 
                                    onclick="return confirm('คุณต้องการลบทีมนี้หรือไม่?');">
                                    <i class="fas fa-trash"></i> ลบ
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">ไม่พบข้อมูลทีม</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    function toggleMembers(button, elementId, memberCount) {
        var content = document.getElementById(elementId);
        
        if (content.style.display === "none" || content.style.display === "") {
            // ถ้าซ่อนอยู่ ให้แสดง
            content.style.display = "block";
            button.innerHTML = '<i class="fas fa-eye-slash"></i> ซ่อน';
        } else {
            // ถ้าแสดงอยู่ ให้ซ่อน
            content.style.display = "none";
            button.innerHTML = '<i class="fas fa-eye"></i> แสดง (' + memberCount + ' คน)';
        }
    }
    </script>
    </body>
</html>