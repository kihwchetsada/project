<?php
// ดึงชื่อทีมจากฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT team_name FROM teams";
$result = $conn->query($sql);

$team_names = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $team_names[] = $row['team_name'];
    }
}

$api_key = "xVshTcgkGt13jwhoJlhsOc83loNJFXFmg43uS1bI"; // ใส่ API key ของคุณที่ได้จากเว็บ Challonge
$tournament_url = "ROV_RMUTI5"; 

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
    $result = file_get_contents($url, false, $context);

    // แสดงผลตอบกลับ (เพื่อตรวจสอบว่าสำเร็จหรือไม่)
    echo "<pre>";
    print_r(json_decode($result, true));
    echo "</pre><hr>";
}
?>
