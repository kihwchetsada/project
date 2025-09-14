<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// เชื่อมต่อฐานข้อมูล
require '../db_connect.php';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset("utf8");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตรวจสอบว่าข้อมูลถูกส่งมาแบบ POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // รับค่าจากฟอร์ม
    $id = $_POST['id'] ?? 1; // บังคับเป็น id = 1
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $is_open = isset($_POST['is_open']) ? 1 : 0; 
    
    // ตรวจสอบว่ามีข้อมูลวันที่ส่งมาหรือไม่
    if (empty($start_date) || empty($end_date)) {
        header('Location: competition.php?error=Date_is_required');
        exit;
    }

    // ตรวจสอบว่ามีข้อมูล ID=1 อยู่ในตารางแล้วหรือยัง
    $check_stmt = $conn->prepare("SELECT id FROM competitions WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $check_stmt->close();

    if ($result->num_rows > 0) {
        // ถ้ามีข้อมูลอยู่แล้ว -> ใช้คำสั่ง UPDATE (ส่วนนี้ถูกต้องอยู่แล้ว)
        $stmt = $conn->prepare("UPDATE competitions SET start_date = ?, end_date = ?, is_open = ? WHERE id = ?");
        $stmt->bind_param("ssii", $start_date, $end_date, $is_open, $id);
    } else {
        // --- ส่วนที่แก้ไข ---
        // ถ้ายังไม่มีข้อมูล -> ใช้คำสั่ง INSERT โดยเพิ่ม tournament_id เข้าไป
        
        // กำหนดค่า tournament_id ที่จะเพิ่มเข้าไป (ในที่นี้กำหนดเป็น 1)
        $tournament_id_to_insert = 1;

        $stmt = $conn->prepare("INSERT INTO competitions (id, tournament_id, start_date, end_date, is_open) VALUES (?, ?, ?, ?, ?)");
        // "iissi" หมายถึง (integer, integer, string, string, integer)
        $stmt->bind_param("iissi", $id, $tournament_id_to_insert, $start_date, $end_date, $is_open);
    }

    // ทำการ execute และตรวจสอบผลลัพธ์
    if ($stmt->execute()) {
        header('Location: competition.php?success=1');
    } else {
        header('Location: competition.php?error=1');
    }

    $stmt->close();
    $conn->close();
    exit;
}

// ถ้าไม่ได้เข้ามาหน้านี้ด้วย POST ให้ส่งกลับไปหน้าฟอร์ม
header('Location: competition.php');
exit;

?>