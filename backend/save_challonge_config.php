<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// รับข้อมูลจากแบบฟอร์ม
$api_key = isset($_POST['api_key']) ? $_POST['api_key'] : '';

// ตรวจสอบข้อมูล
if (empty($api_key)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้']);
    exit;
}

// ตรวจสอบว่ามีตารางหรือไม่ ถ้าไม่มีให้สร้าง
$check_table = "SHOW TABLES LIKE 'challonge_config'";
$result = $conn->query($check_table);

if ($result->num_rows == 0) {
    // สร้างตาราง
    $create_table = "CREATE TABLE challonge_config (
        id INT(11) PRIMARY KEY AUTO_INCREMENT,
        api_key VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    if (!$conn->query($create_table)) {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถสร้างตารางในฐานข้อมูลได้']);
        exit;
    }
}

// ตรวจสอบว่ามีข้อมูลในตารางหรือไม่
$check_data = "SELECT * FROM challonge_config LIMIT 1";
$result = $conn->query($check_data);

if ($result->num_rows > 0) {
    // อัปเดตข้อมูลที่มีอยู่
    $sql = "UPDATE challonge_config SET api_key = ? WHERE id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $api_key);
} else {
    // เพิ่มข้อมูลใหม่
    $sql = "INSERT INTO challonge_config (id, api_key) VALUES (1, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $api_key);
}

// ดำเนินการบันทึกข้อมูล
if ($stmt->execute()) {
    header("Location: challonge_config.php?success=1");
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>