<?php
$conn = new mysqli('localhost', 'root', '', 'competition_system');
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$id = $_POST['id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$is_open = isset($_POST['is_open']) ? 1 : 0;

$sql = "UPDATE competitions_1 SET 
        start_date = ?, 
        end_date = ?, 
        is_open = ? 
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $start_date, $end_date, $is_open, $id);

if ($stmt->execute()) {
    echo "<script>
            alert('บันทึกข้อมูลสำเร็จ');
            window.location.href = 'competition.php';
          </script>";
} else {
    echo "<script>
            alert('เกิดข้อผิดพลาด: " . $conn->error . "');
            window.location.href = 'competition.php';
          </script>";
}

$stmt->close();
$conn->close();
?>