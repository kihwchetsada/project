<?php
// เปิด error
error_reporting(E_ALL); 
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$messageType = "";

// ดึงประเภทการแข่งขัน (สมมุติว่ามีตาราง competition_types)
$competition_types = $conn->query("SELECT id, competition_type FROM competition_types ORDER BY competition_type");

// ดึงทัวร์นาเมนต์
$tournaments = $conn->query("SELECT tournaments_id, tournament_name FROM tournaments ORDER BY tournament_name");

// นับจำนวนทัวร์นาเมนต์
$total_tournaments = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM tournaments");
if ($result) {
    $row = $result->fetch_assoc();
    $total_tournaments = (int)$row['count'];
}

// ประมวลผลเพิ่มทีม
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $team_name = trim($_POST['team_name'] ?? '');
    $competition_type_id = (int)($_POST['competition_type'] ?? 0);
    $tournament_id = (int)($_POST['tournament_id'] ?? 0);

    if (!empty($team_name) && $competition_type_id > 0 && $tournament_id > 0) {

        // ตรวจสอบชื่อทีมซ้ำ
        $stmt = $conn->prepare("SELECT team_id FROM teams WHERE team_name = ? AND tournament_id = ?");
        $stmt->bind_param("si", $team_name, $tournament_id);
        $stmt->execute();
        $check_result = $stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "ชื่อทีมนี้มีอยู่ในทัวร์นาเมนต์แล้ว";
            $messageType = "error";
        } else {
            $stmt->close();

            // เพิ่มทีมใหม่ (สมมุติว่ามีคอลัมน์ competition_type_id ใน teams)
            $stmt = $conn->prepare("INSERT INTO teams (team_name, competition_type_id, tournament_id, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sii", $team_name, $competition_type_id, $tournament_id);

            if ($stmt->execute()) {
                $message = "เพิ่มทีมสำเร็จ!";
                $messageType = "success";
            } else {
                $message = "เกิดข้อผิดพลาด: " . $stmt->error;
                $messageType = "error";
            }
        }
        $stmt->close();
    } else {
        $message = "กรุณากรอกข้อมูลให้ครบถ้วน";
        $messageType = "error";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>เพิ่มทีมเข้าทัวร์นาเมนต์</title>
<link rel="icon" type="image/png" href="img/logo.jpg">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="css/add_team.css">
</head>
<body>
<div class="container">
    <div class="header">
        <div class="icon">
            <i class="fas fa-users"></i>
        </div>
        <h1>เพิ่มทีมเข้าทัวร์นาเมนต์</h1>
        <div class="subtitle">กรอกข้อมูลทีมเพื่อเข้าร่วมการแข่งขัน</div>
    </div>

    <div class="content">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType==='success'?'check-circle':'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="stats-container">
            <div class="stats-item">
                <span class="stats-number"><?php echo $total_tournaments; ?></span>
                <span class="stats-label">ทัวร์นาเมนต์ทั้งหมด</span>
            </div>
        </div>

        <form method="post" id="teamForm">
            <div class="form-group">
                <label for="team_name"><i class="fas fa-flag"></i> ชื่อทีม</label>
                <div class="input-wrapper">
                    <input type="text" id="team_name" name="team_name" placeholder="กรอกชื่อทีม" required maxlength="100" autocomplete="off">
                    <i class="input-icon fas fa-edit"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="competition_type">
                    <i class="fas fa-gamepad"></i> ประเภทการแข่งขัน
                </label>
                <div class="input-wrapper">
                    <select name="competition_type" id="competition_type" required>
                        <option value="">-- เลือกประเภทการแข่งขัน --</option>
                        <?php if ($competition_types && $competition_types->num_rows > 0): ?>
                            <?php while ($row = $competition_types->fetch_assoc()): ?>
                                <option value="<?= (int)$row['id'] ?>">
                                    <?= htmlspecialchars($row['competition_type']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>ไม่มีประเภทการแข่งขัน</option>
                        <?php endif; ?>
                    </select>
                    <i class="input-icon fas fa-chevron-down"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="tournament_id"><i class="fas fa-trophy"></i> เลือกทัวร์นาเมนต์</label>
                <div class="input-wrapper">
                    <select name="tournament_id" id="tournament_id" required>
                        <option value="">-- กรุณาเลือกทัวร์นาเมนต์ --</option>
                        <?php if ($tournaments && $tournaments->num_rows > 0): ?>
                            <?php while ($row = $tournaments->fetch_assoc()): ?>
                                <option value="<?= (int)$row['tournaments_id'] ?>">
                                    <?= htmlspecialchars($row['tournament_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>ไม่มีทัวร์นาเมนต์</option>
                        <?php endif; ?>
                    </select>
                    <i class="input-icon fas fa-chevron-down"></i>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-plus-circle"></i> เพิ่มทีม
            </button>
        </form>

        <div class="navigation">
            <a href="view_teams.php" class="nav-link">
                <i class="fas fa-list"></i> ดูทีมทั้งหมด
            </a>
        </div>
    </div>
</div>
</body>
</html>
