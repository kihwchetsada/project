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

// นับจำนวนทัวร์นาเมนต์
$total_tournaments = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM tournaments");
if ($result) {
    $row = $result->fetch_assoc();
    $total_tournaments = (int)$row['count'];
}

// ประมวลผลเพิ่มทัวร์นาเมนต์
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tournament_name = trim($_POST['tournament_name'] ?? '');
    $tournament_url = trim($_POST['tournament_url'] ?? '');

    if (!empty($tournament_name)) {

        // ตรวจสอบชื่อทัวร์นาเมนต์ซ้ำ
        $stmt = $conn->prepare("SELECT id FROM tournaments WHERE tournament_name = ?");
        $stmt->bind_param("s", $tournament_name);
        $stmt->execute();
        $check_result = $stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "ชื่อนี้มีอยู่แล้วในระบบ";
            $messageType = "error";
        } else {
            $stmt->close();

            // บันทึกข้อมูล
            $stmt = $conn->prepare("INSERT INTO tournaments (tournament_name, tournament_url) VALUES (?, ?)");
            $stmt->bind_param("ss", $tournament_name, $tournament_url);

            if ($stmt->execute()) {
                $message = "เพิ่มทัวร์นาเมนต์สำเร็จ!";
                $messageType = "success";
            } else {
                $message = "เกิดข้อผิดพลาด: " . $stmt->error;
                $messageType = "error";
            }
        }
        $stmt->close();
    } else {
        $message = "กรุณากรอกชื่อทัวร์นาเมนต์";
        $messageType = "error";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>เพิ่มทัวร์นาเมนต์</title>
<link rel="icon" type="image/png" href="img/logo.jpg">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="css/add_tournament.css">
</head>
<body>
<div class="container">
    <div class="header">
        <div class="icon">
            <i class="fas fa-trophy"></i>
        </div>
        <h1>เพิ่มทัวร์นาเมนต์</h1>
        <div class="subtitle">สร้างทัวร์นาเมนต์ใหม่สำหรับการแข่งขัน</div>
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

        <form method="post">
            <div class="form-group">
                <label><i class="fas fa-flag"></i> ชื่อทัวร์นาเมนต์</label>
                <div class="input-wrapper">
                    <input type="text" name="tournament_name" placeholder="กรอกชื่อทัวร์นาเมนต์" required maxlength="100">
                    <i class="input-icon fas fa-edit"></i>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-link"></i> URL Challonge ( เช่น https://challonge.com/rmuti )</label>
                <div class="input-wrapper">
                    <input type="text" name="tournament_url" placeholder="rmuti">
                    <i class="input-icon fas fa-link"></i>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-plus-circle"></i> เพิ่มทัวร์นาเมนต์
            </button>
        </form>

        <div class="navigation">
            <a href="view_teams.php" class="nav-link">
                <i class="fas fa-list"></i> ดูทัวร์นาเมนต์ทั้งหมด
            </a>
        </div>
    </div>
</div>
</body>
</html>
