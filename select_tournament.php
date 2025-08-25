<?php
// เปิด error ให้แสดงบนหน้าจอ
error_reporting(E_ALL);
ini_set('display_errors', 1);

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// แก้ไขตรงนี้: เปลี่ยน tm.id → tm.team_id
$tournaments = $conn->query("
    SELECT t.id, t.tournament_name, COUNT(tm.team_id) as team_count 
    FROM tournaments t 
    LEFT JOIN teams tm ON t.id = tm.tournament_id 
    GROUP BY t.id, t.tournament_name 
    ORDER BY t.tournament_name
");

if (!$tournaments) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลทัวร์นาเมนต์: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challonge Tournament Deployment</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/select_tournament.css">
    <link rel="icon" type="image/png" href="img/logo.jpg">
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="fas fa-trophy"></i>
            <h1>Tournament Deployment</h1>
            <p class="subtitle">เลือกทัวร์นาเมนต์เพื่อ Deploy ไปยัง Challonge</p>
        </div>

        <div class="info-text">
            <i class="fas fa-info-circle"></i>
            กรุณาเลือกทัวร์นาเมนต์ที่ต้องการส่งไปยังแพลตฟอร์ม Challonge เพื่อจัดการการแข่งขัน
        </div>

        <form id="tournamentForm" action="submit_to_challonge.php" method="post">
            <div class="form-group">
                <label for="tournament_id">
                    <i class="fas fa-gamepad"></i> เลือกทัวร์นาเมนต์:
                </label>
                <select name="tournament_id" id="tournament_id" required>
                    <option value="">-- กรุณาเลือกทัวร์นาเมนต์ --</option>
                    <?php while ($row = $tournaments->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>">
                            <?= htmlspecialchars($row['tournament_name']) ?> 
                            (<?= $row['team_count'] ?> ทีม)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-rocket"></i>
                Deploy to Challonge
            </button>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>กำลังประมวลผล...</p>
            </div>
        </form>
    </div>

    <script>
        // เพิ่ม JavaScript สำหรับ UX ที่ดีขึ้น
        document.getElementById('tournamentForm').addEventListener('submit', function(e) {
            const select = document.getElementById('tournament_id');
            if (!select.value) {
                e.preventDefault();
                alert('กรุณาเลือกทัวร์นาเมนต์ก่อนทำการ Deploy');
                return;
            }
            
            // แสดง loading
            document.getElementById('loading').style.display = 'block';
            document.querySelector('.submit-btn').style.display = 'none';
        });

        // เพิ่มเอฟเฟกต์เมื่อเลือก dropdown
        document.getElementById('tournament_id').addEventListener('change', function() {
            if (this.value) {
                this.style.borderColor = '#28a745';
                this.style.backgroundColor = '#f8f9fa';
            } else {
                this.style.borderColor = '#e1e5e9';
                this.style.backgroundColor = 'white';
            }
        });
    </script>
</body>
</html>