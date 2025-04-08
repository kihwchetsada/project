<?php

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli('localhost', 'root', '', 'competition_system');
$conn->set_charset("utf8");
// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ดึงข้อมูลการแข่งขัน
$result = $conn->query("SELECT * FROM competitions_1 WHERE id = 1");
$competitions_1 = $result ? $result->fetch_assoc() : null;

// ตรวจสอบข้อมูล
if (!$competitions_1) {
    $competitions_1 = [
        'start_date' => '',
        'end_date' => '',
        'is_open' => 0,
        'id' => 1,
    ];
    echo "<p style='color: red;'>ไม่มีข้อมูลการแข่งขันในฐานข้อมูล!</p>";
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการการเปิดรับสมัคร</title>
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link rel="stylesheet" href="../css/r.css">
</head>
<body>
    <h1>จัดการการเปิดรับสมัคร</h1>
    <form method="POST" action="update_competition.php">
        <label for="start_date">วันที่เริ่มรับสมัคร:</label>
        <input type="date" id="start_date" name="start_date" value="<?= $competitions_1['start_date'] ?>" required><br>

        <label for="end_date">วันที่สิ้นสุดการรับสมัคร:</label>
        <input type="date" id="end_date" name="end_date" value="<?= $competitions_1['end_date'] ?>" required><br>

        <label for="is_open">เปิดรับสมัคร:</label>
        <input type="checkbox" id="is_open" name="is_open" <?= $competitions_1['is_open'] ? 'checked' : '' ?>><br>

        <input type="hidden" name="id" value="<?= $competitions_1['id'] ?>">
        <button type="submit">บันทึก</button>
    </form>
</body>
<script>
document.getElementById('end_date').addEventListener('change', function() {
    var startDate = document.getElementById('start_date').value;
    var endDate = this.value;
    
    if(startDate && endDate < startDate) {
        alert('วันที่สิ้นสุดต้องมากกว่าวันที่เริ่ม');
        this.value = '';
    }
});
</script>
</html>
