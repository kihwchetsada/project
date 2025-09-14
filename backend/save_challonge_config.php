<?php
// save_challonge_config.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../db_connect.php'; 

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api_key = $_POST['api_key'] ?? '';

    if (empty($api_key)) {
        header('Location: challonge_config.php?status=error&message=API_Key_is_required');
        exit;
    }

    // --- ส่วนที่แก้ไข ---

    // 1. ตรวจสอบว่ามีข้อมูล config ที่ id = 1 อยู่แล้วหรือไม่ (เจาะจง id = 1)
    $check_sql = "SELECT id FROM challong_config WHERE id = 1";
    $result = $conn->query($check_sql);

    if ($result && $result->num_rows > 0) {
        // 2. ถ้ามี id = 1 อยู่แล้ว -> ใช้คำสั่ง UPDATE ที่ id = 1 เท่านั้น
        $stmt = $conn->prepare("UPDATE challong_config SET api_key = ?, updated_at = NOW() WHERE id = 1");
        $stmt->bind_param("s", $api_key);

    } else {
        // 3. ถ้ายังไม่มีข้อมูล id = 1 -> ใช้คำสั่ง INSERT โดยบังคับ id = 1
        $stmt = $conn->prepare("INSERT INTO challong_config (id, api_key, created_at, updated_at) VALUES (1, ?, NOW(), NOW())");
        $stmt->bind_param("s", $api_key);
    }

    // --- จบส่วนที่แก้ไข ---

    if ($stmt->execute()) {
        header('Location: challonge_config.php?status=success');
    } else {
        // หากเกิด error ที่เกี่ยวกับการ INSERT id ซ้ำ (Duplicate entry '1' for key 'PRIMARY')
        // ให้แจ้งผู้ใช้หรือจัดการเป็นพิเศษ
        if ($conn->errno == 1062) {
            header('Location: challonge_config.php?status=error&message=Duplicate_ID_error');
        } else {
             header('Location: challonge_config.php?status=error&message=' . urlencode($stmt->error));
        }
    }
    
    $stmt->close();
    $conn->close();
    exit;

} else {
    header('Location: challonge_config.php');
    exit;
}
?>