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

// เริ่มต้น session
session_start();

// ฟังก์ชันสำหรับดึงข้อมูลทีมทั้งหมดจากฐานข้อมูล
function getTeams($conn) {
    $teams = array();
    $total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM teams"))["total"];
    $random_offset = rand(0, max(0, $total - 10));  // ดึงข้อมูลแบบสุ่ม
    $sql = "SELECT id, team_name FROM teams LIMIT 10 OFFSET $random_offset";
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
            $values = [];
            foreach ($bracket as $roundNumber => $round) {
                foreach ($round as $matchNumber => $match) {
                    $team1Id = $match["team1"]["id"];
                    $team2Id = $match["team2"]["id"];
                    $team1Score = $match["team1_score"];
                    $team2Score = $match["team2_score"];
                    $values[] = "($roundNumber, $matchNumber, $team1Id, $team2Id, $team1Score, $team2Score)";
                }
            }

            $sql = "INSERT INTO matches (round, match_number, team1_id, team2_id, team1_score, team2_score) VALUES " . implode(",", $values);
            mysqli_query($conn, $sql);

            $matchNumber++;
        }
        $roundNumber++;
    }
}

// ฟังก์ชันส่งทีมผู้ชนะเข้ารอบถัดไป
function advanceWinnerToNextRound($roundIndex, $matchIndex) {
    global $conn;  // ใช้ตัวแปร global แทน $GLOBALS
    
    // ตรวจสอบว่ามีรอบถัดไปหรือไม่
    if (!$_SESSION["bracket"][$roundIndex + 1] ?? false) return;

    
    // คำนวณ matchIndex ในรอบถัดไป
    $nextMatchIndex = floor($matchIndex / 2);
    
    // ดึงข้อมูลผู้ชนะจากรอบปัจจุบัน
    $winner = $_SESSION["bracket"][$roundIndex][$matchIndex]["winner"];
    if (!$winner) return; // ถ้ายังไม่มีผู้ชนะ ให้หยุดการทำงาน
    
    // กำหนดทีมในรอบถัดไป (ทีม 1 หรือ ทีม 2)
    if ($matchIndex % 2 == 0) {
        $_SESSION["bracket"][$roundIndex + 1][$nextMatchIndex]["team1"] = $winner;
    } else {
        $_SESSION["bracket"][$roundIndex + 1][$nextMatchIndex]["team2"] = $winner;
    }
    
    // อัปเดตฐานข้อมูล
    $roundNumber = $roundIndex + 2; // รอบถัดไป
    $matchNumber = $nextMatchIndex + 1;
    
    $team1Id = $_SESSION["bracket"][$roundIndex + 1][$nextMatchIndex]["team1"]["id"] ?? 0;
    $team2Id = $_SESSION["bracket"][$roundIndex + 1][$nextMatchIndex]["team2"]["id"] ?? 0;
    
    $sql = "UPDATE matches SET team1_id = $team1Id, team2_id = $team2Id 
            WHERE round = $roundNumber AND match_number = $matchNumber";
    mysqli_query($conn, $sql);
}

// ฟังก์ชันบันทึกผลการแข่งขัน - แก้ไขให้ทำงานได้อย่างถูกต้อง
function updateMatchScore($conn, $matchId, $team1Score, $team2Score) {
    list($roundIndex, $matchIndex) = explode('-', $matchId);
    $roundNumber = (int)$roundIndex + 1;
    $matchNumber = (int)$matchIndex + 1;
    
    $sql = "UPDATE matches SET team1_score = $team1Score, team2_score = $team2Score 
            WHERE round = $roundNumber AND match_number = $matchNumber";
    
    return mysqli_query($conn, $sql);
}

// ประมวลผลคำขอ AJAX สำหรับการบันทึกคะแนน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_score') {
    $matchId = $_POST['match_id'];
    $team1Score = $_POST['team1_score'];
    $team2Score = $_POST['team2_score'];
    
    // ป้องกัน SQL Injection โดยการแปลงเป็นตัวเลข
    $team1Score = intval($team1Score);
    $team2Score = intval($team2Score);
    
    // บันทึกผลลัพธ์
    $success = false;
    $error_message = "";
    
    try {
        // บันทึกคะแนนลงฐานข้อมูล
        $result = updateMatchScore($conn, $matchId, $team1Score, $team2Score);
        
        // ตรวจสอบผลการบันทึก
        if (!$result) {
            $error_message = "Database error: " . mysqli_error($conn);
        } else {
            $success = true;
            
            // อัปเดตข้อมูลใน session
            if (isset($_SESSION["bracket"])) {
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
                        // กรณีคะแนนเท่ากัน
                        $_SESSION["bracket"][$roundIndex][$matchIndex]["winner"] = null;
                    }
                    
                    // ดำเนินการส่งทีมเข้ารอบถัดไป
                    advanceWinnerToNextRound($roundIndex, $matchIndex);
                } else {
                    $error_message = "Match not found in session";
                }
            } else {
                $error_message = "Bracket not found in session";
            }
        }
    } catch (Exception $e) {
        $error_message = "Exception: " . $e->getMessage();
    }
    
    // ส่งผลลัพธ์กลับเป็น JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success, 
        'error' => $error_message
    ]);
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
        $_SESSION["bracket"] = array_map(function($round) {
            return array_map(function($match) {
                return ["id" => $match["id"], "winner" => $match["winner"]["id"] ?? null];
            }, $round);
        }, $bracket);
        
        
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
    <title>ตารางการแข่งขัน RoV Tournament</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/schedule.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <h1>ตารางการแข่งขัน RoV Tournament</h1>
        
        <div id="notification" style="display: none; padding: 10px; margin-bottom: 15px; border-radius: 5px;"></div>
        
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
                    <div class="team <?php echo (isset($match["winner"]) && isset($match["winner"]["id"]) && isset($match["team1"]["id"]) && $match["winner"]["id"] == $match["team1"]["id"]) ? "winner" : ""; ?>" data-team-id="<?php echo isset($match["team1"]["id"]) ? $match["team1"]["id"] : 0; ?>">
                        <span class="team-name <?php echo (isset($match["team1"]["team_name"]) && $match["team1"]["team_name"] == "BYE") ? "bye-team" : ""; ?>"><?php echo isset($match["team1"]["team_name"]) ? htmlspecialchars($match["team1"]["team_name"]) : "รอทีม"; ?></span>
                    </div>
                    <div class="team <?php echo (isset($match["winner"]) && isset($match["winner"]["id"]) && isset($match["team2"]["id"]) && $match["winner"]["id"] == $match["team2"]["id"]) ? "winner" : ""; ?>" data-team-id="<?php echo isset($match["team2"]["id"]) ? $match["team2"]["id"] : 0; ?>">
                        <span class="team-name <?php echo (isset($match["team2"]["team_name"]) && $match["team2"]["team_name"] == "BYE") ? "bye-team" : ""; ?>"><?php echo isset($match["team2"]["team_name"]) ? htmlspecialchars($match["team2"]["team_name"]) : "รอทีม"; ?></span>
                    </div>
                    
                    <?php if (isset($match["team1"]["team_name"]) && isset($match["team2"]["team_name"]) && $match["team1"]["team_name"] != "BYE" && $match["team2"]["team_name"] != "BYE"): ?>
                    <div class="score-container" data-match-id="<?php echo $roundIndex . '-' . $matchIndex; ?>">
                        <input type="number" class="score-input team1-score" value="<?php echo isset($match["team1_score"]) ? $match["team1_score"] : "0"; ?>" min="0">
                        <span class="vs-label">VS</span>
                        <input type="number" class="score-input team2-score" value="<?php echo isset($match["team2_score"]) ? $match["team2_score"] : "0"; ?>" min="0">
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
        <p style="text-align: center; margin-top: 30px;">กรุณาคลิกปุ่ม "สร้างตารางการแข่งขันใหม่" เพื่อเริ่มต้นสร้างตารางการแข่งขัน</p>
        <?php endif; ?>
    </div> 
    <script>
    $(document).ready(function() {
        // ฟังก์ชันแสดงข้อความแจ้งเตือน
        function showNotification(message, isSuccess) {
            var notification = $('#notification');
            
            // กำหนดสีพื้นหลังตามสถานะ
            if (isSuccess) {
                notification.css('background-color', '#4CAF50').css('color', 'white');
            } else {
                notification.css('background-color', '#f44336').css('color', 'white');
            }
            
            // แสดงข้อความ
            notification.text(message).fadeIn();
            
            // ซ่อนข้อความหลังจาก 3 วินาที
            setTimeout(function() {
                notification.fadeOut();
            }, 3000);
        }
        
        // จัดการการบันทึกคะแนน
        $('.save-score').on('click', function() {
            var container = $(this).closest('.score-container');
            var matchId = container.data('match-id');
            var team1Score = container.find('.team1-score').val();
            var team2Score = container.find('.team2-score').val();
            
            // แสดง loading
            var saveButton = $(this);
            saveButton.prop('disabled', true).text('กำลังบันทึก...');
            
            // ส่งคำขอ AJAX ไปยังเซิร์ฟเวอร์
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    action: 'update_score',
                    match_id: matchId,
                    team1_score: team1Score,
                    team2_score: team2Score
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // แสดงข้อความแจ้งเตือนสำเร็จ
                        showNotification('บันทึกคะแนนเรียบร้อยแล้ว', true);
                        
                        // รีโหลดหน้าเพื่ออัปเดตการแสดงผล
                        fetch(window.location.href, {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                        body: new URLSearchParams({ action: 'update_score', match_id: matchId, team1_score: team1Score, team2_score: team2Score })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            showNotification('บันทึกคะแนนเรียบร้อย', true);
                                            container.find('.team1-score').attr('disabled', true);
                                            container.find('.team2-score').attr('disabled', true);
                                        } else {
                                            showNotification('เกิดข้อผิดพลาด: ' + data.error, false);
                                        }
                                    });
                    } else {
                        // แสดงข้อความแจ้งเตือนข้อผิดพลาด
                        var errorMsg = response.error ? response.error : 'เกิดข้อผิดพลาดในการบันทึกคะแนน';
                        showNotification(errorMsg, false);
                        saveButton.prop('disabled', false).text('บันทึก');
                    }
                },
                error: function(xhr, status, error) {
                    // แสดงข้อความแจ้งเตือนข้อผิดพลาดการเชื่อมต่อ
                    console.error(xhr.responseText);
                    showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', false);
                    saveButton.prop('disabled', false).text('บันทึก');
                }
            });
        });
        
        // จัดการสีพื้นหลังผู้ชนะ
        $('.match').each(function() {
            var winner = $(this).find('.team.winner');
            if (winner.length > 0) {
                winner.css('background-color', '#9acd32');
            }
        });
    });
    </script>
</body>
</html>