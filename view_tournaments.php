<?php
// เปิด error
error_reporting(E_ALL); 
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ดึงรายการทัวร์นาเมนต์ทั้งหมด
$sql = "SELECT id, tournament_name, tournament_url, created_at FROM tournaments ORDER BY created_at DESC";
$result = $conn->query($sql);

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>รายการทัวร์นาเมนต์</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="css/view_tournaments.css">
<style>
    body {
        font-family: 'Kanit', sans-serif;
        background: #f4f6f9;
        margin: 0;
        padding: 0;
    }
    .container {
        width: 90%;
        max-width: 1000px;
        margin: 30px auto;
        background: #fff;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    h1 {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
    }
    th, td {
        padding: 12px;
        border-bottom: 1px solid #ddd;
        text-align: left;
    }
    th {
        background: #ff9800;
        color: white;
    }
    tr:hover {
        background: #f1f1f1;
    }
    .actions a {
        margin-right: 8px;
        text-decoration: none;
        padding: 6px 10px;
        border-radius: 5px;
        font-size: 14px;
    }
    .btn-edit {
        background: #4CAF50;
        color: white;
    }
    .btn-delete {
        background: #f44336;
        color: white;
    }
    .btn-add-team {
        background: #2196F3;
        color: white;
    }
    .nav-link {
        display: inline-block;
        margin-top: 15px;
        text-decoration: none;
        background: #ff9800;
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
    }
</style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-trophy"></i> รายการทัวร์นาเมนต์</h1>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <tr>
                <th>ชื่อทัวร์นาเมนต์</th>
                <th>ลิงก์ Challonge</th>
                <th>วันที่สร้าง</th>
                <th>การจัดการ</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['tournament_name']) ?></td>
                    <td>
                        <?php if (!empty($row['tournament_url'])): ?>
                            <a href="<?= htmlspecialchars($row['tournament_url']) ?>" target="_blank">เปิดลิงก์</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['created_at'] ?? '-') ?></td>
                    <td class="actions">
                        <a href="edit_tournament.php?id=<?= $row['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> แก้ไข</a>
                        <a href="delete_tournament.php?id=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('ยืนยันการลบ?')"><i class="fas fa-trash"></i> ลบ</a>
                        <a href="add_team.php?tournament_id=<?= $row['id'] ?>" class="btn-add-team"><i class="fas fa-users"></i> เพิ่มทีม</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p style="text-align:center;">ไม่มีทัวร์นาเมนต์ในระบบ</p>
    <?php endif; ?>

    <div style="text-align:center;">
        <a href="add_tournament.php" class="nav-link"><i class="fas fa-plus-circle"></i> เพิ่มทัวร์นาเมนต์</a>
    </div>
</div>
</body>
</html>
