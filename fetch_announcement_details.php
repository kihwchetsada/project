<?php
// ไฟล์: fetch_announcement_details.php

// -----------------------------------------------------
// VVV โค้ดสำหรับเปิดการแสดงผลข้อผิดพลาดของ PHP VVV
error_reporting(E_ALL);
ini_set('display_errors', 1);
// -----------------------------------------------------

// กำหนด Content-Type เป็น JSON 
header('Content-Type: application/json');

// เชื่่อมต่อฐานข้อมูล 
// ตรวจสอบให้แน่ใจว่าไฟล์นี้อยู่ถูก Path และไม่มี error ในตัวมันเอง
require 'db_connect.php'; 

// ตรวจสอบ ID ที่ส่งมา
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid announcement ID']);
    exit;
}

$announcement_id = (int)$_GET['id'];

// เชื่อมต่อ DB
// สมมติว่าตัวแปร DB ถูกกำหนดใน db_connect.php
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    // ส่ง error กลับไปในกรณีที่เชื่อมต่อฐานข้อมูลล้มเหลว
    http_response_code(500);
    echo json_encode(['error' => 'Database Connection failed: ' . $conn->connect_error]);
    exit;
}

// คำสั่ง SQL: ต้องแน่ใจว่า 'image_path' คือชื่อคอลัมน์ที่ถูกต้อง
$stmt = $conn->prepare("SELECT id, title, category, priority, status, created_at, description, image_path AS image_url FROM announcements WHERE id = ?");

// **ตรวจสอบว่า SQL ถูกต้อง** หากชื่อคอลัมน์ผิด prepare() จะคืนค่าเป็น false
if (!$stmt) {
    http_response_code(500); 
    echo json_encode(['error' => 'SQL Prepare failed: ' . $conn->error . ' (Check your column names like image_path)']);
    exit;
}


$stmt->bind_param("i", $announcement_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // จัดรูปแบบวันที่ให้เป็นที่อ่านได้
    $row['created_at'] = date('d/m/Y H:i', strtotime($row['created_at']));
    
    // ส่งข้อมูลเป็น JSON กลับไป
    echo json_encode($row);
} else {
    // ไม่พบประกาศ
    echo json_encode(['error' => 'Announcement not found.']);
}

$stmt->close();
$conn->close();
?>