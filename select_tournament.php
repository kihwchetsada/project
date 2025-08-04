<?php
// เปิด error ให้แสดงบนหน้าจอ
error_reporting(E_ALL);
ini_set('display_errors', 1);

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// แก้ไขตรงนี้: เปลี่ยน tm.id → tm.team_id
$tournaments = $conn->query("
    SELECT t.id, t.tournament_name, COUNT(tm.team_id) as team_count 
    FROM tournaments t 
    LEFT JOIN teams tm ON t.id = tm.tournament_id 
    GROUP BY t.id, t.tournament_name 
    ORDER BY t.tournament_name
");

if (!$tournaments) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลทัวร์นาเมนต์: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challonge Tournament Deployment</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        h1 {
            color: #333;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            font-size: 16px;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        select {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            background: white;
            color: #333;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            cursor: pointer;
        }

        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        select:hover {
            border-color: #667eea;
        }

        option {
            padding: 10px;
            background: white;
            color: #333;
        }

        .submit-btn {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn i {
            margin-right: 8px;
        }

        .info-text {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #555;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .header i {
                font-size: 40px;
            }
        }

        /* เอฟเฟกต์เมื่อเลือก */
        select option:checked {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="fas fa-trophy"></i>
            <h1>Tournament Deployment</h1>
            <p class="subtitle">เลือกทัวร์นาเมนต์เพื่อ Deploy ไปยัง Challonge</p>
        </div>

        <div class="info-text">
            <i class="fas fa-info-circle"></i>
            กรุณาเลือกทัวร์นาเมนต์ที่ต้องการส่งไปยังแพลตฟอร์ม Challonge เพื่อจัดการการแข่งขัน
        </div>

        <form id="tournamentForm" action="submit_to_challonge.php" method="post">
            <div class="form-group">
                <label for="tournament_id">
                    <i class="fas fa-gamepad"></i> เลือกทัวร์นาเมนต์:
                </label>
                <select name="tournament_id" id="tournament_id" required>
                    <option value="">-- กรุณาเลือกทัวร์นาเมนต์ --</option>
                    <?php while ($row = $tournaments->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>">
                            <?= htmlspecialchars($row['tournament_name']) ?> 
                            (<?= $row['team_count'] ?> ทีม)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-rocket"></i>
                Deploy to Challonge
            </button>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>กำลังประมวลผล...</p>
            </div>
        </form>
    </div>

    <script>
        // เพิ่ม JavaScript สำหรับ UX ที่ดีขึ้น
        document.getElementById('tournamentForm').addEventListener('submit', function(e) {
            const select = document.getElementById('tournament_id');
            if (!select.value) {
                e.preventDefault();
                alert('กรุณาเลือกทัวร์นาเมนต์ก่อนทำการ Deploy');
                return;
            }
            
            // แสดง loading
            document.getElementById('loading').style.display = 'block';
            document.querySelector('.submit-btn').style.display = 'none';
        });

        // เพิ่มเอฟเฟกต์เมื่อเลือก dropdown
        document.getElementById('tournament_id').addEventListener('change', function() {
            if (this.value) {
                this.style.borderColor = '#28a745';
                this.style.backgroundColor = '#f8f9fa';
            } else {
                this.style.borderColor = '#e1e5e9';
                this.style.backgroundColor = 'white';
            }
        });
    </script>
</body>
</html>