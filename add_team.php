<?php
// เปิด error
error_reporting(E_ALL); ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $team_name = $conn->real_escape_string($_POST['team_name']);
    $tournament_id = (int)$_POST['tournament_id'];

    if (!empty($team_name) && $tournament_id > 0) {
        $sql = "INSERT INTO teams (team_name, tournament_id) VALUES ('$team_name', $tournament_id)";
        if ($conn->query($sql) === TRUE) {
            $message = "เพิ่มทีมสำเร็จ!";
        } else {
            $message = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    } else {
        $message = "กรุณากรอกชื่อทีมและเลือกทัวร์นาเมนต์";
    }
}

// ดึงทัวร์นาเมนต์
$tournaments = $conn->query("SELECT id, tournament_name FROM tournaments");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>เพิ่มทีม</title>
</head>
<body>
    <h2>เพิ่มทีมเข้าแข่งขัน</h2>
    <?php if (!empty($message)) echo "<p><strong>$message</strong></p>"; ?>

    <form method="post">
        <label>ชื่อทีม:</label><br>
        <input type="text" name="team_name" required><br><br>

        <label>เลือกทัวร์นาเมนต์:</label><br>
        <select name="tournament_id" required>
            <option value="">-- กรุณาเลือก --</option>
            <?php while ($row = $tournaments->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['tournament_name']) ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit">บันทึกทีม</button>
    </form>

    <br>
    <a href="view_teams.php">→ ดูทีมทั้งหมด</a>
</body>
</html>
