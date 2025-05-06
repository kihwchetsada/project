<?php
header('Content-Type: application/json');

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว']);
    exit;
}

// ดึงข้อมูลการตั้งค่า Challonge API
$config_sql = "SELECT * FROM challonge_config LIMIT 1";
$config_result = $conn->query($config_sql);

if ($config_result && $config_result->num_rows > 0) {
    $config = $config_result->fetch_assoc();
    $api_key = $config['api_key'];
    $tournament_url = $config['tournament_url'];
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่พบการตั้งค่า Challonge API กรุณาตั้งค่าก่อนใช้งาน']);
    exit;
}

// ดึงชื่อทีมจากฐานข้อมูล
$sql = "SELECT team_name FROM teams";
$result = $conn->query($sql);

$team_names = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $team_names[] = $row['team_name'];
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลทีมในฐานข้อมูล']);
    exit;
}

// เพิ่มทีมเข้าทัวร์นาเมนต์
$results = [];

foreach ($team_names as $team) {
    $url = "https://api.challonge.com/v1/tournaments/{$tournament_url}/participants.json";

    $data = [
        "api_key" => $api_key,
        "participant" => [
            "name" => $team
        ]
    ];

    $options = [
        "http" => [
            "header"  => "Content-type: application/json",
            "method"  => "POST",
            "content" => json_encode($data),
            "ignore_errors" => true
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        $results[] = [
            'team' => $team,
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อ'
        ];
        continue;
    }
    
    // ตรวจสอบ HTTP response code
    $status_line = $http_response_header[0];
    preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
    $status = $match[1];
    
    if ($status === '200' || $status === '201') {
        $response_data = json_decode($response, true);
        if (isset($response_data['participant']) && isset($response_data['participant']['id'])) {
            $results[] = [
                'team' => $team,
                'success' => true,
                'message' => 'เพิ่มทีมสำเร็จ',
                'participant_id' => $response_data['participant']['id']
            ];
        } else {
            $results[] = [
                'team' => $team,
                'success' => false,
                'message' => 'ข้อมูลตอบกลับไม่ถูกต้อง'
            ];
        }
    } else {
        $response_data = json_decode($response, true);
        $error_message = isset($response_data['errors']) ? implode(', ', $response_data['errors']) : 'ไม่ทราบสาเหตุ';
        
        $results[] = [
            'team' => $team,
            'success' => false,
            'message' => "ไม่สามารถเพิ่มทีม: " . $error_message
        ];
    }
}

// ส่งผลลัพธ์กลับไป
echo json_encode([
    'success' => true,
    'message' => 'ดำเนินการเสร็จสิ้น',
    'results' => $results
]);
?>