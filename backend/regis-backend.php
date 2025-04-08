<?php
session_start();

// เชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "competition_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

// ดึงข้อมูลทีมจากฐานข้อมูล
$sql = "SELECT team_name FROM teams";
$result = $conn->query($sql);

$teams = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $teams[] = $row['team_name'];
    }
} else {
    $error = "ไม่มีทีมที่ลงทะเบียนในฐานข้อมูล";
}

// ฟังก์ชันจัดตารางการแข่งขัน
function generateBrackets($teams) {
    if (count($teams) < 2) {
        return ['error' => 'ต้องมีอย่างน้อย 2 ทีมเพื่อจัดตารางแข่งขัน'];
    }

    shuffle($teams);

    $requiredTeams = pow(2, ceil(log(count($teams), 2)));

    while (count($teams) < $requiredTeams) {
        $teams[] = "BYE";
    }

    $brackets = [];
    for ($i = 0; $i < count($teams); $i += 2) {
        $brackets[] = [
            'team1' => $teams[$i],
            'team2' => $teams[$i + 1],
            'winner' => null,
            'score1' => null,
            'score2' => null
        ];
    }

    return $brackets;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_brackets'])) {
    echo "กำลังสร้างตารางการแข่งขัน...";
    $_SESSION['brackets'] = [generateBrackets($teams)];
    if (isset($_SESSION['brackets'][0]['error'])) {
        echo "พบข้อผิดพลาด: " . $_SESSION['brackets'][0]['error'];
        $_SESSION['brackets'] = [];
    } else {
        echo "ตารางการแข่งขันถูกสร้างสำเร็จ!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_teams'])) {
    $_SESSION['teams'] = [];
    $_SESSION['brackets'] = [];
    $error = "ตารางการแข่งทั้งหมดถูกล้างเรียบร้อยแล้ว";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_scores'])) {
    if (!empty($_SESSION['brackets'])) {
        $currentRound = end($_SESSION['brackets']);

        foreach ($_POST['scores'] as $index => $scoreData) {
            $currentRound[$index]['score1'] = $scoreData['score1'];
            $currentRound[$index]['score2'] = $scoreData['score2'];
            $currentRound[$index]['winner'] = $scoreData['score1'] > $scoreData['score2'] ? $currentRound[$index]['team1'] : $currentRound[$index]['team2'];
        }

        $_SESSION['brackets'][key($_SESSION['brackets'])] = $currentRound;

        $winners = array_column($currentRound, 'winner');
        if (count($winners) > 1) {
            $_SESSION['brackets'][] = generateBrackets($winners);
        }
    } else {
        $error = "ไม่สามารถอัปเดตคะแนนได้ เนื่องจากยังไม่มีรอบการแข่งขัน";
    }
}
//การค้นหาทีม
$searchResult = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search_team'])) {
    $searchTerm = trim($_GET['search_team']);
    if (!empty($searchTerm)) {
        $stmt = $conn->prepare("SELECT team_name FROM teams WHERE team_name LIKE ?");
        $likeSearch = "%" . $searchTerm . "%";
        $stmt->bind_param("s", $likeSearch);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $searchResult[] = $row['team_name'];
            }
        } else {
            $error = "ไม่พบทีมที่ค้นหา";
        }

        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROV Tournament Hub</title>
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #5540cf;
            --primary-light: #6a55e8;
            --secondary: #29b6f6;
            --dark: #263238;
            --light: #eceff1;
            --danger: #f44336;
            --success: #4caf50;
            --warning: #ff9800;
            --white: #ffffff;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-800: #343a40;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--gray-100);
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }
        
        .card h2 {
            color: var(--primary);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--gray-200);
            font-size: 1.5rem;
        }
        
        .search-form {
            display: flex;
            margin-bottom: 20px;
        }
        
        .search-form input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid var(--gray-300);
            border-radius: 8px 0 0 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .search-form input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .search-form button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        
        .search-form button:hover {
            background-color: var(--primary-light);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            background-color: var(--primary);
            color: white;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-align: center;
            margin: 5px 0;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: var(--danger);
        }
        
        .btn-success {
            background-color: var(--success);
        }
        
        .team-list {
            list-style: none;
            margin: 15px 0;
        }
        
        .team-list li {
            background-color: var(--gray-100);
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .team-list li:hover {
            background-color: var(--gray-200);
        }
        
        .team-list li::before {
            content: "\f091";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 10px;
            color: var(--warning);
        }

        .error {
            color: var(--danger);
            background-color: rgba(244, 67, 54, 0.1);
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .success {
            color: var(--success);
            background-color: rgba(76, 175, 80, 0.1);
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        /* Tournament Bracket Styles */
        .tournament-bracket {
            display: flex;
            overflow-x: auto;
            padding: 20px 0;
            margin-bottom: 20px;
        }
        
        .tournament-round {
            min-width: 240px;
            margin-right: 40px;
            display: flex;
            flex-direction: column;
        }
        
        .tournament-round-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            color: var(--primary);
            font-size: 1.2rem;
            padding: 10px;
            background-color: var(--gray-100);
            border-radius: 8px;
            position: sticky;
            top: 0;
        }
        
        .match-connector {
            position: relative;
        }
        
        .match-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .match {
            background-color: var(--white);
            border: 2px solid var(--gray-300);
            border-radius: 8px;
            padding: 15px;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 2;
        }
        
        .match:hover {
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .match-title {
            text-align: center;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .team-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 10px;
            border-radius: 5px;
            margin-bottom: 5px;
            position: relative;
        }
        
        .team-row:last-child {
            margin-bottom: 0;
        }
        
        .team-name {
            font-weight: bold;
            flex: 1;
        }
        
        .team-score {
            background-color: var(--gray-100);
            min-width: 40px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .team-row.winner {
            background-color: rgba(76, 175, 80, 0.1);
        }
        
        .team-row.winner .team-name::after {
            content: "\f521";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-left: 8px;
            color: var(--success);
        }
        
        .score-input {
            width: 100%;
            margin-top: 10px;
        }
        
        .score-input-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .score-input-row label {
            flex: 1;
            font-size: 0.85rem;
        }
        
        .score-input-row input {
            width: 60px;
            padding: 8px;
            border: 1px solid var(--gray-300);
            border-radius: 5px;
            text-align: center;
        }
        
        /* Connector Lines */
        .connector {
            position: absolute;
            z-index: 1;
        }
        
        .connector-vertical {
            position: absolute;
            width: 2px;
            background-color: var(--primary);
            right: -20px;
            top: 60px;
            height: 160px;
        }
        
        .connector-horizontal {
            position: absolute;
            height: 2px;
            background-color: var(--primary);
            right: -20px;
            width: 20px;
        }
        
        .connector-top {
            top: 60px;
        }
        
        .connector-bottom {
            top: 220px;
        }
        
        .connector-middle {
            position: absolute;
            right: -40px;
            top: 140px;
            width: 20px;
            height: 2px;
            background-color: var(--primary);
        }
        
        /* Final Winner */
        .final-winner {
            background: linear-gradient(135deg, #ff9966, #ff5e62);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-top: 30px;
            animation: pulse 2s infinite;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .final-winner h2 {
            color: white;
            margin-bottom: 10px;
            font-size: 2rem;
            border: none;
        }
        
        .final-winner .trophy {
            font-size: 4rem;
            margin-bottom: 20px;
            color: gold;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .final-winner-name {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 94, 98, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(255, 94, 98, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 94, 98, 0); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .tournament-bracket {
                flex-direction: column;
                overflow-x: visible;
            }
            
            .tournament-round {
                width: 100%;
                margin-right: 0;
                margin-bottom: 30px;
            }
            
            .connector-vertical,
            .connector-horizontal,
            .connector-middle {
                display: none;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-form input,
            .search-form button {
                border-radius: 8px;
                width: 100%;
            }
            
            .match {
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-gamepad"></i> ROV Tournament Hub</h1>
            <p>ระบบจัดการแข่งขันแบบแพ้คัดออก</p>
        </div>
        
        <!-- ฟอร์มค้นหาทีม -->
        <div class="card">
            <h2><i class="fas fa-search"></i> ค้นหาทีม</h2>
            <form method="GET" class="search-form">
                <input type="text" name="search_team" placeholder="ป้อนชื่อทีมที่ต้องการค้นหา..." required>
                <button type="submit"><i class="fas fa-search"></i> ค้นหา</button>
            </form>
            
            <?php if (!empty($searchResult)): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i> พบผลการค้นหา <?= count($searchResult) ?> ทีม
                </div>
                <ul class="team-list">
                    <?php foreach ($searchResult as $team): ?>
                        <li><?= htmlspecialchars($team) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search_team'])): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- รายชื่อทีม -->
        <div class="card">
            <h2><i class="fas fa-users"></i> รายชื่อทีมที่ลงทะเบียน (<?= count($teams) ?> ทีม)</h2>
            
            <?php if (!empty($error) && !isset($_GET['search_team'])): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (count($teams) > 0): ?>
                <ul class="team-list">
                    <?php foreach ($teams as $team): ?>
                        <li><?= htmlspecialchars($team) ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if (count($teams) >= 2 && empty($_SESSION['brackets'])): ?>
                    <form method="POST">
                        <button type="submit" name="generate_brackets" class="btn btn-success">
                            <i class="fas fa-play-circle"></i> จัดตารางการแข่งขัน
                        </button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i> ไม่มีทีมที่ลงทะเบียนในระบบ
                </div>
            <?php endif; ?>
            
            <!-- ปุ่มรีเซ็ตข้อมูลทีม -->
            <form method="POST" style="margin-top: 15px;">
                <button type="submit" name="reset_teams" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> ล้างตารางแข่งขัน
                </button>
            </form>
        </div>
        
        <!-- แสดงตารางการแข่งขันแบบสายชัดเจน -->
        <?php if (!empty($_SESSION['brackets'])): ?>
            <div class="card">
                <h2><i class="fas fa-sitemap"></i> ตารางการแข่งขัน</h2>
                
                <div class="tournament-bracket">
                    <?php 
                    // แสดงรอบทั้งหมดในแนวนอน
                    foreach ($_SESSION['brackets'] as $roundIndex => $round): 
                    ?>
                        <div class="tournament-round">
                            <div class="tournament-round-title">
                                <?php 
                                // ตั้งชื่อรอบให้เหมาะสม
                                $totalRounds = count($_SESSION['brackets']);
                                $roundName = "";
                                
                                if ($roundIndex == $totalRounds - 1 && $totalRounds > 1) {
                                    $roundName = "รอบชิงชนะเลิศ";
                                } elseif ($roundIndex == $totalRounds - 2 && $totalRounds > 2) {
                                    $roundName = "รอบรองชนะเลิศ";
                                } elseif ($roundIndex == $totalRounds - 3 && $totalRounds > 3) {
                                    $roundName = "รอบ 8 ทีมสุดท้าย";
                                } elseif ($roundIndex == 0 && $totalRounds > 1) {
                                    $roundName = "รอบแรก";
                                } else {
                                    $roundName = "รอบที่ " . ($roundIndex + 1);
                                }
                                
                                echo htmlspecialchars($roundName);
                                ?>
                            </div>
                            
                            <form method="POST">
                                <?php foreach ($round as $index => $match): ?>
                                    <div class="match-wrapper">
                                        <div class="match">
                                            <!-- ทีม 1 -->
                                            <div class="team-row <?= ($match['winner'] == $match['team1']) ? 'winner' : '' ?>">
                                                <div class="team-name"><?= htmlspecialchars($match['team1']) ?></div>
                                                <div class="team-score">
                                                    <?= ($match['score1'] !== null) ? htmlspecialchars($match['score1']) : '-' ?>
                                                </div>
                                            </div>
                                            
                                            <!-- ทีม 2 -->
                                            <div class="team-row <?= ($match['winner'] == $match['team2']) ? 'winner' : '' ?>">
                                                <div class="team-name"><?= htmlspecialchars($match['team2']) ?></div>
                                                <div class="team-score">
                                                    <?= ($match['score2'] !== null) ? htmlspecialchars($match['score2']) : '-' ?>
                                                </div>
                                            </div>
                                            
                                            <!-- ฟอร์มบันทึกคะแนน -->
                                            <?php if ($roundIndex === array_key_last($_SESSION['brackets'])): ?>
                                                <div class="score-input">
                                                    <div class="score-input-row">
                                                        <label>คะแนน <?= htmlspecialchars($match['team1']) ?>:</label>
                                                        <input type="number" name="scores[<?= $index ?>][score1]" value="<?= htmlspecialchars($match['score1']) ?>" min="0">
                                                    </div>
                                                    <div class="score-input-row">
                                                        <label>คะแนน <?= htmlspecialchars($match['team2']) ?>:</label>
                                                        <input type="number" name="scores[<?= $index ?>][score2]" value="<?= htmlspecialchars($match['score2']) ?>" min="0">
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- เส้นเชื่อมระหว่างคู่แข่งขัน -->
                                        <?php if ($roundIndex < count($_SESSION['brackets']) - 1): ?>
                                            <?php if ($index % 2 == 0): ?>
                                                <div class="connector connector-vertical"></div>
                                                <div class="connector connector-horizontal connector-top"></div>
                                            <?php else: ?>
                                                <div class="connector connector-horizontal connector-bottom"></div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if ($roundIndex === array_key_last($_SESSION['brackets'])): ?>
                                    <button type="submit" name="update_scores" class="btn btn-success" style="width: 100%;">
                                        <i class="fas fa-save"></i> บันทึกคะแนน
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- แสดงผู้ชนะเลิศ -->
            <?php if (count($_SESSION['brackets']) > 0 && count(end($_SESSION['brackets'])) === 1 && end($_SESSION['brackets'])[0]['winner']): ?>
                <div class="final-winner">
                    <div class="trophy"><i class="fas fa-trophy"></i></div>
                    <h2>ผู้ชนะเลิศการแข่งขัน</h2>
                    <div class="final-winner-name"><?= htmlspecialchars(end($_SESSION['brackets'])[0]['winner']) ?></div>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
</body>
</html>