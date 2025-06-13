<?php
$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "
SELECT t.team_name, tm.tournament_name
FROM teams t
JOIN tournaments tm ON t.tournament_id = tm.id
ORDER BY tm.tournament_name, t.team_name
";

$result = $conn->query($sql);

$teams_by_tournament = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $teams_by_tournament[$row['tournament_name']][] = $row['team_name'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>รายชื่อทีม</title>
</head>
<body>
    <h2>รายชื่อทีมทั้งหมด (แยกตามทัวร์นาเมนต์)</h2>
    <a href="add_team.php">← เพิ่มทีมใหม่</a> | 
    <a href="select_tournament.php">→ ส่งทีมเข้า Challonge</a>
    <hr>

    <?php if (empty($teams_by_tournament)): ?>
        <p>ยังไม่มีทีม</p>
    <?php else: ?>
        <?php foreach ($teams_by_tournament as $tournament => $teams): ?>
            <h3><?= htmlspecialchars($tournament) ?></h3>
            <ul>
                <?php foreach ($teams as $team): ?>
                    <li><?= htmlspecialchars($team) ?></li>
                <?php endforeach; ?>
            </ul>
            <hr>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
