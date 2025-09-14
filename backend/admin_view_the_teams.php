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

// ดึงประเภทการแข่งขันทั้งหมดจาก teams
$stmt = $conn->prepare("SELECT DISTINCT tournament_id FROM teams ORDER BY tournament_id ASC");
$stmt->execute();
$tournament_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
    $sql .= " AND t.tournament_id = :category";
    $params[':category'] = $category;
}
$sql .= " ORDER BY tr.tournament_name ASC, t.team_name ASC";

$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการทีม</title>
    <link rel="stylesheet" href="../css/admin_view_the_teams.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-users"></i> จัดการทีม</h1>
        <a href="admin_dashboard.php" class="btn-dashboard"><i class="fas fa-tachometer-alt"></i>กลับไปหน้าแดชบอร์ด</a>
        <!-- ฟอร์มค้นหา -->
        <form method="get">
            <input type="text" name="search" placeholder="ค้นหาชื่อทีม..." value="<?= htmlspecialchars($search) ?>">
            <select name="category">
                <option value="">-- ทุกประเภทการแข่งขัน --</option>
                <?php foreach ($tournament_ids as $tid): ?>
                    <option value="<?= $tid ?>" <?= ($category == $tid) ? 'selected' : '' ?>>
                        <?= "Tournament ID #" . $tid ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit"><i class="fas fa-search"></i> ค้นหา</button>
            <a href="admin_view_the_teams.php"><i class="fas fa-times"></i> ล้าง</a>
        </form>

        <!-- ตารางรายชื่อทีม -->
        <table>
            <thead>
                <tr>
                    <th>ชื่อทีม</th>
                    <th>โค้ช</th>
                    <th>เบอร์โทร</th>
                    <th>โรงเรียน</th>
                    <th>การแข่งขัน</th>
                    <th>สถานะ</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($teams)): ?>
                    <?php foreach ($teams as $team): ?>
                        <tr>
                            <td><?= htmlspecialchars($team['team_name']) ?></td>
                            <td><?= htmlspecialchars($team['coach_name']) ?></td>
                            <td><?= htmlspecialchars($team['coach_phone']) ?></td>
                            <td><?= htmlspecialchars($team['leader_school']) ?></td>
                            <td><?= htmlspecialchars($team['tournament_name']) ?></td>
                            <td><?= htmlspecialchars($team['status']) ?></td>
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
                        <td colspan="7">ไม่พบข้อมูลทีม</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
