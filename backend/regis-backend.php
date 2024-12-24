<?php
session_start();

// เชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rov_tournament";

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROV Tournament Hub</title>
    <link rel="stylesheet" href="../css/g.css">
    <link rel="icon" type="image/png" href="../img/logo.jpg">
</head>
<body>
    <div class="container">
        <h1>จัดตารางแข่งขันแบบแพ้คัดออก</h1>

        <!-- ฟอร์มค้นหาทีม -->
        <form method="GET">
            <input type="text" name="search_team" placeholder="ค้นหาชื่อทีม" required>
            <button type="submit">ค้นหา</button>
        </form>

        <!-- ปุ่มรีเซ็ตข้อมูลทีม -->
        <form method="POST">
            <button type="submit" name="reset_teams" style="background-color: red;">ล้างตารางแข่งขัน</button>
        </form>

        <?php if (!empty($error)): ?>
            <p class="error"> <?= htmlspecialchars($error) ?> </p>
        <?php endif; ?>


        <?php if (!empty($searchResult)): ?>
            <h2>ผลการค้นหา (พบ <?= count($searchResult) ?> ทีม)</h2>
            <ul>
                <?php foreach ($searchResult as $team): ?>
                    <li><?= htmlspecialchars($team) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search_team'])): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <!-- แสดงรายชื่อทีม -->
        <h2>รายชื่อทีมที่ลงทะเบียน (<?= count($teams) ?> ทีม)</h2>
        <ul>
            <?php foreach ($teams as $team): ?>
                <li><?= htmlspecialchars($team) ?></li>
            <?php endforeach; ?>
        </ul>

        <?php if (count($teams) >= 2 && empty($_SESSION['brackets'])): ?>
            <form method="POST">
                <button type="submit" name="generate_brackets">จัดตารางการแข่งขัน</button>
            </form>
        <?php endif; ?>

        <!-- แสดงตารางการแข่งขัน -->
        <?php if (!empty($_SESSION['brackets'])): ?>
            <?php foreach ($_SESSION['brackets'] as $roundIndex => $round): ?>
                <h2>รอบที่ <?= $roundIndex + 1 ?></h2>
                <form method="POST">
                    <ul>
                        <?php foreach ($round as $index => $match): ?>
                            <li>
                                <?= htmlspecialchars($match['team1']) ?> VS <?= htmlspecialchars($match['team2']) ?><br>
                                <label>คะแนน <?= htmlspecialchars($match['team1']) ?>:</label>
                                <input type="number" name="scores[<?= $index ?>][score1]" value="<?= htmlspecialchars($match['score1']) ?>">
                                <label>คะแนน <?= htmlspecialchars($match['team2']) ?>:</label>
                                <input type="number" name="scores[<?= $index ?>][score2]" value="<?= htmlspecialchars($match['score2']) ?>">
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($roundIndex === array_key_last($_SESSION['brackets'])): ?>
                        <button type="submit" name="update_scores">บันทึกคะแนน</button>
                    <?php endif; ?>
                </form>
            <?php endforeach; ?>

            <?php if (count($_SESSION['brackets']) > 0 && count(end($_SESSION['brackets'])) === 1): ?>
                <h2>ผู้ชนะเลิศ: <?= htmlspecialchars(end($_SESSION['brackets'])[0]['winner']) ?></h2>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>