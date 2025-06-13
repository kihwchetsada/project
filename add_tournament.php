<?php
// เปิด error เพื่อดูปัญหา
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบว่ามีการส่งฟอร์มหรือยัง
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn = new mysqli("localhost", "root", "", "tournament_registration");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $name = $conn->real_escape_string($_POST['tournament_name']);
    $url = $conn->real_escape_string($_POST['tournament_url']);

    if (!empty($name) && !empty($url)) {
        $sql = "INSERT INTO tournaments (tournament_name, tournament_url) VALUES ('$name', '$url')";
        if ($conn->query($sql) === TRUE) {
            $message = "เพิ่มทัวร์นาเมนต์สำเร็จ!";
        } else {
            $message = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    } else {
        $message = "กรุณากรอกข้อมูลให้ครบ";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>เพิ่มทัวร์นาเมนต์</title>
</head>
<body>
    <h2>เพิ่มรายการทัวร์นาเมนต์ใหม่</h2>
    <?php if (!empty($message)) echo "<p><strong>$message</strong></p>"; ?>

    <form method="post">
        <label for="tournament_name">ชื่อทัวร์นาเมนต์:</label><br>
        <input type="text" name="tournament_name" id="tournament_name" required><br><br>

        <label for="tournament_url">URL ของ Challonge:</label><br>
        <input type="text" name="tournament_url" id="tournament_url" required>
        <small>ตัวอย่าง: ถ้า URL คือ https://challonge.com/ROV_RMUTI5 ให้ใส่ <code>ROV_RMUTI5</code></small><br><br>

        <button type="submit">บันทึก</button>
    </form>

    <br>
    <a href="select_tournament.php">← กลับไปหน้าเลือกทัวร์นาเมนต์</a>
</body>
</html>
