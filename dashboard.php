<?php
session_start();

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// สมมติข้อมูลสำหรับตารางการแข่งขัน
$userData = [
    'username' => $_SESSION['username'],
    'lastLogin' => date('d/m/Y H:i', time()),
    'role' => 'ผู้จัดการแข่งขัน'
];

// ข้อมูลตารางการแข่งขัน
$tournaments = [
    // รอบคัดเลือก (จบไปแล้ว)
    [
        'round' => 'รอบคัดเลือก',
        'status' => 'completed',
        'matches' => [
            [
                'match_id' => 'Q-001',
                'team1' => 'Team Phoenix',
                'team2' => 'Team Dragon',
                'date' => '10/02/2025',
                'time' => '13:00',
                'venue' => 'ออนไลน์',
                'result' => 'Team Phoenix ชนะ 2-1',
                'stream' => 'https://example.com/watch/Q-001'
            ],
            [
                'match_id' => 'Q-002',
                'team1' => 'Team Eagle',
                'team2' => 'Team Warrior',
                'date' => '10/02/2025',
                'time' => '15:00',
                'venue' => 'ออนไลน์',
                'result' => 'Team Warrior ชนะ 2-0',
                'stream' => 'https://example.com/watch/Q-002'
            ],
            [
                'match_id' => 'Q-003',
                'team1' => 'Team Ninja',
                'team2' => 'Team StarLight',
                'date' => '11/02/2025',
                'time' => '13:00',
                'venue' => 'ออนไลน์',
                'result' => 'Team StarLight ชนะ 2-1',
                'stream' => 'https://example.com/watch/Q-003'
            ],
            [
                'match_id' => 'Q-004',
                'team1' => 'Team Galaxy',
                'team2' => 'Team Hunter',
                'date' => '11/02/2025',
                'time' => '15:00',
                'venue' => 'ออนไลน์',
                'result' => 'Team Galaxy ชนะ 2-0',
                'stream' => 'https://example.com/watch/Q-004'
            ],
        ]
    ],
    
    // รอบ 16 ทีมสุดท้าย (กำลังจะเล่น)
    [
        'round' => 'รอบ 16 ทีมสุดท้าย',
        'status' => 'upcoming',
        'matches' => [
            [
                'match_id' => 'R16-001',
                'team1' => 'Team Phoenix',
                'team2' => 'Team Alpha',
                'date' => '28/03/2025',
                'time' => '13:00',
                'venue' => 'ศูนย์กีฬาอีสปอร์ต กรุงเทพฯ',
                'result' => 'รอการแข่งขัน',
                'stream' => 'https://example.com/watch/R16-001'
            ],
            [
                'match_id' => 'R16-002',
                'team1' => 'Team Warrior',
                'team2' => 'Team BlueStorm',
                'date' => '28/03/2025',
                'time' => '15:00',
                'venue' => 'ศูนย์กีฬาอีสปอร์ต กรุงเทพฯ',
                'result' => 'รอการแข่งขัน',
                'stream' => 'https://example.com/watch/R16-002'
            ],
            [
                'match_id' => 'R16-003',
                'team1' => 'Team StarLight',
                'team2' => 'Team Inferno',
                'date' => '29/03/2025',
                'time' => '13:00',
                'venue' => 'ศูนย์กีฬาอีสปอร์ต กรุงเทพฯ',
                'result' => 'รอการแข่งขัน',
                'stream' => 'https://example.com/watch/R16-003'
            ],
            [
                'match_id' => 'R16-004',
                'team1' => 'Team Galaxy',
                'team2' => 'Team Thunder',
                'date' => '29/03/2025',
                'time' => '15:00',
                'venue' => 'ศูนย์กีฬาอีสปอร์ต กรุงเทพฯ',
                'result' => 'รอการแข่งขัน',
                'stream' => 'https://example.com/watch/R16-004'
            ],
        ]
    ],
    
    // รอบ 8 ทีมสุดท้าย
    [
        'round' => 'รอบ 8 ทีมสุดท้าย',
        'status' => 'pending',
        'matches' => [
            [
                'match_id' => 'QF-001',
                'team1' => 'TBD',
                'team2' => 'TBD',
                'date' => '05/04/2025',
                'time' => '13:00',
                'venue' => 'ศูนย์กีฬาอีสปอร์ต กรุงเทพฯ',
                'result' => 'รอการแข่งขัน',
                'stream' => 'https://example.com/watch/QF-001'
            ],
            [
                'match_id' => 'QF-002',
                'team1' => 'TBD',
                'team2' => 'TBD',
                'date' => '05/04/2025',
                'time' => '15:00',
                'venue' => 'ศูนย์กีฬาอีสปอร์ต กรุงเทพฯ',
                'result' => 'รอการแข่งขัน',
                'stream' => 'https://example.com/watch/QF-002'
            ],
        ]
    ],
    
    // รอบรองชนะเลิศ
    [
        'round' => 'รอบรองชนะเลิศ',
        'status' => 'pending',
        'matches' => [
            [
                'match_id' => 'SF-001',
                'team1' => 'TBD',
                'team2' => 'TBD',
                'date' => '12/04/2025',
                'time' => '13:00',
                'venue' => 'ศูนย์กีฬาอีสปอร์ต กรุงเทพฯ',
                'result' => 'รอการแข่งขัน',
                'stream' => 'https://example.com/watch/SF-001'
            ],
        ]
    ],
    
    // รอบชิงชนะเลิศ
    [
        'round' => 'รอบชิงชนะเลิศ',
        'status' => 'pending',
        'matches' => [
            [
                'match_id' => 'F-001',
                'team1' => 'TBD',
                'team2' => 'TBD',
                'date' => '19/04/2025',
                'time' => '14:00',
                'venue' => 'อิมแพ็ค อารีน่า เมืองทองธานี',
                'result' => 'รอการแข่งขัน',
                'stream' => 'https://example.com/watch/F-001'
            ],
        ]
    ],
];

// ฟังก์ชันสำหรับล็อกเอาท์
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>ตารางการแข่งขัน | ระบบจัดการการแข่งขัน ROV</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        /* เพิ่มสไตล์เฉพาะสำหรับหน้าตารางการแข่งขัน */
        .schedule-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-button {
            padding: 8px 16px;
            border-radius: 6px;
            background-color: #f0f0f0;
            border: none;
            cursor: pointer;
            font-family: 'Kanit', sans-serif;
            font-weight: 400;
            transition: all 0.3s;
        }
        
        .filter-button.active {
            background-color: #4A90E2;
            color: white;
        }
        
        .schedule-round {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .round-header {
            padding: 15px;
            background-color: #f5f5f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .round-title {
            font-size: 18px;
            font-weight: 500;
        }
        
        .round-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-completed {
            background-color: #28a745;
            color: white;
        }
        
        .badge-upcoming {
            background-color: #fd7e14;
            color: white;
        }
        
        .badge-pending {
            background-color: #6c757d;
            color: white;
        }
        
        .match-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .match-table th {
            background-color: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 500;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .match-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .match-table tr:last-child td {
            border-bottom: none;
        }
        
        .match-teams {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .team-vs {
            margin: 0 10px;
            color: #888;
            font-weight: 500;
        }
        
        .match-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-family: 'Kanit', sans-serif;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-edit {
            background-color: #4A90E2;
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #3a7bc8;
        }
        
        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
        }
        
        .btn-watch {
            background-color: #9b59b6;
            color: white;
        }
        
        .btn-watch:hover {
            background-color: #8e44ad;
        }
        
        .btn-add-match {
            background-color: #4A90E2;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-family: 'Kanit', sans-serif;
            font-weight: 400;
            margin-left: auto;
        }
        
        .btn-add-round {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Kanit', sans-serif;
            font-weight: 500;
            margin: 20px 0;
        }
        
        .empty-round {
            padding: 30px;
            text-align: center;
            color: #777;
        }
        
        @media (max-width: 768px) {
            .match-table {
                display: block;
                overflow-x: auto;
            }
            
            .round-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .match-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-trophy"></i>
                <span>ROV Tournament</span>
            </div>
            <button class="mobile-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="sidebar-menu">
            <ul>
                <li>
                    <a href="dashboard.php"><i class="fas fa-home"></i><span>หน้าหลัก</span></a>
                </li>
                <li>
                    <a href="teams.php"><i class="fas fa-users"></i><span>จัดการทีม</span></a>
                </li>
                <li class="active">
                    <a href="schedule.php"><i class="fas fa-calendar-days"></i><span>ตารางการแข่งขัน</span></a>
                </li>
                <li>
                    <a href="results.php"><i class="fas fa-ranking-star"></i><span>ผลการแข่งขัน</span></a>
                </li>
                <li>
                    <a href="stats.php"><i class="fas fa-chart-bar"></i><span>สถิติ</span></a>
                </li>
                <li>
                    <a href="settings.php"><i class="fas fa-cog"></i><span>ตั้งค่า</span></a>
                </li>
                <li>
                    <a href="?logout=1"><i class="fas fa-sign-out-alt"></i><span>ออกจากระบบ</span></a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <div class="top-navbar">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="ค้นหาการแข่งขัน...">
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="badge">5</span>
                </div>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($userData['username']); ?></span>
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <div class="welcome-header">
                <h2>ตารางการแข่งขัน ROV Tournament</h2>
                <p>จัดการกำหนดการแข่งขันทั้งหมดของทัวร์นาเมนต์</p>
            </div>

            <!-- ส่วนการกรอง -->
            <div class="schedule-filters">
                <button class="filter-button active" data-filter="all">ทั้งหมด</button>
                <button class="filter-button" data-filter="completed">เสร็จสิ้นแล้ว</button>
                <button class="filter-button" data-filter="upcoming">กำลังจะมาถึง</button>
                <button class="filter-button" data-filter="pending">รอดำเนินการ</button>
                
                <button class="btn-add-round">
                    <i class="fas fa-plus"></i> เพิ่มรอบการแข่งขันใหม่
                </button>
            </div>

            <!-- ตารางการแข่งขันตามรอบ -->
            <div class="schedule-container">
                <?php foreach ($tournaments as $tournament): ?>
                <div class="schedule-round" data-status="<?php echo $tournament['status']; ?>">
                    <div class="round-header">
                        <div class="round-title"><?php echo htmlspecialchars($tournament['round']); ?></div>
                        <div>
                            <span class="round-badge badge-<?php echo $tournament['status']; ?>">
                                <?php 
                                if ($tournament['status'] == 'completed') echo 'เสร็จสิ้น';
                                elseif ($tournament['status'] == 'upcoming') echo 'กำลังจะมาถึง';
                                else echo 'รอดำเนินการ';
                                ?>
                            </span>
                            <button class="btn-add-match">
                                <i class="fas fa-plus"></i> เพิ่มการแข่งขัน
                            </button>
                        </div>
                    </div>
                    
                    <?php if (empty($tournament['matches'])): ?>
                    <div class="empty-round">
                        <p>ยังไม่มีการแข่งขันในรอบนี้</p>
                    </div>
                    <?php else: ?>
                    <table class="match-table">
                        <thead>
                            <tr>
                                <th>รหัสแมตช์</th>
                                <th>ทีม</th>
                                <th>วันที่ & เวลา</th>
                                <th>สถานที่</th>
                                <th>ผลการแข่งขัน</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tournament['matches'] as $match): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($match['match_id']); ?></td>
                                <td>
                                    <div class="match-teams">
                                        <span><?php echo htmlspecialchars($match['team1']); ?></span>
                                        <span class="team-vs">VS</span>
                                        <span><?php echo htmlspecialchars($match['team2']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($match['date']); ?> | <?php echo htmlspecialchars($match['time']); ?></td>
                                <td><?php echo htmlspecialchars($match['venue']); ?></td>
                                <td><?php echo htmlspecialchars($match['result']); ?></td>
                                <td>
                                    <div class="match-actions">
                                        <a href="#" class="btn-action btn-edit">
                                            <i class="fas fa-edit"></i> แก้ไข
                                        </a>
                                        <?php if ($tournament['status'] == 'completed'): ?>
                                        <a href="<?php echo htmlspecialchars($match['stream']); ?>" target="_blank" class="btn-action btn-watch">
                                            <i class="fas fa-play"></i> ดูย้อนหลัง
                                        </a>
                                        <?php endif; ?>
                                        <a href="#" class="btn-action btn-delete">
                                            <i class="fas fa-trash"></i> ลบ
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Footer -->
            <div class="dashboard-footer">
                <p>&copy; <?php echo date('Y'); ?> ระบบจัดการการแข่งขัน ROV. สงวนลิขสิทธิ์</p>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('sidebar-active');
        });

        // ปิด sidebar เมื่อคลิกที่เนื้อหาหลักในโหมดมือถือ
        document.querySelector('.main-content').addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                document.querySelector('.sidebar').classList.remove('sidebar-active');
            }
        });

        // ฟังก์ชันสำหรับกรองรายการแข่งขัน
        document.querySelectorAll('.filter-button').forEach(button => {
            button.addEventListener('click', function() {
                // เอาคลาส active ออกจากปุ่มทั้งหมด
                document.querySelectorAll('.filter-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // เพิ่มคลาส active ให้กับปุ่มที่ถูกคลิก
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                const rounds = document.querySelectorAll('.schedule-round');
                
                rounds.forEach(round => {
                    if (filter === 'all' || round.getAttribute('data-status') === filter) {
                        round.style.display = 'block';
                    } else {
                        round.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>