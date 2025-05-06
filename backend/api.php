<?php
// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ดึงข้อมูลการตั้งค่า Challonge API
$config_sql = "SELECT * FROM challonge_config LIMIT 1";
$config_result = $conn->query($config_sql);

if ($config_result && $config_result->num_rows > 0) {
    $config = $config_result->fetch_assoc();
    $api_key = $config['api_key'];
    $tournament_url = $config['tournament_url'];
} else {
    die("ไม่พบการตั้งค่า Challonge API กรุณาตั้งค่าก่อนใช้งาน");
}

// ดึงชื่อทีมจากฐานข้อมูล
$sql = "SELECT team_name FROM teams";
$result = $conn->query($sql);

$team_names = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $team_names[] = $row['team_name'];
    }
}

// ส่วนแสดงผล
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มทีมเข้าสู่ทัวร์นาเมนต์ Challonge</title>
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #3a00b0;
            text-align: center;
            margin-bottom: 30px;
        }
        .info-box {
            background-color: #e9f5ff;
            border: 1px solid #b3d7ff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .team-list {
            margin: 20px 0;
        }
        .team-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .team-item:last-child {
            border-bottom: none;
        }
        .btn {
            background-color: #3a00b0;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: inline-block;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #2a0080;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .result-area {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .success {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>เพิ่มทีมเข้าสู่ทัวร์นาเมนต์ Challonge</h1>
        
        <div class="info-box">
            <p><strong>ทัวร์นาเมนต์:</strong> <?php echo htmlspecialchars($tournament_url); ?></p>
            <p><strong>จำนวนทีมทั้งหมด:</strong> <?php echo count($team_names); ?> ทีม</p>
            <p><a href="challonge_config.php">แก้ไขการตั้งค่า Challonge API</a></p>
        </div>
        
        <h2>รายชื่อทีมที่จะเพิ่มเข้าทัวร์นาเมนต์</h2>
        <div class="team-list">
            <?php foreach ($team_names as $team): ?>
                <div class="team-item"><?php echo htmlspecialchars($team); ?></div>
            <?php endforeach; ?>
        </div>
        
        <button id="addTeamsBtn" class="btn">เพิ่มทีมเข้าทัวร์นาเมนต์</button>
        <a href="index.php" class="btn btn-secondary">กลับหน้าหลัก</a>
        
        <div id="resultArea" class="result-area" style="display: none;">
            <h3>ผลการเพิ่มทีม</h3>
            <div id="resultContent"></div>
        </div>
    </div>

    <script>
        document.getElementById('addTeamsBtn').addEventListener('click', function() {
            this.disabled = true;
            this.textContent = 'กำลังเพิ่มทีม...';
            
            fetch('process_challonge_teams.php')
                .then(response => response.json())
                .then(data => {
                    const resultArea = document.getElementById('resultArea');
                    const resultContent = document.getElementById('resultContent');
                    
                    resultArea.style.display = 'block';
                    
                    if (data.success) {
                        let html = '<p class="success">เพิ่มทีมเข้าทัวร์นาเมนต์เรียบร้อยแล้ว</p>';
                        html += '<ul>';
                        
                        data.results.forEach(result => {
                            let status = result.success ? 'success' : 'error';
                            html += `<li class="${status}">${result.team}: ${result.message}</li>`;
                        });
                        
                        html += '</ul>';
                        resultContent.innerHTML = html;
                    } else {
                        resultContent.innerHTML = `<p class="error">เกิดข้อผิดพลาด: ${data.message}</p>`;
                    }
                    
                    this.textContent = 'เพิ่มทีมเข้าทัวร์นาเมนต์';
                    this.disabled = false;
                })
                .catch(error => {
                    document.getElementById('resultArea').style.display = 'block';
                    document.getElementById('resultContent').innerHTML = '<p class="error">เกิดข้อผิดพลาดในการเชื่อมต่อ</p>';
                    console.error(error);
                    
                    this.textContent = 'เพิ่มทีมเข้าทัวร์นาเมนต์';
                    this.disabled = false;
                });
        });
    </script>
</body>
</html>