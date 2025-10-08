<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$dashboard = ($_SESSION['conn']['role'] === 'admin') 
    ? 'backend/admin_dashboard.php' 
    : 'backend/organizer_dashboard.php';

// เชื่อมต่อฐานข้อมูล
require 'db_connect.php'; // $conn เป็น PDO

// --- ดึงข้อมูล "รุ่น" ทั้งหมดมาเพื่อสร้างเมนูตัวเลือก ---
$stmt_tournament = $conn->query("SELECT id, tournament_name FROM tournaments ORDER BY tournament_name ASC");
$tournaments = $stmt_tournament->fetchAll(PDO::FETCH_ASSOC);

// --- ตรวจสอบว่ามีการเลือกรุ่นจากเมนูหรือไม่ ---
$selected_tournament_id = isset($_GET['tournament_id']) && !empty($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : null;

// --- สร้าง Query เพื่อดึงทีม ---
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

// --- ดึงข้อมูลสมาชิกทีมทั้งหมด ---
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
    <link rel="stylesheet" href="css/approved_teams.css">
</head>
<body>
    <a href="<?= $dashboard ?>" class="btn">กลับไปหน้าแดชบอร์ด</a>
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
                        <!-- ชื่อทีม (คลิกเพื่อขยาย/ยุบ) -->
                        <div class="team-name toggle-btn" data-target="team-<?= $team['team_id'] ?>">
                            <i class="fas fa-flag"></i> <?= htmlspecialchars($team['team_name']) ?>
                            <small style="margin-left:10px; color:#666;">(กดเพื่อดูรายละเอียด)</small>
                        </div>

                        <!-- รายละเอียดทีม (ซ่อนตอนแรก) -->
                        <div id="team-<?= $team['team_id'] ?>" class="team-detail" style="display:none; margin-top:10px;">
                            <div class="card-section">
                                <h4 class="section-title"><i class="fas fa-school"></i> โรงเรียน</h4>
                                <div class="info-row"><?= htmlspecialchars($team['leader_school']) ?></div>
                                <?php if (!empty($team['tournament_name'])): ?>
                                    <div class="competition-badge"><i class="fas fa-tag"></i> <?= htmlspecialchars($team['tournament_name']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="card-section">
                                <h4 class="section-title"><i class="fas fa-user-tie"></i> ข้อมูลผู้ควบคุมทีม</h4>
                                <div class="info-row"><i class="fas fa-user"></i> <?= htmlspecialchars($team['coach_name']) ?></div>
                                <div class="info-row"><i class="fas fa-phone"></i> <?= htmlspecialchars($team['coach_phone']) ?></div>
                            </div>

                            <div class="card-section">
                                <h4 class="section-title"><i class="fas fa-users"></i> รายชื่อสมาชิกในทีม</h4>
                                <div class="members-container">
                                    <?php 
                                    $current_members = $members_by_team[$team['team_id']] ?? [];
                                    if (!empty($current_members)):
                                        foreach ($current_members as $member): ?>
                                            <div class="member-item">
                                                <span class="member-name"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($member['member_name']) ?></span>
                                                <span class="member-position"><?= htmlspecialchars($member['position']) ?></span>
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
                                <h4 class="section-title"><i class="fas fa-clipboard-check"></i> ข้อมูลการอนุมัติ</h4>
                                <div class="info-row">
                                    <i class="fas fa-user-shield"></i> 
                                    อนุมัติโดย: <?= htmlspecialchars($team['approved_by']) ?>
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

    <button id="backToTopBtn" title="กลับไปด้านบนสุด"><i class="fas fa-arrow-up"></i></button>

<script>
    // ปุ่มกลับไปด้านบน
    const backToTopButton = document.getElementById("backToTopBtn");
    window.onscroll = () => {
        if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
            backToTopButton.classList.add("show");
        } else {
            backToTopButton.classList.remove("show");
        }
    };
    backToTopButton.addEventListener("click", () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Accordion: แสดงทีมเดียวที่เลือก
    document.querySelectorAll(".toggle-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const target = document.getElementById(btn.dataset.target);

            // ซ่อนทีมอื่นทั้งหมดก่อน
            document.querySelectorAll(".team-detail").forEach(el => {
                if (el !== target) {
                    el.style.display = "none";
                }
            });

            // Toggle เฉพาะทีมที่กด
            if (target.style.display === "none" || target.style.display === "") {
                target.style.display = "block";
            } else {
                target.style.display = "none";
            }
        });
    });
</script>
</body>
</html>