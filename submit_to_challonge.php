<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบว่ามี POST มาจากฟอร์มหรือไม่
if (!isset($_POST['tournament_id'])) {
    die("ไม่ได้เลือกทัวร์นาเมนต์");
}

$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tournament_id = (int)$_POST['tournament_id'];

// ดึงข้อมูล tournament_url จาก tournaments
$tournamentQuery = $conn->query("SELECT tournament_url FROM tournaments WHERE id = $tournament_id");
if ($tournamentQuery && $tournamentQuery->num_rows > 0) {
    $tournamentRow = $tournamentQuery->fetch_assoc();
    $tournament_url = $tournamentRow['tournament_url'];
} else {
    die("ไม่พบ tournament_url สำหรับ ID นี้");
}

// ดึงชื่อทีมทั้งหมดที่อยู่ใน tournament_id นี้
$teamQuery = $conn->query("
    SELECT team_name 
    FROM teams 
    WHERE tournament_id = $tournament_id
");

$team_names = [];

if ($teamQuery && $teamQuery->num_rows > 0) {
    while ($row = $teamQuery->fetch_assoc()) {
        $team_names[] = $row['team_name'];
    }
} else {
    die("ไม่พบทีมในทัวร์นาเมนต์นี้");
}

// API Key จาก Challonge
$api_key = "xVshTcgkGt13jwhoJlhsOc83loNJFXFmg43uS1bI";

// วนลูปส่งแต่ละทีมเข้า Challonge
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
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    // แสดงผลลัพธ์จาก API
    echo "<h3>เพิ่มทีม: <strong>" . htmlspecialchars($team) . "</strong></h3>";
    echo "<pre>";
    print_r(json_decode($response, true));
    echo "</pre><hr>";
}
?>
