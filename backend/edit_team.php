<?php
require '../db_connect.php';

if (isset($_GET['team_id']) && is_numeric($_GET['team_id'])) {
    $team_id = intval($_GET['team_id']);
    
    // ดึงข้อมูลทีม
    $stmt = $conn->prepare("SELECT * FROM teams WHERE team_id = :team_id");
    $stmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
    $stmt->execute();
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$team) {
        die("ไม่พบทีมนี้");
    }
    
    // อัปเดตถ้ามีการกดบันทึก
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $team_name = $_POST['team_name'];
        $coach_name = $_POST['coach_name'];
        $coach_phone = $_POST['coach_phone'];
        
        $stmt = $conn->prepare("UPDATE teams SET team_name=:team_name, coach_name=:coach_name, coach_phone=:coach_phone WHERE team_id=:team_id");
        $stmt->execute([
            ':team_name' => $team_name,
            ':coach_name' => $coach_name,
            ':coach_phone' => $coach_phone,
            ':team_id' => $team_id
        ]);
        
        header("Location: manage_teams.php?success=1");
        exit;
    }
} else {
    die("ไม่มี team_id");
}
?>

<form method="post">
    <label>ชื่อทีม</label>
    <input type="text" name="team_name" value="<?php echo htmlspecialchars($team['team_name']); ?>" required>
    
    <label>ชื่อโค้ช</label>
    <input type="text" name="coach_name" value="<?php echo htmlspecialchars($team['coach_name']); ?>">
    
    <label>เบอร์โทรโค้ช</label>
    <input type="text" name="coach_phone" value="<?php echo htmlspecialchars($team['coach_phone']); ?>">
    
    <button type="submit">บันทึก</button>
</form>
