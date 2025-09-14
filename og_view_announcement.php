<?php
session_start();

require 'db_connect.php';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset("utf8");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// ดึงข้อมูลทั้งหมด
$result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการประกาศ - Announcement Management</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
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
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .content {
            padding: 30px;
        }

        .actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
        }

        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #495057;
            padding: 18px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }

        .table td {
            padding: 16px;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: middle;
        }

        .table tr:hover {
            background: #f8f9fa;
            transition: background 0.3s ease;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .status-inactive {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }

        .priority-high {
            color: #dc3545;
            font-weight: 600;
        }

        .priority-medium {
            color: #fd7e14;
            font-weight: 600;
        }

        .priority-low {
            color: #28a745;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 14px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-view {
            background: #6c757d;
            color: white;
        }

        .btn-view:hover {
            background: #5a6268;
            transform: scale(1.05);
        }

        .btn-edit {
            background: #ffc107;
            color: #212529;
        }

        .btn-edit:hover {
            background: #e0a800;
            transform: scale(1.05);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: scale(1.05);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #495057;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 30px;
        }

        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            min-width: 120px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .content {
                padding: 20px;
            }

            .actions {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                justify-content: center;
            }

            .table-container {
                overflow-x: auto;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }

            .stats-bar {
                flex-direction: column;
            }

            .stat-card {
                min-width: auto;
            }
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #4CAF50;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Fade in animation for table rows */
        .table tr {
            animation: slideIn 0.5s ease-out;
            animation-fill-mode: both;
        }

        .table tr:nth-child(1) { animation-delay: 0.1s; }
        .table tr:nth-child(2) { animation-delay: 0.2s; }
        .table tr:nth-child(3) { animation-delay: 0.3s; }
        .table tr:nth-child(4) { animation-delay: 0.4s; }
        .table tr:nth-child(5) { animation-delay: 0.5s; }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-bullhorn"></i> จัดการประกาศ</h1>
            <p>ระบบจัดการประกาศและข้อมูลข่าวสาร</p>
        </div>

        <div class="content">
            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-card">
                    <div class="stat-number"><?= $result->num_rows ?></div>
                    <div class="stat-label">ประกาศทั้งหมด</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $activeCount = 0;
                        $result_copy = $conn->query("SELECT status FROM announcements WHERE status = 'active'");
                        echo $result_copy->num_rows;
                        ?>
                    </div>
                    <div class="stat-label">ประกาศที่ใช้งาน</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="actions">
                <a href="add_announcement.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    เพิ่มประกาศใหม่
                </a>
                <a href="backend/organizer_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    กลับไปหน้าหลัก
                </a>
            </div>

            <!-- Table -->
            <div class="table-container">
                <?php if ($result->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> ID</th>
                                <th><i class="fas fa-heading"></i> หัวข้อ</th>
                                <th><i class="fas fa-tags"></i> หมวดหมู่</th>
                                <th><i class="fas fa-exclamation-triangle"></i> ความสำคัญ</th>
                                <th><i class="fas fa-toggle-on"></i> สถานะ</th>
                                <th><i class="fas fa-calendar"></i> วันที่สร้าง</th>
                                <th><i class="fas fa-cogs"></i> การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Reset result pointer
                            $result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
                            while ($row = $result->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><strong>#<?= $row['id'] ?></strong></td>
                                    <td>
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                            <?= htmlspecialchars($row['title']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge" style="background: linear-gradient(135deg, #e3f2fd, #bbdefb); color: #0d47a1;">
                                            <?= htmlspecialchars($row['category']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $priorityClass = '';
                                        $priorityIcon = '';
                                        switch($row['priority']) {
                                            case 'สูง':
                                            case 'high':
                                                $priorityClass = 'priority-high';
                                                $priorityIcon = 'fas fa-arrow-up';
                                                break;
                                            case 'ปานกลาง':
                                            case 'medium':
                                                $priorityClass = 'priority-medium';
                                                $priorityIcon = 'fas fa-minus';
                                                break;
                                            case 'ต่ำ':
                                            case 'low':
                                                $priorityClass = 'priority-low';
                                                $priorityIcon = 'fas fa-arrow-down';
                                                break;
                                        }
                                        ?>
                                        <span class="<?= $priorityClass ?>">
                                            <i class="<?= $priorityIcon ?>"></i>
                                            <?= htmlspecialchars($row['priority']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'active'): ?>
                                            <span class="status-badge status-active">
                                                <i class="fas fa-check-circle"></i> ใช้งาน
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">
                                                <i class="fas fa-times-circle"></i> ปิด
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-clock" style="color: #6c757d;"></i>
                                        <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view_announcement.php?id=<?= $row['id'] ?>" class="btn-sm btn-view" title="ดูรายละเอียด">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_announcement.php?id=<?= $row['id'] ?>" class="btn-sm btn-edit" title="แก้ไข">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?= $row['id'] ?>)" class="btn-sm btn-delete" title="ลบ">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bullhorn"></i>
                        <h3>ยังไม่มีประกาศในระบบ</h3>
                        <p>เริ่มต้นสร้างประกาศแรกของคุณ</p>
                        <a href="add_announcement.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            สร้างประกาศใหม่
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Confirm delete function
        function confirmDelete(id) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบประกาศนี้?\n\nการดำเนินการนี้ไม่สามารถยกเลิกได้')) {
                // Show loading
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
                
                // Redirect to delete
                setTimeout(() => {
                    window.location.href = `delete_announcement.php?id=${id}`;
                }, 500);
            }
        }

        // Add loading state to buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!this.classList.contains('btn-delete')) {
                    const loading = document.createElement('span');
                    loading.className = 'loading';
                    this.appendChild(loading);
                }
            });
        });

        // Auto refresh stats
        setTimeout(() => {
            location.reload();
        }, 300000); // Refresh every 5 minutes
    </script>
</body>
</html>