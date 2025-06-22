<?php
require 'db.php'; // เชื่อมต่อฐานข้อมูล
require 'db_connect.php';

// ดึงข้อมูลเฉพาะทีมที่ได้รับการอนุมัติ
$sql = "SELECT * FROM teams WHERE status = 'approved'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายชื่อทีมที่อนุมัติแล้ว</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header .subtitle {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
        }

        .stats-card {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }

        .stats-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }

        .stats-icon {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stats-text h3 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .stats-text p {
            color: #666;
            font-size: 1rem;
        }

        .table-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            backdrop-filter: blur(20px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 20px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        th i {
            margin-right: 8px;
        }

        td {
            padding: 18px 15px;
            border-bottom: 1px solid #eee;
            color: #333;
            font-size: 0.95rem;
            transition: background-color 0.3s ease;
        }

        tr:hover td {
            background-color: #f8f9ff;
        }

        .team-name {
            font-weight: 600;
            color: #667eea;
        }

        .members-list {
            line-height: 1.6;
            max-width: 300px;
        }

        .advisor-name {
            color: #764ba2;
            font-weight: 500;
        }

        .contact-number {
            font-family: 'Courier New', monospace;
            background: #f0f2ff;
            padding: 5px 10px;
            border-radius: 15px;
            display: inline-block;
            color: #667eea;
            font-weight: 500;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-data i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-data h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #333;
        }

        .no-data p {
            font-size: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .stats-content {
                flex-direction: column;
                text-align: center;
            }
            
            table {
                font-size: 0.85rem;
            }
            
            th, td {
                padding: 12px 8px;
            }
            
            .members-list {
                max-width: 200px;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.8rem;
            }
            
            th, td {
                padding: 10px 5px;
                font-size: 0.8rem;
            }
            
            .contact-number {
                font-size: 0.8rem;
                padding: 3px 8px;
            }
        }

        /* Animation */
        .table-container {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stats-card {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><i class="fas fa-trophy"></i> รายชื่อทีมที่ได้รับการอนุมัติ</h1>
        <p class="subtitle">ทีมที่ผ่านการคัดเลือกและพร้อมเข้าร่วมการแข่งขัน</p>
    </div>

    <?php
    $team_count = ($result instanceof mysqli_result) ? $result->num_rows : 0;
    ?>
    
    <div class="stats-card">
        <div class="stats-content">
            <div class="stats-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stats-text">
                <h3><?= $team_count ?></h3>
                <p>ทีมที่ได้รับการอนุมัติทั้งหมด</p>
            </div>
        </div>
    </div>

    <div class="table-container">
        <?php if ($result instanceof mysqli_result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-flag"></i>ชื่อทีม</th>
                        <th><i class="fas fa-users"></i>สมาชิก</th>
                        <th><i class="fas fa-user-tie"></i>อาจารย์ที่ปรึกษา</th>
                        <th><i class="fas fa-phone"></i>เบอร์ติดต่อ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($team = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="team-name"><?= htmlspecialchars($team['team_name']) ?></td>
                            <td class="members-list"><?= nl2br(htmlspecialchars($team['members'])) ?></td>
                            <td class="advisor-name"><?= htmlspecialchars($team['advisor']) ?></td>
                            <td><span class="contact-number"><?= htmlspecialchars($team['contact_number']) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-inbox"></i>
                <h3>ยังไม่มีข้อมูล</h3>
                <p>ยังไม่มีทีมที่ได้รับการอนุมัติในระบบ</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>