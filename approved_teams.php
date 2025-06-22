<?php
require 'db.php'; // เชื่อมต่อฐานข้อมูล
require 'db_connect.php';

// ดึงข้อมูลเฉพาะทีมที่ได้รับการอนุมัติ
$sql = "SELECT * FROM teams WHERE status = 'approved'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายชื่อทีมที่อนุมัติแล้ว</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table {
            width: 80%;
            border-collapse: collapse;
            margin: 30px auto;
        }
        th, td {
            padding: 10px;
            border: 1px solid #999;
            text-align: center;
        }
        th {
            background-color: #f0f0f0;
        }
        h2 {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>

<h2>รายชื่อทีมที่ได้รับการอนุมัติ</h2>

<table>
    <thead>
        <tr>
            <th>ชื่อทีม</th>
            <th>สมาชิก</th>
            <th>อาจารย์ที่ปรึกษา</th>
            <th>เบอร์ติดต่อ</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result instanceof mysqli_result && $result->num_rows > 0): ?>
            <?php while ($team = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($team['team_name']) ?></td>
                    <td><?= htmlspecialchars($team['members']) ?></td>
                    <td><?= htmlspecialchars($team['advisor']) ?></td>
                    <td><?= htmlspecialchars($team['contact_number']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">ยังไม่มีทีมที่ได้รับการอนุมัติ</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
