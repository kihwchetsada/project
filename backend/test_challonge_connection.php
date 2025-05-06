<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// รับข้อมูลจากแบบฟอร์ม
$api_key = isset($_POST['api_key']) ? $_POST['api_key'] : '';
$tournament_url = isset($_POST['tournament_url']) ? $_POST['tournament_url'] : '';

// ตรวจสอบข้อมูล
if (empty($api_key) || empty($tournament_url)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit;
}

// สร้าง URL สำหรับการทดสอบการเชื่อมต่อ (ดึงข้อมูลทัวร์นาเมนต์)
$url = "https://api.challonge.com/v1/tournaments/{$tournament_url}.json?api_key={$api_key}";

// ตั้งค่า options สำหรับการเชื่อมต่อ
$options = [
    "http" => [
        "header" => "Content-type: application/json",
        "method" => "GET"
    ]
];

// สร้าง context และทำการเชื่อมต่อ
$context = stream_context_create($options);

// ดักจับข้อผิดพลาดที่อาจเกิดขึ้น
try {
    $result = @file_get_contents($url, false, $context);
    
    // ตรวจสอบว่าการเชื่อมต่อสำเร็จหรือไม่
    if ($result === false) {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อกับ Challonge API ได้']);
        exit;
    }
    
    // แปลงข้อมูลที่ได้รับเป็น JSON
    $tournament_data = json_decode($result, true);
    
    // ตรวจสอบว่าข้อมูลถูกต้องหรือไม่
    if (isset($tournament_data['tournament']) && isset($tournament_data['tournament']['name'])) {
        echo json_encode([
            'success' => true, 
            'message' => 'เชื่อมต่อสำเร็จ', 
            'tournament_name' => $tournament_data['tournament']['name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลทัวร์นาเมนต์']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>