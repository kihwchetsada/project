<?php
// การเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rov_tournament";

// สร้างการเชื่อมต่อ
$conn = mysqli_connect($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ฟังก์ชันสำหรับดึงข้อมูลทีมทั้งหมดจากฐานข้อมูล
function getTeams($conn) {
    $teams = array();
    $sql = "SELECT id, team_name FROM teams ORDER BY RAND()";
    $result = mysqli_query($conn, $sql);
    
    // ตรวจสอบว่า query สำเร็จหรือไม่
    if ($result === false) {
        echo "เกิดข้อผิดพลาดในการดึงข้อมูลทีม: " . mysqli_error($conn);
        return $teams; // ส่งคืนอาร์เรย์ว่าง
    }
    
    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $teams[] = $row;
        }
    }
    
    return $teams;
}

// ฟังก์ชันสำหรับสร้างตารางการแข่งขันแบบแพ้คัดออก
function createBracket($teams) {
    $totalTeams = count($teams);
    
    // คำนวณจำนวนรอบและจำนวนทีมที่ต้องการ
    $rounds = ceil(log($totalTeams, 2));
    $neededTeams = pow(2, $rounds);
    
    // เติม "BYE" หากจำนวนทีมไม่พอตามรูปแบบ 2^n
    while (count($teams) < $neededTeams) {
        $teams[] = array("id" => 0, "team_name" => "BYE");
    }
    
    // สร้างตารางการแข่งขันตามรอบ
    $bracket = array();
    for ($i = 0; $i < $rounds; $i++) {
        $bracket[$i] = array();
    }
    
    // รอบแรก
    $matchCount = $neededTeams / 2;
    for ($i = 0; $i < $matchCount; $i++) {
        $team1 = $teams[$i];
        $team2 = $teams[$neededTeams - 1 - $i];
        
        // ถ้าเจอ BYE ให้ทีมอีกฝั่งผ่านเข้ารอบเลย
        $winner = null;
        if ($team1["team_name"] == "BYE") {
            $winner = $team2;
        } elseif ($team2["team_name"] == "BYE") {
            $winner = $team1;
        }
        
        $bracket[0][$i] = array(
            "team1" => $team1,
            "team2" => $team2,
            "team1_score" => 0,
            "team2_score" => 0,
            "winner" => $winner
        );
    }
    
    return $bracket;
}

// ฟังก์ชันสำหรับบันทึกตารางการแข่งขันลงฐานข้อมูล
function saveBracket($conn, $bracket) {
    // ลบข้อมูลตารางการแข่งขันเก่า
    $sql = "TRUNCATE TABLE matches";
    mysqli_query($conn, $sql);
    
    // บันทึกข้อมูลใหม่
    $roundNumber = 1;
    foreach ($bracket as $round) {
        $matchNumber = 1;
        foreach ($round as $match) {
            $team1Id = $match["team1"]["id"];
            $team2Id = $match["team2"]["id"];
            $team1Score = isset($match["team1_score"]) ? $match["team1_score"] : 0;
            $team2Score = isset($match["team2_score"]) ? $match["team2_score"] : 0;
            
            $sql = "INSERT INTO matches (round, match_number, team1_id, team2_id, team1_score, team2_score) 
                    VALUES ($roundNumber, $matchNumber, $team1Id, $team2Id, $team1Score, $team2Score)";
            mysqli_query($conn, $sql);
            
            $matchNumber++;
        }
        $roundNumber++;
    }
}

// ฟังก์ชันบันทึกผลการแข่งขัน
function updateMatchScore($conn, $matchId, $team1Score, $team2Score) {
    list($roundIndex, $matchIndex) = explode('-', $matchId);
    $roundNumber = $roundIndex + 1;
    $matchNumber = $matchIndex + 1;
    
    $sql = "UPDATE matches SET team1_score = $team1Score, team2_score = $team2Score 
            WHERE round = $roundNumber AND match_number = $matchNumber";
    
    return mysqli_query($conn, $sql);
}

// เริ่มต้น session
session_start();

// ประมวลผลคำขอ AJAX สำหรับการบันทึกคะแนน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_score') {
    $matchId = $_POST['match_id'];
    $team1Score = $_POST['team1_score'];
    $team2Score = $_POST['team2_score'];
    
    // บันทึกคะแนนลงฐานข้อมูล
    $result = updateMatchScore($conn, $matchId, $team1Score, $team2Score);
    
    // อัปเดตข้อมูลใน session
    if ($result && isset($_SESSION["bracket"])) {
        list($roundIndex, $matchIndex) = explode('-', $matchId);
        $roundIndex = (int)$roundIndex;
        $matchIndex = (int)$matchIndex;
        
        if (isset($_SESSION["bracket"][$roundIndex][$matchIndex])) {
            $_SESSION["bracket"][$roundIndex][$matchIndex]["team1_score"] = $team1Score;
            $_SESSION["bracket"][$roundIndex][$matchIndex]["team2_score"] = $team2Score;
            
            // กำหนดผู้ชนะตามคะแนน
            if ($team1Score > $team2Score) {
                $_SESSION["bracket"][$roundIndex][$matchIndex]["winner"] = $_SESSION["bracket"][$roundIndex][$matchIndex]["team1"];
            } elseif ($team2Score > $team1Score) {
                $_SESSION["bracket"][$roundIndex][$matchIndex]["winner"] = $_SESSION["bracket"][$roundIndex][$matchIndex]["team2"];
            } else {
                // กรณีคะแนนเท่ากัน (อาจต้องกำหนดกฎเพิ่มเติม)
                $_SESSION["bracket"][$roundIndex][$matchIndex]["winner"] = null;
            }
        }
    }
    
    // ส่งผลลัพธ์กลับเป็น JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => (bool)$result]);
    exit;
}

// ประมวลผล
if (isset($_POST["generate"])) {
    $teams = getTeams($conn);
    
    // ตรวจสอบว่ามีข้อมูลทีมหรือไม่
    if (count($teams) > 0) {
        $bracket = createBracket($teams);
        saveBracket($conn, $bracket);
        
        // เก็บข้อมูลใน session
        $_SESSION["bracket"] = $bracket;
        
        // กำหนดข้อความแจ้งเตือนความสำเร็จ
        $_SESSION["message"] = "สร้างตารางการแข่งขันเรียบร้อยแล้ว";
    } else {
        // กำหนดข้อความแจ้งเตือนความผิดพลาด
        $_SESSION["error"] = "ไม่พบข้อมูลทีม กรุณาเพิ่มทีมก่อนสร้างตารางการแข่งขัน";
    }
    
    // Redirect เพื่อหลีกเลี่ยงการ resubmit form
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit();
}

// ดึงข้อมูลตารางการแข่งขันจาก session
$bracket = isset($_SESSION["bracket"]) ? $_SESSION["bracket"] : null;

// เตรียมข้อความแจ้งเตือน
$message = isset($_SESSION["message"]) ? $_SESSION["message"] : "";
$error = isset($_SESSION["error"]) ? $_SESSION["error"] : "";

// ล้างข้อความแจ้งเตือนออกจาก session
unset($_SESSION["message"]);
unset($_SESSION["error"]);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตารางการแข่งขัน</title>
    <style>
        body {
            font-family: 'Sarabun', Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
        }
        .button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .button:hover {
            background-color: #45a049;
        }
        .tournament-bracket {
            display: flex;
            margin-top: 30px;
            overflow-x: auto;
        }
        .round {
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            width: 220px;
            margin-right: 40px;
        }
        .match {
            display: flex;
            flex-direction: column;
            min-height: 120px;
            margin-bottom: 20px;
            position: relative;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px;
            background-color: #fafafa;
        }
        .team {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #f1f1f1;
            margin-bottom: 5px;
            border-radius: 4px;
            cursor: pointer;
            position: relative;
        }
        .team.winner {
            background-color: #e6f7e6;
            border-left: 4px solid #4CAF50;
        }
        .team img {
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }
        .team .team-name {
            flex-grow: 1;
        }
        .connector {
            position: absolute;
            right: -40px;
            top: 50%;
            width: 40px;
            height: 2px;
            background-color: #777;
        }
        .connector:after {
            content: '';
            position: absolute;
            right: 0;
            top: -50px;
            width: 2px;
            height: 100px;
            background-color: #777;
        }
        .round-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            color: #555;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
        }
        .score-input {
            width: 40px;
            text-align: center;
            margin: 0 5px;
            padding: 3px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        .score-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 5px;
        }
        .save-score {
            background-color: #337ab7;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 3px 8px;
            cursor: pointer;
            font-size: 12px;
        }
        .save-score:hover {
            background-color: #286090;
        }
        .score-display {
            font-weight: bold;
            margin: 0 5px;
        }
        .bye-team {
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ตารางการแข่งขันแบบแพ้คัดออก</h1>
        
        <?php if (!empty($message)): ?>
        <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
        <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <button type="submit" name="generate" class="button">สร้างตารางการแข่งขันใหม่</button>
        </form>
        
        <?php if ($bracket): ?>
        <div class="tournament-bracket">
            <?php 
            $roundNames = array("รอบแรก", "รอบ 16 ทีม", "รอบ 8 ทีม", "รอบรองชนะเลิศ", "รอบชิงชนะเลิศ");
            foreach ($bracket as $roundIndex => $round): 
                $roundName = isset($roundNames[$roundIndex]) ? $roundNames[$roundIndex] : "รอบที่ " . ($roundIndex + 1);
            ?>
            <div class="round">
                <div class="round-title"><?php echo $roundName; ?></div>
                <?php foreach ($round as $matchIndex => $match): ?>
                <div class="match" data-round="<?php echo $roundIndex; ?>" data-match-index="<?php echo $matchIndex; ?>" data-match-id="<?php echo $roundIndex . '-' . $matchIndex; ?>">
                    <div class="team <?php echo (isset($match["winner"]) && isset($match["winner"]["id"]) && isset($match["team1"]["id"]) && $match["winner"]["id"] == $match["team1"]["id"]) ? "winner" : ""; ?>" data-team-id="<?php echo $match["team1"]["id"]; ?>">
                        <span class="team-name <?php echo ($match["team1"]["team_name"] == "BYE") ? "bye-team" : ""; ?>"><?php echo htmlspecialchars($match["team1"]["team_name"]); ?></span>
                    </div>
                    <div class="team <?php echo (isset($match["winner"]) && isset($match["winner"]["id"]) && isset($match["team2"]["id"]) && $match["winner"]["id"] == $match["team2"]["id"]) ? "winner" : ""; ?>" data-team-id="<?php echo $match["team2"]["id"]; ?>">
                        <span class="team-name <?php echo ($match["team2"]["team_name"] == "BYE") ? "bye-team" : ""; ?>"><?php echo htmlspecialchars($match["team2"]["team_name"]); ?></span>
                    </div>
                    
                    <?php if ($match["team1"]["team_name"] != "BYE" && $match["team2"]["team_name"] != "BYE"): ?>
                    <div class="score-container" data-match-id="<?php echo $roundIndex . '-' . $matchIndex; ?>">
                        <span class="score-display team1-score-display"><?php echo isset($match["team1_score"]) ? $match["team1_score"] : "0"; ?></span>
                        <input type="number" class="score-input team1-score" value="<?php echo isset($match["team1_score"]) ? $match["team1_score"] : "0"; ?>" min="0">
                        <span>vs</span>
                        <input type="number" class="score-input team2-score" value="<?php echo isset($match["team2_score"]) ? $match["team2_score"] : "0"; ?>" min="0">
                        <span class="score-display team2-score-display"><?php echo isset($match["team2_score"]) ? $match["team2_score"] : "0"; ?></span>
                        <button class="save-score">บันทึก</button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($roundIndex < count($bracket) - 1): ?>
                    <div class="connector"></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p>กรุณาคลิกปุ่ม "สร้างตารางการแข่งขันใหม่" เพื่อเริ่มต้นสร้างตารางการแข่งขัน</p>
        <?php endif; ?>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
                        let tournamentData = { rounds: [] };

                        fetchTournamentData();
                        addScoreEventListeners();

                        function fetchTournamentData() {
                            const rounds = document.querySelectorAll('.round');
                            tournamentData.rounds = Array.from(rounds).map((round, roundIndex) => {
                                return {
                                    matches: Array.from(round.querySelectorAll('.match')).map((match, matchIndex) => {
                                        const teams = match.querySelectorAll('.team');
                                        const team1 = {
                                            id: teams[0].dataset.teamId || 0,
                                            name: teams[0].querySelector('.team-name').textContent.trim(),
                                            isWinner: teams[0].classList.contains('winner'),
                                            score: parseInt(match.querySelector('.team1-score-display')?.textContent || "0") || 0
                                        };
                                        const team2 = {
                                            id: teams[1].dataset.teamId || 0,
                                            name: teams[1].querySelector('.team-name').textContent.trim(),
                                            isWinner: teams[1].classList.contains('winner'),
                                            score: parseInt(match.querySelector('.team2-score-display')?.textContent || "0") || 0
                                        };

                                        return {
                                            team1,
                                            team2,
                                            winner: team1.isWinner ? team1 : (team2.isWinner ? team2 : null),
                                            matchId: match.dataset.matchId || `${roundIndex}-${matchIndex}`
                                        };
                                    })
                                };
                            });

                            addEventListeners();
                        }

                        function addScoreEventListeners() {
                            document.querySelectorAll('.save-score').forEach(button => {
                                button.addEventListener('click', function () {
                                    const scoreContainer = this.closest('.score-container');
                                    const matchId = scoreContainer.dataset.matchId;
                                    const team1ScoreInput = scoreContainer.querySelector('.team1-score');
                                    const team2ScoreInput = scoreContainer.querySelector('.team2-score');

                                    let team1Score = parseInt(team1ScoreInput.value) || 0;
                                    let team2Score = parseInt(team2ScoreInput.value) || 0;

                                    saveScore(matchId, team1Score, team2Score, function (success) {
                                        if (success) {
                                            updateWinnerByScore(matchId, team1Score, team2Score);
                                        } else {
                                            alert('เกิดข้อผิดพลาดในการบันทึกคะแนน');
                                        }
                                    });
                                });
                            });
                        }

                        function saveScore(matchId, team1Score, team2Score, callback) {
                            const formData = new FormData();
                            formData.append('action', 'update_score');
                            formData.append('match_id', matchId);
                            formData.append('team1_score', team1Score);
                            formData.append('team2_score', team2Score);

                            fetch(window.location.href, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => callback(data.success))
                            .catch(error => {
                                console.error('Error:', error);
                                callback(false);
                            });
                        }

                        function updateWinnerByScore(matchId, team1Score, team2Score) {
                            const match = document.querySelector(`.match[data-match-id="${matchId}"]`);
                            if (!match) return;

                            const teams = match.querySelectorAll('.team');
                            const team1 = teams[0];
                            const team2 = teams[1];

                            let winner = null;
                            if (team1Score > team2Score) {
                                team1.classList.add('winner');
                                team2.classList.remove('winner');
                                winner = team1;
                            } else if (team2Score > team1Score) {
                                team2.classList.add('winner');
                                team1.classList.remove('winner');
                                winner = team2;
                            } else {
                                team1.classList.remove('winner');
                                team2.classList.remove('winner');
                            }

                            if (winner) {
                                const [roundIndex, matchIndex] = matchId.split('-').map(Number);
                                tournamentData.rounds[roundIndex].matches[matchIndex].winner = {
                                    id: winner.dataset.teamId,
                                    name: winner.querySelector('.team-name').textContent,
                                    score: winner === team1 ? team1Score : team2Score
                                };

                                advanceToNextRound(roundIndex, matchIndex, tournamentData.rounds[roundIndex].matches[matchIndex].winner);
                            }
                        }

                        function advanceToNextRound(currentRound, matchIndex, winner) {
                            const nextRoundIndex = currentRound + 1;
                            const nextMatchIndex = Math.floor(matchIndex / 2);

                            if (nextRoundIndex < tournamentData.rounds.length) {
                                const nextRound = tournamentData.rounds[nextRoundIndex];
                                const nextMatch = nextRound.matches[nextMatchIndex];

                                if (matchIndex % 2 === 0) {
                                    nextMatch.team1 = winner;
                                } else {
                                    nextMatch.team2 = winner;
                                }

                                const nextMatchElement = document.querySelector(`.match[data-round="${nextRoundIndex}"][data-match-index="${nextMatchIndex}"]`);
                                if (nextMatchElement) {
                                    const nextTeams = nextMatchElement.querySelectorAll('.team');
                                    if (matchIndex % 2 === 0) {
                                        nextTeams[0].querySelector('.team-name').textContent = winner.name;
                                        nextTeams[0].dataset.teamId = winner.id;
                                    } else {
                                        nextTeams[1].querySelector('.team-name').textContent = winner.name;
                                        nextTeams[1].dataset.teamId = winner.id;
                    }
                }
            }
        }
    });
    </script>