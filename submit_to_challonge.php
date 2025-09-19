<?php
error_reporting(E_ALL);
ini_set('display_errors', 1); 

if (!isset($_POST['tournament_id'])) {
    die("ไม่ได้เลือกทัวร์นาเมนต์");
}

require_once 'db_connect.php';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tournament_id = (int)$_POST['tournament_id'];

// ดึง tournament_url
$tournamentQuery = $conn->query("SELECT tournament_url FROM tournaments WHERE id = $tournament_id");
if ($tournamentQuery && $tournamentQuery->num_rows > 0) {
    $tournamentRow = $tournamentQuery->fetch_assoc();
    $tournament_url = $tournamentRow['tournament_url'];
} else {
    die("ไม่พบ tournament_url สำหรับ ID นี้");
}

// ดึงทีม
$teamQuery = $conn->query("SELECT team_name FROM teams WHERE tournament_id = $tournament_id");
$team_names = [];

if ($teamQuery && $teamQuery->num_rows > 0) {
    while ($row = $teamQuery->fetch_assoc()) {
        $team_names[] = $row['team_name'];
    }
} else {
    die("ไม่พบทีมในทัวร์นาเมนต์นี้");
}

$api = $conn->query("SELECT api_key FROM challong_config WHERE id = 1");
$api_key = $api->fetch_assoc()['api_key']; 
if (!$api_key) {
    die("ไม่พบ API Key");
}

// เพิ่มทีมเข้า Challonge
echo '<!DOCTYPE html>';
echo '<html lang="th">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>เพิ่มทีมเข้าการแข่งขัน</title>';
echo '<link rel="icon" type="image/x-icon" href="img/logo.jpg">';
echo '<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: "Noto Sans Thai", Arial, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
    }
    
    .container {
        max-width: 1000px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        backdrop-filter: blur(10px);
    }
    
    .header {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        padding: 30px;
        text-align: center;
        position: relative;
    }
    
    .header::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.1\"%3E%3Ccircle cx=\"30\" cy=\"30\" r=\"4\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
        opacity: 0.3;
    }
    
    .header h1 {
        font-size: 2.5rem;
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
    }
    
    .header p {
        font-size: 1.1rem;
        opacity: 0.9;
        position: relative;
        z-index: 1;
    }
    
    .content {
        padding: 30px;
    }
    
    .team-item {
        background: #fff;
        border: 2px solid #e3f2fd;
        border-radius: 15px;
        margin-bottom: 25px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }
    
    .team-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        border-color: #2196f3;
    }
    
    .team-header {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        padding: 20px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .team-name {
        font-size: 1.5rem;
        font-weight: bold;
        color: #1976d2;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .team-name::before {
        content: "🏆";
        font-size: 1.2em;
    }
    
    .response-container {
        padding: 20px;
        background: #fafafa;
        border-radius: 8px;
        margin: 15px 20px;
        font-family: "Courier New", monospace;
        font-size: 0.9rem;
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #e0e0e0;
    }
    
    .status {
        padding: 15px 20px;
        margin: 0 20px 20px;
        border-radius: 8px;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .status.success {
        background: #e8f5e8;
        color: #2e7d32;
        border: 1px solid #c8e6c8;
    }
    
    .status.success::before {
        content: "✅";
    }
    
    .status.error {
        background: #ffebee;
        color: #c62828;
        border: 1px solid #ffcdd2;
    }
    
    .status.error::before {
        content: "❌";
    }
    
    .loading {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        color: #666;
        font-style: italic;
    }
    
    .loading::before {
        content: "";
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid #e0e0e0;
        border-top: 2px solid #2196f3;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-right: 10px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .progress-bar {
        background: #e0e0e0;
        height: 8px;
        border-radius: 4px;
        margin: 20px 30px;
        overflow: hidden;
    }
    
    .progress-fill {
        background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s ease;
    }
    
    .controls {
        background: #f8f9fa;
        padding: 30px;
        border-top: 1px solid #e0e0e0;
        display: flex;
        justify-content: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 25px;
        font-size: 1rem;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(79, 172, 254, 0.6);
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }
    
    .countdown {
        background: #fff3cd;
        color: #856404;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 30px;
        text-align: center;
        border: 1px solid #ffeaa7;
        font-weight: bold;
    }
    
    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 20px 30px;
    }
    
    .stat-item {
        background: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        border: 2px solid #e3f2fd;
        transition: all 0.3s ease;
    }
    
    .stat-item:hover {
        border-color: #2196f3;
        transform: translateY(-2px);
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #2196f3;
        display: block;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
        margin-top: 5px;
    }
    
    @media (max-width: 768px) {
        .container {
            margin: 10px;
            border-radius: 15px;
        }
        
        .header h1 {
            font-size: 2rem;
        }
        
        .content {
            padding: 20px;
        }
        
        .controls {
            flex-direction: column;
            align-items: center;
        }
        
        .btn {
            width: 100%;
            max-width: 300px;
            justify-content: center;
        }
    }
</style>';
echo '</head>';
echo '<body>';
echo '<div class="container">';

// Header
echo '<div class="header">';
echo '<h1>🏆 เพิ่มทีมเข้าการแข่งขัน</h1>';
echo '<p>กำลังดำเนินการเพิ่มทีมทั้งหมดเข้าสู่ระบบการแข่งขัน</p>';
echo '</div>';

echo '<div class="content">';

// แสดงสถิติ
$total_teams = count($team_names);
echo '<div class="stats">';
echo '<div class="stat-item">';
echo '<span class="stat-number">' . $total_teams . '</span>';
echo '<div class="stat-label">ทีมทั้งหมด</div>';
echo '</div>';
echo '<div class="stat-item">';
echo '<span class="stat-number" id="processed-count">0</span>';
echo '<div class="stat-label">ดำเนินการแล้ว</div>';
echo '</div>';
echo '<div class="stat-item">';
echo '<span class="stat-number" id="success-count">0</span>';
echo '<div class="stat-label">สำเร็จ</div>';
echo '</div>';
echo '<div class="stat-item">';
echo '<span class="stat-number" id="error-count">0</span>';
echo '<div class="stat-label">ข้อผิดพลาด</div>';
echo '</div>';
echo '</div>';

// Progress bar
echo '<div class="progress-bar">';
echo '<div class="progress-fill" id="progress-fill" style="width: 0%"></div>';
echo '</div>';

$processed = 0;
$successful = 0;
$errors = 0;

// ✅ ตรวจสอบก่อนว่าทัวร์นาเมนต์เข้าถึงได้จริง
$test_url = "https://api.challonge.com/v1/tournaments/{$tournament_url}.json?api_key={$api_key}";
$ch = curl_init($test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ปิด SSL verify สำหรับ localhost
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code >= 200 && $http_code < 300) {
    $t_data = json_decode($response, true);
    echo '<div class="status success">✅ ตรวจสอบแล้ว: เจอทัวร์นาเมนต์ <strong>' 
        . htmlspecialchars($t_data['tournament']['name']) . '</strong></div>';
} else {
    echo '<div class="status error">❌ ไม่สามารถเข้าถึงทัวร์นาเมนต์: ' 
        . htmlspecialchars($tournament_url) . '</div>';
    echo '<div class="response-container">';
    echo '<strong>HTTP Code:</strong> ' . $http_code . '<br>';
    echo '<strong>Response:</strong> ' . htmlspecialchars($response);
    echo '</div>';
    exit;
}

// ✅ เริ่มเพิ่มทีม
foreach ($team_names as $index => $team) {
    $processed++;
    $progress_percent = ($processed / $total_teams) * 100;
    
    echo '<div class="team-item">';
    echo '<div class="team-header">';
    echo '<h3 class="team-name">' . htmlspecialchars($team) . '</h3>';
    echo '</div>';
    
    echo '<div class="loading">กำลังเพิ่มทีม...</div>';
    if (ob_get_level()) ob_flush();
    flush();
    
    // URL API เพิ่มทีม
    $url = "https://api.challonge.com/v1/tournaments/{$tournament_url}/participants.json?api_key={$api_key}";
    $data = ["participant" => ["name" => $team]];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $errors++;
        echo '<div class="status error">cURL Error: ' . curl_error($ch) . '</div>';
    } else {
        if ($http_code >= 200 && $http_code < 300) {
            $successful++;
            echo '<div class="status success">เพิ่มทีม ' . htmlspecialchars($team) . ' สำเร็จ!</div>';
            echo '<div class="response-container">';
            $response_data = json_decode($response, true);
            if ($response_data) {
                echo '<strong>ข้อมูลการตอบกลับ:</strong><br>';
                echo htmlspecialchars(json_encode($response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            echo '</div>';
        } else {
            $errors++;
            echo '<div class="status error">เกิดข้อผิดพลาดในการเพิ่มทีม: ' . htmlspecialchars($team) . '</div>';
            echo '<div class="response-container">';
            echo '<strong>HTTP Code:</strong> ' . $http_code . '<br>';
            echo '<strong>Response:</strong> ' . htmlspecialchars($response);
            echo '</div>';
        }
    }
    curl_close($ch);
    
    echo '</div>'; // team-item
    
    // อัพเดท progress bar
    echo '<script>
        document.getElementById("processed-count").textContent = "' . $processed . '";
        document.getElementById("success-count").textContent = "' . $successful . '";
        document.getElementById("error-count").textContent = "' . $errors . '";
        document.getElementById("progress-fill").style.width = "' . $progress_percent . '%";
    </script>';
    
    if (ob_get_level()) ob_flush();
    flush();
    
    if ($index < count($team_names) - 1) sleep(1);
}

echo '</div>'; // ปิด content

// แสดง countdown
echo '<div class="countdown" id="countdown">
    <strong>🕒 ระบบจะนำคุณกลับไปหน้าแดชบอร์ดใน <span id="countdown-timer">15</span> วินาที</strong>
</div>';

// Controls
echo '<div class="controls">';
echo '<form method="post" action="backend/admin_dashboard.php" style="display: inline;">';
echo '<button type="submit" class="btn btn-primary">🏠 กลับหน้าแดชบอร์ด</button>';
echo '</form>';

echo '</div>';

echo '</div>'; // ปิด container

// JavaScript สำหรับ countdown และ auto redirect
echo '<script>
let timeLeft = 15;
const countdownTimer = document.getElementById("countdown-timer");
const countdownDiv = document.getElementById("countdown");

const countdown = setInterval(function() {
    timeLeft--;
    countdownTimer.textContent = timeLeft;
    
    if (timeLeft <= 5) {
        countdownDiv.style.background = "#ffebee";
        countdownDiv.style.color = "#c62828";
        countdownDiv.style.border = "1px solid #ffcdd2";
    }
    
    if (timeLeft <= 0) {
        clearInterval(countdown);
        countdownDiv.innerHTML = "<strong>🔄 กำลังเปลี่ยนหน้า...</strong>";
        window.location.href = "backend/admin_dashboard.php";
    }
}, 1000);

// เพิ่มเอฟเฟกต์ smooth scroll เมื่อมีการเพิ่มทีมใหม่
function scrollToLatest() {
    const teamItems = document.querySelectorAll(".team-item");
    if (teamItems.length > 0) {
        teamItems[teamItems.length - 1].scrollIntoView({ behavior: "smooth", block: "center" });
    }
}

// เรียกใช้ทุกครั้งที่มีการเพิ่มทีม
document.addEventListener("DOMContentLoaded", function() {
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === "childList") {
                scrollToLatest();
            }
        });
    });
    
    observer.observe(document.querySelector(".content"), { childList: true, subtree: true });
});

// เพิ่มเอฟเฟกต์ confetti เมื่อเสร็จสิ้น
if (' . $processed . ' === ' . $total_teams . ') {
    setTimeout(function() {
        // Simple confetti effect
        for (let i = 0; i < 50; i++) {
            createConfetti();
        }
    }, 1000);
}

function createConfetti() {
    const confetti = document.createElement("div");
    confetti.style.position = "fixed";
    confetti.style.left = Math.random() * 100 + "vw";
    confetti.style.top = "-10px";
    confetti.style.width = "10px";
    confetti.style.height = "10px";
    confetti.style.backgroundColor = ["#ff6b6b", "#4ecdc4", "#45b7d1", "#f9ca24", "#f0932b", "#eb4d4b", "#6c5ce7"][Math.floor(Math.random() * 7)];
    confetti.style.pointerEvents = "none";
    confetti.style.borderRadius = "50%";
    confetti.style.zIndex = "9999";
    confetti.style.animation = "fall 3s linear forwards";
    
    document.body.appendChild(confetti);
    
    setTimeout(() => {
        confetti.remove();
    }, 3000);
}

// CSS animation สำหรับ confetti
const style = document.createElement("style");
style.textContent = `
    @keyframes fall {
        to {
            transform: translateY(100vh) rotate(360deg);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
</script>';

echo '</body>';
echo '</html>';

?>
