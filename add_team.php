<?php
// เปิด error
error_reporting(E_ALL);
ini_set('display_errors', 1);
// เชื่อมต่อฐานข้อมูล
require_once 'db_connect.php';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$messageType = "";

// ดึงข้อมูลทัวร์นาเมนต์
$tournaments_result = $conn->query("SELECT id, tournament_name FROM tournaments ORDER BY tournament_name");
$tournaments = [];
if ($tournaments_result && $tournaments_result->num_rows > 0) {
    $tournaments = $tournaments_result->fetch_all(MYSQLI_ASSOC);
}

// นับจำนวนทัวร์นาเมนต์
$total_tournaments = count($tournaments);

// ประมวลผลเพิ่มทีม
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $team_name = trim($_POST['team_name'] ?? '');
    $tournament_id = (int)($_POST['tournament_id'] ?? 0);

    // เงื่อนไขเหลือแค่ชื่อทีมและทัวร์นาเมนต์
    if (!empty($team_name) && $tournament_id > 0) {

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

            // แก้ไขคำสั่ง INSERT และ bind_param ให้ไม่มี competition_type_id
            $stmt = $conn->prepare("INSERT INTO teams (team_name, tournament_id, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("si", $team_name, $tournament_id);

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
            <?php if (!empty($message)) : ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
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
                    <label for="tournament_id"><i class="fas fa-trophy"></i> เลือกทัวร์นาเมนต์</label>
                    <div class="input-wrapper">
                        <select name="tournament_id" id="tournament_id" required>
                            <option value="">-- กรุณาเลือกทัวร์นาเมนต์ --</option>
                            <?php if (!empty($tournaments)) : ?>
                                <?php foreach ($tournaments as $row) : ?>
                                    <option value="<?= (int)$row['id'] ?>">
                                        <?= htmlspecialchars($row['tournament_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else : ?>
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