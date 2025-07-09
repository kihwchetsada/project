<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';         // user_db
require 'db_connect.php'; // competition_db

// ตรวจสอบว่าเข้าสู่ระบบหรือยัง
if (!isset($_SESSION['userData']) || $_SESSION['userData']['role'] !== 'organizer') {
    die('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
}

$approved_by = $_SESSION['userData']['username'];

// ดึงทีมที่ยังไม่อนุมัติ - แก้ไข Query ให้ใช้ team_id
$stmt = $conn->query("SELECT team_id, team_name, competition_type, coach_name, coach_phone, leader_school, created_at FROM teams WHERE is_approved = 0 ORDER BY created_at DESC");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อนุมัติทีมการแข่งขัน</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            padding: 20px;
        }
 
        .card {
        align-items: center;
        background-image: linear-gradient(144deg, #af40ff, #5b42f3 50%, #00ddeb);
        border: 0;
        border-radius: 8px;
        box-shadow: rgba(151, 65, 252, 0.2) 0 15px 30px -5px;
        box-sizing: border-box;
        color: #ffffff;
        display: flex;
        font-size: 18px;
        justify-content: center;
        line-height: 1em;
        max-width: 100%;
        min-width: 140px;
        padding: 3px;
        text-decoration: none;
        user-select: none;
        -webkit-user-select: none;
        touch-action: manipulation;
        white-space: nowrap;
        cursor: pointer;
        transition: all 0.3s;
        }

        .card:active,
        .card:hover {
        outline: 0;
        }

        .card span {
        background-color: rgb(5, 6, 45);
        padding: 16px 24px;
        border-radius: 6px;
        width: 100%;
        height: 100%;
        transition: 300ms;
        }

        .card:hover span {
        background: none;
        }

        .card:active {
        transform: scale(0.9);
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="1" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .header .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .main-content {
            padding: 30px;
        }

        .stats-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 2px solid #e9ecef;
        }

        .stat-item {
            display: flex;
            align-items: center;
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 1.5rem;
            margin-right: 10px;
            color: #3498db;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-left: 5px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #bdc3c7;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .team-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            margin-bottom: 25px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            border-color: #3498db;
        }

        .team-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .team-name {
            font-size: 1.4rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .team-name i {
            margin-right: 10px;
            color: #3498db;
        }

        .team-type {
            display: inline-block;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .team-details {
            padding: 25px;
        }

        .detail-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: background 0.3s ease;
        }

        .detail-row:hover {
            background: #e9ecef;
        }

        .detail-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 0.9rem;
        }

        .detail-content {
            flex: 1;
        }

        .detail-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }

        .detail-value {
            color: #5a6c7d;
            font-size: 1rem;
        }

        .approval-form {
            background: #f8f9fa;
            padding: 25px;
            border-top: 2px solid #e9ecef;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 0.95rem;
            resize: vertical;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            align-items: center;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 120px;
            justify-content: center;
            text-decoration: none;
        }

        .btn-approve {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }

        .btn-approve:hover {
            background: linear-gradient(135deg, #229954, #27ae60);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .btn-reject {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-reject:hover {
            background: linear-gradient(135deg, #c0392b, #a93226);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .created-date {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 10px;
            font-style: italic;
        }

        .debug-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .detail-row {
                flex-direction: column;
                text-align: center;
            }
            
            .detail-icon {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

            <button class="card" onclick="location.href='backend/organizer_dashboard.php'">
                <span class="text"><i class="fas fa-sign-out-alt"></i> กลับไปหน้าหลัก</span>
            </button>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-clipboard-check"></i> อนุมัติทีมการแข่งขัน</h1>
            <p class="subtitle">จัดการและอนุมัติทีมที่สมัครเข้าร่วมการแข่งขัน</p>
        </div>

        <div class="main-content">
            <div class="stats-bar">
                <div class="stat-item">
                    <i class="fas fa-users stat-icon"></i>
                    <span class="stat-number"><?php echo count($teams); ?></span>
                    <span class="stat-label">ทีมรอการอนุมัติ</span>
                </div>
            </div>

            <?php if (empty($teams)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>ไม่มีทีมที่รอการอนุมัติ</h3>
                    <p>ทีมทั้งหมดได้รับการอนุมัติแล้ว หรือยังไม่มีทีมสมัครเข้าร่วม</p>
                </div>
            <?php else: ?>
                <?php foreach ($teams as $team): ?>
                    <div class="team-card">
                        <div class="team-header">
                            <div class="team-name">
                                <i class="fas fa-flag"></i>
                                <?php echo htmlspecialchars($team['team_name']); ?>
                            </div>
                            <span class="team-type"><?php echo htmlspecialchars($team['competition_type']); ?></span>
                            <?php if (isset($team['created_at'])): ?>
                                <div class="created-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    สมัครเมื่อ <?php echo date('d/m/Y H:i', strtotime($team['created_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="team-details">
                            <div class="detail-row">
                                <div class="detail-icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">โค้ช</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($team['coach_name']); ?></div>
                                </div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">เบอร์โทรศัพท์</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($team['coach_phone']); ?></div>
                                </div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-icon">
                                    <i class="fas fa-school"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">โรงเรียน</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($team['leader_school']); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="approval-form">
                            <!-- Debug Info 
                            <div class="debug-info">
                                <strong>Debug:</strong> Team ID = <?php echo isset($team['team_id']) ? $team['team_id'] : 'ไม่พบ'; ?>
                            </div> -->

                            <form method="post" action="process_team_approval.php" onsubmit="showLoading(this)">
                                <!-- ใช้ team_id แทน id -->
                                <input type="hidden" name="team_id" value="<?php echo isset($team['team_id']) ? htmlspecialchars($team['team_id']) : ''; ?>">

                                <div class="form-group">
                                    <label for="rejection_reason_<?php echo isset($team['team_id']) ? htmlspecialchars($team['team_id']) : ''; ?>" class="form-label">
                                        <i class="fas fa-comment-alt"></i> เหตุผล (กรณีไม่อนุมัติ)
                                    </label>
                                    <textarea 
                                        name="rejection_reason" 
                                        id="rejection_reason_<?php echo isset($team['team_id']) ? htmlspecialchars($team['team_id']) : ''; ?>"
                                        class="form-textarea" 
                                        rows="3" 
                                        placeholder="ระบุเหตุผลหากไม่อนุมัติทีมนี้..."
                                    ></textarea>
                                </div>

                                <div class="button-group">
                                    <?php if (isset($team['team_id']) && $team['team_id'] > 0): ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-approve">
                                            ✔ อนุมัติ
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn btn-reject" onclick="return confirmReject()">
                                            ✘ ไม่อนุมัติ
                                        </button>

                                    <?php else: ?>
                                        <div style="color: red; font-weight: bold;">
                                            ข้อผิดพลาด: ไม่พบ Team ID - ไม่สามารถดำเนินการได้
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="loading">
                                    <div class="spinner"></div>
                                    <p>กำลังดำเนินการ...</p>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($_SESSION['approval_status'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: '<?php echo $_SESSION['approval_status'] === "approved" ? "อนุมัติทีมสำเร็จ!" : "ไม่อนุมัติทีมแล้ว"; ?>',
            text: 'ระบบได้บันทึกการดำเนินการเรียบร้อยแล้ว',
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#3085d6'
        });
    </script>
    <?php unset($_SESSION['approval_status']); ?>
    <?php endif; ?>

    <script>
        function confirmReject() {
            return confirm('คุณแน่ใจหรือไม่ว่าต้องการไม่อนุมัติทีมนี้?\n\nการกระทำนี้ไม่สามารถย้อนกลับได้');
        }

        document.querySelectorAll('button[name="action"], input[name="action"]').forEach(btn => {
        btn.addEventListener('click', function() {
            // ลบ class clicked ออกจากปุ่มอื่น ๆ
            document.querySelectorAll('button[name="action"], input[name="action"]').forEach(b => b.classList.remove('clicked'));
            this.classList.add('clicked');
        });
    });

    function showLoading(form) {
        const buttons = form.querySelectorAll('.btn');
        const loading = form.querySelector('.loading');

        // หาค่าของปุ่มที่ถูกกด
        const clicked = form.querySelector('.clicked');
        if (clicked && clicked.name === 'action') {
            // เพิ่ม hidden input เพื่อส่งค่า action ไปแน่นอน
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'action';
            input.value = clicked.value;
            form.appendChild(input);
        }

        buttons.forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = '0.6';
        });

        loading.style.display = 'block';
    }

        // เพิ่ม animation เมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.team-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>