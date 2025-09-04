<?php
session_start();
require 'db_connect.php'; // เชื่อมต่อฐานข้อมูล (PDO)

// ตรวจสอบสิทธิ์ (ถ้าคุณต้องการให้เฉพาะ admin/organizer ใช้ได้)
if (!isset($_SESSION['userData']) || $_SESSION['userData']['role'] !== 'organizer') {
    die('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// ดึงรายชื่อ Tournament
$stmt = $conn->query("SELECT id, tournament_name FROM tournaments ORDER BY tournament_name ASC");
$tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เลือก Tournament - PDF Generator</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: white;
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3);
        }

        h1 {
            color: #333;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #666;
            font-size: 16px;
            font-weight: 300;
        }

        .count-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 6px 16px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 15px;
        }

        .form-section {
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 16px;
        }

        label i {
            margin-right: 8px;
            color: #4CAF50;
        }

        .select-wrapper {
            position: relative;
        }

        select {
            width: 100%;
            padding: 16px 20px;
            font-size: 16px;
            font-family: 'Kanit', sans-serif;
            font-weight: 400;
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            background: white;
            color: #333;
            appearance: none;
            cursor: pointer;
            transition: all 0.3s ease;
            padding-right: 50px;
        }

        select:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        .select-wrapper::after {
            content: '▼';
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            pointer-events: none;
            font-size: 14px;
            transition: transform 0.3s ease;
        }

        .select-wrapper:hover::after {
            transform: translateY(-50%) rotate(180deg);
        }

        .btn {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 16px 40px;
            font-size: 16px;
            font-weight: 500;
            font-family: 'Kanit', sans-serif;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn i {
            font-size: 18px;
        }

        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            color: #666;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .back-btn:hover {
            background: white;
            color: #4CAF50;
            transform: translateX(-5px);
        }

        /* Loading state */
        .btn.loading {
            background: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }

        .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                padding: 30px 20px;
            }

            h1 {
                font-size: 24px;
            }

            .logo {
                width: 70px;
                height: 70px;
                font-size: 28px;
            }

            .back-btn {
                width: 45px;
                height: 45px;
                font-size: 16px;
            }
        }

        /* Success animation */
        .success {
            border-color: #4CAF50 !important;
            background: rgba(76, 175, 80, 0.05) !important;
        }

        .error {
            border-color: #f44336 !important;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</head>
<body>
    <a href="backend\organizer_dashboard.php" class="back-btn" title="กลับ">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-trophy"></i>
            </div>
            <h1>เลือก Tournament</h1>
            <p class="subtitle">สร้างรายงาน PDF รายชื่อทีม</p>
            <div class="count-badge">
                <i class="fas fa-list"></i> <?= count($tournaments); ?> รายการ
            </div>
        </div>

        <form method="get" action="print_pdf.php" class="form-section" id="tournamentForm">
            <div class="form-group">
                <label for="tid">
                    <i class="fas fa-trophy"></i>
                    เลือก Tournament:
                </label>
                <div class="select-wrapper">
                    <select name="tid" id="tid" required>
                        <option value="">-- กรุณาเลือก Tournament --</option>
                        <?php foreach ($tournaments as $t): ?>
                            <option value="<?= $t['id']; ?>"><?= htmlspecialchars($t['tournament_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn" id="submitBtn">
                <i class="fas fa-file-pdf"></i>
                สร้าง PDF
                <div class="spinner" id="spinner"></div>
            </button>
        </form>
    </div>

    <script>
        const form = document.getElementById('tournamentForm');
        const select = document.getElementById('tid');
        const submitBtn = document.getElementById('submitBtn');
        const spinner = document.getElementById('spinner');

        // Handle form submission
        form.addEventListener('submit', function(e) {
            if (select.value === '') {
                e.preventDefault();
                select.classList.add('error');
                setTimeout(() => {
                    select.classList.remove('error');
                }, 500);
                return false;
            }

            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<div class="spinner" style="display: block;"></div> กำลังสร้าง PDF...';
        });

        // Handle select change
        select.addEventListener('change', function() {
            if (this.value !== '') {
                this.classList.add('success');
                this.classList.remove('error');
            } else {
                this.classList.remove('success');
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                history.back();
            }
        });
    </script>
</body>
</html>