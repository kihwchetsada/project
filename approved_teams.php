<?php
require 'db.php'; // เชื่อมต่อฐานข้อมูล
require 'db_connect.php'; // $conn เป็น PDO

$sql = "
SELECT 
    t.*, 
    GROUP_CONCAT(CONCAT(m.member_name, ' (', m.position, ')') SEPARATOR '\n') AS members
FROM teams t
LEFT JOIN team_members m ON t.team_id = m.team_id
WHERE t.is_approved = 1
GROUP BY t.team_id
";
$stmt = $conn->query($sql);
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
$team_count = count($teams);

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายชื่อทีมที่อนุมัติแล้ว</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link rel="stylesheet" href="css/approved_teams.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-trophy"></i> รายชื่อทีมที่ได้รับการอนุมัติ</h1>
            <p class="subtitle">ทีมที่ผ่านการคัดเลือกและพร้อมเข้าร่วมการแข่งขัน</p>
        </div>
        <div class="stats-card">
            <div class="stats-content">
                <div class="stats-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-text">
                    <h3><?= $team_count ?></h3>
                    <p>ทีมที่ได้รับการอนุมัติทั้งหมด</p>
                </div>
            </div>
        </div>

        <div class="table-container">
        <?php if ($team_count > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><i class="fas fa-flag"></i>ชื่อทีม</th>
                    <th><i class="fas fa-users"></i>สมาชิก</th>
                    <th><i class="fas fa-user-tie"></i>อาจารย์ที่ปรึกษา</th>
                    <th><i class="fas fa-phone"></i>เบอร์ติดต่อ</th>
                    <th><i class="fas fa-user"></i>อนุมัติโดย</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teams as $team): ?>
                    <tr>
                        <td class="team-name"><?= htmlspecialchars($team['team_name']) ?></td>
                        <td class="members-list"><?= nl2br(htmlspecialchars($team['members'])) ?></td>
                        <td class="coach-name"><?= htmlspecialchars($team['coach_name']) ?></td>
                        <td><span class="coach-phone"><?= htmlspecialchars($team['coach_phone']) ?></span></td>
                        <td><span class="approved-by"><?= htmlspecialchars($team['approved_by']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">
            <i class="fas fa-inbox"></i>
            <h3>ยังไม่มีข้อมูล</h3>
            <p>ยังไม่มีทีมที่ได้รับการอนุมัติในระบบ</p>
        </div>
    <?php endif; ?>
        </div>
    </div>
</body>
</html>