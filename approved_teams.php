<?php
// เชื่อมต่อฐานข้อมูล
require 'db.php';
require 'db_connect.php'; // $conn เป็น PDO

// --- ดึงข้อมูล "รุ่น" ทั้งหมดมาเพื่อสร้างเมนูตัวเลือก ---
$stmt_tournament = $conn->query("SELECT id, tournament_name FROM tournaments ORDER BY tournament_name ASC");
$tournaments = $stmt_tournament->fetchAll(PDO::FETCH_ASSOC);

// --- (แก้ไข) ตรวจสอบว่ามีการเลือกรุ่นจากเมนูหรือไม่ โดยใช้ชื่อ 'tournament_id' ให้ตรงกับฟอร์ม ---
$selected_tournament_id = isset($_GET['tournament_id']) && !empty($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : null;


// --- สร้าง Query เพื่อดึงทีม โดยจะเพิ่มเงื่อนไขการกรองถ้ามีการเลือกรุ่น ---
$params = [];
$sql_teams = "
    SELECT 
        t.team_id, t.team_name, t.coach_name, t.coach_phone, t.leader_school, t.approved_by,
        c.tournament_name as tournament_name 
    FROM teams t
    LEFT JOIN tournaments c ON t.tournament_id = c.id 
    WHERE t.is_approved = 1
";

if ($selected_tournament_id) {
    $sql_teams .= " AND t.tournament_id = ?";
    $params[] = $selected_tournament_id;
}

$sql_teams .= " ORDER BY t.team_name ASC";

$stmt_teams = $conn->prepare($sql_teams);
$stmt_teams->execute($params);
$teams = $stmt_teams->fetchAll(PDO::FETCH_ASSOC);
$team_count = count($teams);


// --- ส่วนการดึงข้อมูลสมาชิกยังคงเหมือนเดิม ---
$members_by_team = [];
if ($team_count > 0) {
    $team_ids = array_column($teams, 'team_id');
    $placeholders = implode(',', array_fill(0, count($team_ids), '?'));
    $sql_members = "
        SELECT team_id, member_name, game_name, age, position 
        FROM team_members 
        WHERE team_id IN ($placeholders) 
        ORDER BY team_id, member_id ASC
    ";
    $stmt_members = $conn->prepare($sql_members);
    $stmt_members->execute($team_ids);
    $all_members = $stmt_members->fetchAll(PDO::FETCH_ASSOC);

    foreach ($all_members as $member) {
        $members_by_team[$member['team_id']][] = $member;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายชื่อทีมที่อนุมัติแล้ว</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --background-color: #f4f7f6;
            --card-bg-color: #ffffff;
            --text-color: #333;
            --text-light: #7f8c8d;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --border-color: #e0e0e0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; background-color: var(--background-color); color: var(--text-color); line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; padding: 20px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; border-radius: 12px; box-shadow: 0 10px 20px rgba(0,0,0,0.15); }
        .header h1 { font-size: 2.5rem; margin-bottom: 10px; font-weight: 700; }
        .header .subtitle { font-size: 1.1rem; opacity: 0.9; }

        /* === CSS สำหรับกล่องกรองข้อมูล === */
        .filter-container {
            background-color: var(--card-bg-color);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px var(--shadow-color);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }
        .filter-container label { font-weight: 700; color: var(--secondary-color); }
        .filter-container select, .filter-container button {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-family: 'Sarabun', sans-serif;
            font-size: 1rem;
        }
        .filter-container button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .filter-container button:hover { background-color: #2980b9; }
        
        .stats-card { background-color: var(--card-bg-color); border-radius: 12px; padding: 25px; text-align: center; margin-bottom: 40px; box-shadow: 0 4px 15px var(--shadow-color); border-left: 5px solid var(--primary-color); }
        .stats-content { display: flex; align-items: center; justify-content: center; gap: 20px; }
        .stats-icon i { font-size: 3rem; color: var(--primary-color); }
        .stats-text h3 { font-size: 2.5rem; font-weight: 700; color: var(--secondary-color); }
        .stats-text p { font-size: 1rem; color: var(--text-light); }
        .teams-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 25px; }
        .team-card { background-color: var(--card-bg-color); border-radius: 12px; box-shadow: 0 4px 15px var(--shadow-color); overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .team-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .card-header { background-color: var(--secondary-color); color: white; padding: 20px; }
        .team-name { font-size: 1.5rem; font-weight: 700; margin-bottom: 5px; }
        .team-name i { margin-right: 10px; }
        .team-school { font-size: 1rem; opacity: 0.9; }
        .team-school i { margin-right: 8px; }
        .card-body { padding: 20px; }
        .card-section { margin-bottom: 20px; }
        .card-section:last-child { margin-bottom: 0; }
        .section-title { font-size: 1.1rem; font-weight: 700; color: var(--secondary-color); margin-bottom: 10px; border-bottom: 2px solid var(--border-color); padding-bottom: 5px; }
        .section-title i { margin-right: 8px; color: var(--primary-color); }
        .info-row { display: flex; align-items: center; margin-bottom: 8px; font-size: 0.95rem; }
        .info-row i { width: 20px; color: var(--text-light); margin-right: 10px; }
        .no-data { text-align: center; padding: 60px 20px; background-color: var(--card-bg-color); border-radius: 12px; box-shadow: 0 4px 15px var(--shadow-color); }
        .no-data i { font-size: 4rem; color: #bdc3c7; margin-bottom: 20px; }
        .no-data h3 { font-size: 1.5rem; color: var(--secondary-color); margin-bottom: 10px; }
        @media (max-width: 400px) { .teams-grid { grid-template-columns: 1fr; } }
        .members-container { display: flex; flex-direction: column; gap: 10px; }
        .member-item { background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 12px 15px; transition: background-color 0.2s ease; }
        .member-item:hover { background-color: #f1f3f5; }
        .member-info { display: flex; justify-content: space-between; align-items: center; font-weight: 500; margin-bottom: 8px; }
        .member-name { color: var(--secondary-color); font-weight: 700; font-size: 1.05rem; }
        .member-position { background-color: var(--primary-color); color: white; font-size: 0.8rem; padding: 3px 10px; border-radius: 12px; font-weight: 500; }
        .member-details { display: flex; flex-wrap: wrap; gap: 10px 20px; font-size: 0.9rem; color: var(--text-light); }
        .member-details span { display: flex; align-items: center; }
        .member-details i { width: 16px; text-align: center; margin-right: 6px; }
        .no-members-info { color: var(--text-light); text-align: center; padding: 15px; font-style: italic; }
        .competition-badge {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-trophy"></i> รายชื่อทีมที่ได้รับการอนุมัติ</h1>
            <p class="subtitle">ทีมที่ผ่านการคัดเลือกและพร้อมเข้าร่วมการแข่งขัน</p>
        </div>

        <div class="filter-container">
            <form action="approved_teams.php" method="GET" style="display: flex; align-items: center; gap: 15px;">
                <label for="tournament_id"><i class="fas fa-filter"></i> เลือกดูตามรุ่น:</label>
                <select name="tournament_id" id="tournament_id">
                    <option value="">-- แสดงทุกรุ่น --</option>
                    <?php foreach ($tournaments as $tournament): ?>
                        <option value="<?php echo $tournament['id'] ?>" <?= ($selected_tournament_id == $tournament['id']) ? 'selected' : '' ?>>
                            <?php echo htmlspecialchars($tournament['tournament_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">กรองข้อมูล</button>
            </form>
        </div>

        <div class="stats-card">
            <div class="stats-content">
                <div class="stats-icon"><i class="fas fa-users-check"></i></div>
                <div class="stats-text">
                    <h3><?= $team_count ?></h3>
                    <p>
                        ทีมที่ได้รับการอนุมัติ 
                        <?php if($selected_tournament_id) echo "(ที่ถูกกรอง)"; ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if ($team_count > 0): ?>
            <div class="teams-grid">
                <?php foreach ($teams as $team): ?>
                    <div class="team-card">
                        <div class="card-header">
                            <div class="team-name"><i class="fas fa-flag"></i><?= htmlspecialchars($team['team_name']) ?></div>
                            <div class="team-school"><i class="fas fa-school"></i><?= htmlspecialchars($team['leader_school']) ?></div>
                                <?php if (!empty($team['tournament_name'])): ?>
                                <div class="competition-badge">
                                    <i class="fas fa-tag"></i> <?= htmlspecialchars($team['tournament_name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="card-section">
                                <h4 class="section-title"><i class="fas fa-user-tie"></i>ข้อมูลผู้ควบคุมทีม</h4>
                                <div class="info-row"><i class="fas fa-user"></i><span><?= htmlspecialchars($team['coach_name']) ?></span></div>
                                <div class="info-row"><i class="fas fa-phone"></i><span><?= htmlspecialchars($team['coach_phone']) ?></span></div>
                            </div>
                            <div class="card-section">
                                <h4 class="section-title"><i class="fas fa-users"></i>รายชื่อสมาชิกในทีม</h4>
                                <div class="members-container">
                                    <?php 
                                    $current_members = $members_by_team[$team['team_id']] ?? [];
                                    if (!empty($current_members)):
                                        foreach ($current_members as $member):
                                    ?>
                                    <div class="member-item">
                                        <div class="member-info">
                                            <span class="member-name"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($member['member_name']) ?></span>
                                            <span class="member-position"><?= htmlspecialchars($member['position']) ?></span>
                                        </div>
                                        <div class="member-details">
                                            <span><i class="fas fa-gamepad"></i> <?= htmlspecialchars($member['game_name']) ?></span>
                                            <span><i class="fas fa-birthday-cake"></i> <?= htmlspecialchars($member['age']) ?> ปี</span>
                                        </div>
                                    </div>
                                    <?php endforeach; else: ?>
                                        <p class="no-members-info">ไม่มีข้อมูลสมาชิกในทีมนี้</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-section">
                                <h4 class="section-title"><i class="fas fa-clipboard-check"></i>ข้อมูลการอนุมัติ</h4>
                                <div class="info-row">
                                    <i class="fas fa-user-shield"></i>
                                    <span><strong>อนุมัติโดย:</strong> <?php echo htmlspecialchars($team['approved_by']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-inbox"></i>
                <h3>ไม่พบข้อมูล</h3>
                <p>ไม่พบทีมที่ได้รับการอนุมัติตามเงื่อนไขที่เลือก</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>