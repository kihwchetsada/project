<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; // เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล

// ---- Query ข้อมูลสถิติต่างๆ ----

// 1. สถิติภาพรวม
$total_members = $conn->query("SELECT COUNT(*) FROM team_members")->fetchColumn();
$total_teams = $conn->query("SELECT COUNT(*) FROM teams")->fetchColumn();
$total_tournaments = $conn->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();

// 2. สถิติข้อมูลอายุ (คำนวณจาก `birthdate`)
$age_stats_stmt = $conn->prepare("
    SELECT
        CASE
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) < 15 THEN 'ต่ำกว่า 15 ปี'
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 15 AND 18 THEN '15-18 ปี'
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 19 AND 22 THEN '19-22 ปี'
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) > 22 THEN 'มากกว่า 22 ปี'
            ELSE 'ไม่ระบุ'
        END as age_group,
        COUNT(*) as count
    FROM team_members
    GROUP BY age_group
    ORDER BY age_group
");
$age_stats_stmt->execute();
$age_stats = $age_stats_stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. สถิติจำนวนทีมในแต่ละทัวร์นาเมนต์
$teams_per_tournament_stmt = $conn->prepare("
    SELECT t.tournament_name, COUNT(teams.team_id) as team_count
    FROM teams as teams
    JOIN tournaments as t ON teams.tournament_id = t.id
    GROUP BY t.tournament_name
    ORDER BY team_count DESC
");
$teams_per_tournament_stmt->execute();
$teams_per_tournament = $teams_per_tournament_stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. สถิติสถานะของทีม
$team_status_stmt = $conn->prepare("
    SELECT status, COUNT(*) as count
    FROM teams
    GROUP BY status
");
$team_status_stmt->execute();
$team_status = $team_status_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าสถิติ - RMUTI Esport Surin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: #667eea;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .page-subtitle {
            font-size: 1.2rem;
            font-weight: 300;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 1.5rem;
        }

        .stat-card h3 {
            color: #333;
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .charts-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .chart-container:hover {
            transform: translateY(-3px);
        }

        .chart-container h2 {
            color: #333;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 25px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .chart-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }

        .full-width-chart {
            grid-column: 1 / -1;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2.2rem;
            }

            .charts-section {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: 25px 20px;
            }

            .chart-container {
                padding: 25px 20px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .stat-card .number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="organizer_dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            กลับไปหน้าหลัก
        </a>

        <div class="page-header">
            <h1 class="page-title">📊 สถิติการแข่งขัน</h1>
            <p class="page-subtitle">ภาพรวมข้อมูลทัวร์นาเมนต์ RMUTI Esport Surin</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>ผู้เข้าร่วมทั้งหมด</h3>
                <div class="number"><?php echo number_format($total_members); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-friends"></i>
                </div>
                <h3>ทีมทั้งหมด</h3>
                <div class="number"><?php echo number_format($total_teams); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <h3>ทัวร์นาเมนต์</h3>
                <div class="number"><?php echo number_format($total_tournaments); ?></div>
            </div>
        </div>

        <div class="charts-section">
            <div class="chart-container">
                <h2>
                    <div class="chart-icon">
                        <i class="fas fa-birthday-cake"></i>
                    </div>
                    สัดส่วนผู้สมัครตามรุ่นอายุ
                </h2>
                <canvas id="ageChart"></canvas>
            </div>
            <div class="chart-container">
                <h2>
                    <div class="chart-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    สัดส่วนสถานะทีม
                </h2>
                <canvas id="teamStatusChart"></canvas>
            </div>
        </div>

        <div class="chart-container full-width-chart">
            <h2>
                <div class="chart-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                จำนวนทีมในแต่ละทัวร์นาเมนต์
            </h2>
            <canvas id="teamsPerTournamentChart"></canvas>
        </div>
    </div>

    <script>
    // Chart.js Configuration
    Chart.defaults.font.family = 'Sarabun';
    Chart.defaults.font.size = 14;

    // Color scheme
    const colors = {
        primary: ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe'],
        gradients: [
            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
            'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
            'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
        ]
    };

    // 1. Age Chart
    const ageCtx = document.getElementById('ageChart').getContext('2d');
    const ageData = <?php echo json_encode($age_stats); ?>;
    new Chart(ageCtx, {
        type: 'doughnut',
        data: {
            labels: ageData.map(item => item.age_group),
            datasets: [{
                data: ageData.map(item => item.count),
                backgroundColor: [
                    'rgba(102, 126, 234, 0.8)',
                    'rgba(118, 75, 162, 0.8)',
                    'rgba(240, 147, 251, 0.8)',
                    'rgba(245, 87, 108, 0.8)',
                    'rgba(79, 172, 254, 0.8)'
                ],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: {
                            size: 13
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderWidth: 0,
                    cornerRadius: 8
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeOutQuart'
            }
        }
    });

    // 2. Team Status Chart
    const teamStatusCtx = document.getElementById('teamStatusChart').getContext('2d');
    const teamStatusData = <?php echo json_encode($team_status); ?>;
    const statusLabelsThai = {
        'registered': 'ลงทะเบียน',
        'confirmed': 'ยืนยันแล้ว',
        'cancelled': 'ยกเลิก',
        'completed': 'เสร็จสิ้น'
    };

    new Chart(teamStatusCtx, {
        type: 'pie',
        data: {
            labels: teamStatusData.map(item => statusLabelsThai[item.status] || item.status),
            datasets: [{
                data: teamStatusData.map(item => item.count),
                backgroundColor: [
                    'rgba(102, 126, 234, 0.8)',
                    'rgba(67, 233, 123, 0.8)',
                    'rgba(245, 87, 108, 0.8)',
                    'rgba(247, 112, 154, 0.8)'
                ],
                borderWidth: 3,
                borderColor: '#fff',
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: {
                            size: 13
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderWidth: 0,
                    cornerRadius: 8
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeOutQuart'
            }
        }
    });

    // 3. Teams per Tournament Chart
    const teamsPerTournamentCtx = document.getElementById('teamsPerTournamentChart').getContext('2d');
    const teamsPerTournamentData = <?php echo json_encode($teams_per_tournament); ?>;

    new Chart(teamsPerTournamentCtx, {
        type: 'bar',
        data: {
            labels: teamsPerTournamentData.map(item => item.tournament_name),
            datasets: [{
                label: 'จำนวนทีม',
                data: teamsPerTournamentData.map(item => item.team_count),
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: 'rgba(234, 155, 102, 1)',
                borderWidth: 0,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderWidth: 0,
                    cornerRadius: 8
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeOutQuart'
            }
        }
    });
    </script>
</body>
</html>