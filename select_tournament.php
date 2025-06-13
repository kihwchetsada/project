<?php
// ดึงทัวร์นาเมนต์ทั้งหมดจากฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tournaments = $conn->query("SELECT id, tournament_name FROM tournaments");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>เลือกทัวร์นาเมนต์</title>
</head>
<body>
    <h2>เลือกทัวร์นาเมนต์เพื่อส่งทีมเข้า Challonge</h2>
    <form action="submit_to_challonge.php" method="post">
        <label for="tournament_id">เลือกทัวร์นาเมนต์:</label>
        <select name="tournament_id" id="tournament_id" required>
            <option value="">-- กรุณาเลือก --</option>
            <?php while ($row = $tournaments->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['tournament_name']) ?></option>
            <?php endwhile; ?>
        </select>
        <br><br>
        <button type="submit">ส่งทีมเข้าระบบ Challonge</button>
    </form>
</body>
</html>
