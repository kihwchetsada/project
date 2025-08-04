<?php
// เปิด error
error_reporting(E_ALL); 
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $team_name = trim($_POST['team_name']);
    $tournament_id = (int)$_POST['tournament_id'];

    if (!empty($team_name) && $tournament_id > 0) {
        // ตรวจสอบชื่อทีมซ้ำ
        $stmt = $conn->prepare("SELECT team_id FROM teams WHERE team_name = ? AND tournament_id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("si", $team_name, $tournament_id);
        $stmt->execute();
        $check_result = $stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "ชื่อทีมนี้มีอยู่ในทัวร์นาเมนต์แล้ว";
            $messageType = "error";
        } else {
            $stmt->close(); // ปิด statement ก่อนใช้ใหม่

            $stmt = $conn->prepare("INSERT INTO teams (team_name, tournament_id) VALUES (?, ?)");
            if (!$stmt) {
                die("Prepare failed (insert): " . $conn->error);
            }

            $stmt->bind_param("si", $team_name, $tournament_id);
            if ($stmt->execute()) {
                $message = "เพิ่มทีมสำเร็จ!";
                $messageType = "success";
            } else {
                $message = "เกิดข้อผิดพลาดในการเพิ่มทีม: " . $stmt->error;
                $messageType = "error";
            }
        }
        $stmt->close();
    } else {
        $message = "กรุณากรอกชื่อทีมและเลือกทัวร์นาเมนต์";
        $messageType = "error";
    }
}
// ดึงทัวร์นาเมนต์
$tournaments = $conn->query("SELECT id, tournament_name FROM tournaments ORDER BY tournament_name");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มทีม - Tournament Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="white" opacity="0.1"/></svg>') repeat;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-100px, -100px) rotate(360deg); }
        }

        .header h1 {
            font-size: 2.5em;
            font-weight: 600;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .header .subtitle {
            font-size: 1.1em;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .header .icon {
            font-size: 3em;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .content {
            padding: 40px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            text-align: center;
            animation: fadeIn 0.5s ease-out;
            position: relative;
            overflow: hidden;
        }

        .message.success {
            background: linear-gradient(135deg, #4ecdc4, #44a08d);
            color: white;
            border-left: 5px solid #2ecc71;
        }

        .message.error {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border-left: 5px solid #e74c3c;
        }

        .message::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shine 2s infinite;
        }

        @keyframes shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 1.1em;
        }

        .form-group label i {
            margin-right: 8px;
            color: #667eea;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            font-size: 1.1em;
            font-family: 'Kanit', sans-serif;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .form-group input:hover,
        .form-group select:hover {
            border-color: #a0aec0;
            background: white;
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 1.2em;
            pointer-events: none;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 1.2em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-family: 'Kanit', sans-serif;
            position: relative;
            overflow: hidden;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .navigation {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e1e8ed;
        }

        .nav-link {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .nav-link:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            text-decoration: none;
        }

        .nav-link i {
            margin-right: 8px;
        }

        .stats-container {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
        }

        .stats-item {
            display: inline-block;
            margin: 0 15px;
        }

        .stats-number {
            font-size: 2em;
            font-weight: 700;
            display: block;
        }

        .stats-label {
            font-size: 0.9em;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 2em;
            }

            .content {
                padding: 25px;
            }

            .form-group input,
            .form-group select {
                padding: 12px 15px;
                font-size: 1em;
            }

            .submit-btn {
                padding: 12px 25px;
                font-size: 1.1em;
            }
        }

        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading.active {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>

    <div class="container">
        <div class="header">
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <h1>เพิ่มทีมใหม่</h1>
            <div class="subtitle">สร้างทีมเพื่อเข้าร่วมการแข่งขัน</div>
        </div>

        <div class="content">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php if ($messageType === 'success'): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-triangle"></i>
                    <?php endif; ?>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php
            // แสดงสถิติ
            $total_tournaments = $conn->query("SELECT COUNT(*) as count FROM tournaments")->fetch_assoc()['count'];
            $total_teams = $conn->query("SELECT COUNT(*) as count FROM teams")->fetch_assoc()['count'];
            ?>
            
            <div class="stats-container">
                <div class="stats-item">
                    <span class="stats-number"><?php echo $total_tournaments; ?></span>
                    <span class="stats-label">ทัวร์นาเมนต์</span>
                </div>
                <div class="stats-item">
                    <span class="stats-number"><?php echo $total_teams; ?></span>
                    <span class="stats-label">ทีมทั้งหมด</span>
                </div>
            </div>

            <form method="post" id="teamForm">
                <div class="form-group">
                    <label for="team_name">
                        <i class="fas fa-flag"></i>
                        ชื่อทีม
                    </label>
                    <div class="input-wrapper">
                        <input type="text" 
                               id="team_name" 
                               name="team_name" 
                               placeholder="กรอกชื่อทีมของคุณ" 
                               required 
                               maxlength="100"
                               autocomplete="off">
                        <i class="input-icon fas fa-edit"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="tournament_id">
                        <i class="fas fa-trophy"></i>
                        เลือกทัวร์นาเมนต์
                    </label>
                    <div class="input-wrapper">
                        <select name="tournament_id" id="tournament_id" required>
                            <option value="">-- กรุณาเลือกทัวร์นาเมนต์ --</option>
                            <?php if ($tournaments && $tournaments->num_rows > 0): ?>
                                <?php while ($row = $tournaments->fetch_assoc()): ?>
                                    <option value="<?= $row['id'] ?>">
                                        <?= htmlspecialchars($row['tournament_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>ไม่มีทัวร์นาเมนต์</option>
                            <?php endif; ?>
                        </select>
                        <i class="input-icon fas fa-chevron-down"></i>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-plus-circle"></i>
                    เพิ่มทีม
                </button>
            </form>

            <div class="navigation">
                <a href="view_teams.php" class="nav-link">
                    <i class="fas fa-list"></i>
                    ดูทีมทั้งหมด
                </a>
            </div>
        </div>
    </div>

    <script>
        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('teamForm');
            const teamNameInput = document.getElementById('team_name');
            const tournamentSelect = document.getElementById('tournament_id');
            const loading = document.getElementById('loading');

            // Auto-capitalize team name
            teamNameInput.addEventListener('input', function() {
                this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
            });

            // Form submission with loading
            form.addEventListener('submit', function(e) {
                const teamName = teamNameInput.value.trim();
                const tournamentId = tournamentSelect.value;

                if (!teamName || !tournamentId) {
                    e.preventDefault();
                    showMessage('กรุณากรอกข้อมูลให้ครบถ้วน', 'error');
                    return;
                }

                if (teamName.length < 2) {
                    e.preventDefault();
                    showMessage('ชื่อทีมต้องมีอย่างน้อย 2 ตัวอักษร', 'error');
                    teamNameInput.focus();
                    return;
                }

                // Show loading
                loading.classList.add('active');
            });

            // Enhanced select styling
            tournamentSelect.addEventListener('change', function() {
                if (this.value) {
                    this.style.color = '#333';
                } else {
                    this.style.color = '#999';
                }
            });

            // Auto-hide success messages
            const successMessage = document.querySelector('.message.success');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.animation = 'fadeOut 0.5s ease-out forwards';
                    setTimeout(() => {
                        successMessage.remove();
                    }, 500);
                }, 3000);
            }
        });

        function showMessage(text, type) {
            const existingMessage = document.querySelector('.message');
            if (existingMessage) {
                existingMessage.remove();
            }

            const message = document.createElement('div');
            message.className = `message ${type}`;
            message.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${text}
            `;

            const content = document.querySelector('.content');
            content.insertBefore(message, content.firstChild);

            // Auto-hide after 3 seconds
            setTimeout(() => {
                message.style.animation = 'fadeOut 0.5s ease-out forwards';
                setTimeout(() => {
                    message.remove();
                }, 500);
            }, 3000);
        }

        // Add fadeOut animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(-20px); }
            }
        `;
        document.head.appendChild(style);

        // Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>