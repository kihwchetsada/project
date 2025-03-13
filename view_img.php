<?php
// ตั้งค่า timezone เป็นประเทศไทย
date_default_timezone_set('Asia/Bangkok');

session_start();

$upload_dir = __DIR__ . '/uploads/';
$keys_dir = __DIR__ . '/keys/';
$teams_dir = __DIR__ . '/teams/';

// ฟังก์ชันถอดรหัสรูปภาพ
function decryptImage($encrypted_file, $file_id) {
    global $keys_dir;
    
    $key_file = $keys_dir . $file_id . '_key.txt';
    $iv_file = $keys_dir . $file_id . '_iv.txt';
    $tag_file = $keys_dir . $file_id . '_tag.txt';

    if (!file_exists($encrypted_file) || !file_exists($key_file) || !file_exists($iv_file) || !file_exists($tag_file)) {
        echo "❌ Key, IV หรือ Tag หายไป!<br>";
        return false;
    }

    // โหลด Key, IV, และ Tag
    $encryption_key = base64_decode(file_get_contents($key_file));
    $iv = base64_decode(file_get_contents($iv_file));
    $tag = base64_decode(file_get_contents($tag_file));

    if (!$encryption_key || !$iv || !$tag) {
        echo "❌ ข้อมูล Key, IV หรือ Tag ผิดพลาด!<br>";
        return false;
    }

    // โหลดข้อมูลไฟล์ที่เข้ารหัส
    $encrypted_data = file_get_contents($encrypted_file);

    // ถอดรหัสข้อมูล
    $decrypted_data = openssl_decrypt(
        $encrypted_data,
        'aes-256-gcm',
        $encryption_key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($decrypted_data === false) {
        echo "❌ OpenSSL Error: " . openssl_error_string() . "<br>";
    }

    return $decrypted_data;
}

// โหลดข้อมูลทีมทั้งหมด
$team_files = glob($teams_dir . '*.json');
$teams_data = [];
$log_message = "";
$selected_team = isset($_GET['team']) ? $_GET['team'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

if ($team_files) {
    foreach($team_files as $team_file) {
        $json_data = file_get_contents($team_file);
        $team = json_decode($json_data, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            // เพิ่มที่อยู่ไฟล์เพื่อใช้สำหรับการอ้างอิง
            $team['file_path'] = $team_file;
            $teams_data[] = $team;
        } else {
            $log_message .= "❌ JSON Error ในไฟล์ " . $team_file . ": " . json_last_error_msg() . "<br>";
        }
    }
    
    // เรียงลำดับทีมตามชื่อ
    usort($teams_data, function($a, $b) {
        return strcmp($a['team_name'], $b['team_name']);
    });
    
    $log_message .= "📂 โหลดไฟล์ทีมทั้งหมด " . count($teams_data) . " ทีม<br>";
} else {
    $log_message = "⚠️ ไม่พบไฟล์ทีมในไดเร็กทอรี " . $teams_dir;
}

// ค้นหาทีมตามชื่อ
$filtered_teams = $teams_data;
if (!empty($search_query)) {
    $filtered_teams = array_filter($teams_data, function($team) use ($search_query) {
        return stripos($team['team_name'], $search_query) !== false;
    });
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลทีมและรูปภาพ</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --error-color: #e74c3c;
            --bg-color: #f5f8fa;
            --card-bg: #ffffff;
            --text-color: #34495e;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Sarabun', 'Prompt', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            padding: 0;
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .search-container {
            margin-bottom: 2rem;
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .search-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 0 20px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: var(--transition);
        }

        .search-button:hover {
            background-color: var(--secondary-color);
        }

        .reset-button {
            background-color: #95a5a6;
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 0 20px;
            cursor: pointer;
            font-size: 1rem;
            transition: var(--transition);
        }

        .reset-button:hover {
            background-color: #7f8c8d;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.5rem;
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
        }

        .team-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .team-card {
            background-color: #f9f9f9;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border-left: 4px solid var(--primary-color);
            cursor: pointer;
            transition: var(--transition);
        }

        .team-card:hover {
            background-color: #f0f8ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .team-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }

        .team-card i {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }

        .member-count {
            color: #666;
            font-size: 0.9rem;
        }

        .member-details {
            background-color: #fcfcfc;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 1.5rem;
            border: 1px solid #eee;
            display: none; /* เริ่มต้นซ่อนรายละเอียดสมาชิก */
        }

        .member-details.active {
            display: block;
        }

        .member-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .member-name {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .member-name i {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }

        .id-card-image {
            max-width: 100%;
            height: auto;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 1rem;
            border: 1px solid #eee;
            transition: var(--transition);
        }

        .id-card-image:hover {
            transform: scale(1.02);
        }

        .error-message {
            background-color: #ffeaea;
            border-left: 4px solid var(--error-color);
            padding: 1rem;
            margin: 1rem 0;
            color: var(--error-color);
            border-radius: var(--border-radius);
        }

        .success-message {
            background-color: #eaffea;
            border-left: 4px solid var(--success-color);
            padding: 1rem;
            margin: 1rem 0;
            color: #2d7d2d;
            border-radius: var(--border-radius);
        }

        .log-container {
            background-color: #f5f5f5;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-top: 2rem;
            font-family: monospace;
            color: #555;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .back-to-teams {
            display: inline-block;
            margin-bottom: 1rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
        }

        .back-to-teams:hover {
            text-decoration: underline;
        }

        .search-results {
            margin-bottom: 1rem;
            padding: 0.5rem 1rem;
            background-color: #f0f8ff;
            border-radius: var(--border-radius);
            color: var(--secondary-color);
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem;
            color: #777;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header {
                padding: 1.5rem;
            }
            
            .card {
                padding: 1rem;
            }
            
            .member-card {
                padding: 1rem;
            }

            .team-list {
                grid-template-columns: 1fr;
            }

            .search-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> ระบบข้อมูลทีม</h1>
            <p>ระบบแสดงข้อมูลทีมและเอกสารประจำตัวของสมาชิก</p>
        </div>

        <?php if (!empty($log_message)): ?>
        <div class="log-container">
            <code><?php echo $log_message; ?></code>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="card-title"><i class="fas fa-search"></i> ค้นหาทีม</h2>
            <form action="" method="GET" class="search-container">
                <input type="text" name="search" class="search-input" placeholder="พิมพ์ชื่อทีมที่ต้องการค้นหา..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-button"><i class="fas fa-search"></i> ค้นหา</button>
                <?php if (!empty($search_query)): ?>
                <a href="?" class="reset-button"><i class="fas fa-times"></i> ล้าง</a>
                <?php endif; ?>
            </form>

            <?php if (!empty($search_query)): ?>
            <div class="search-results">
                <i class="fas fa-info-circle"></i> ผลการค้นหา "<?php echo htmlspecialchars($search_query); ?>": พบ <?php echo count($filtered_teams); ?> ทีม
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($selected_team)): ?>
            <?php
            // หาข้อมูลทีมที่เลือก
            $team_data = null;
            foreach ($teams_data as $team) {
                if ($team['file_path'] === $selected_team) {
                    $team_data = $team;
                    break;
                }
            }
            ?>

            <?php if ($team_data): ?>
            <div class="card">
                <a href="?" class="back-to-teams"><i class="fas fa-arrow-left"></i> กลับไปหน้ารายชื่อทีมทั้งหมด</a>
                
                <h2 class="card-title">ข้อมูลทีม: <?php echo htmlspecialchars($team_data['team_name']); ?></h2>
                
                <h3><i class="fas fa-user-friends"></i> สมาชิกทีม (<?php echo count($team_data['members']); ?> คน)</h3>
                
                <?php foreach ($team_data['members'] as $index => $member): ?>
                    <div class="member-card">
                        <h4 class="member-name">
                            <i class="fas fa-user-circle"></i> 
                            <?php echo htmlspecialchars($member['name']); ?> 
                            <small>(สมาชิกคนที่ <?php echo $index + 1; ?>)</small>
                        </h4>
                        
                        <?php if (!empty($member['id_card_image'])): ?>
                            <?php
                            $file_path = $upload_dir . $member['id_card_image'];
                            $file_id = pathinfo($file_path, PATHINFO_FILENAME);
                            $decrypted_image = decryptImage($file_path, $file_id);
                            ?>
                            
                            <?php if ($decrypted_image): ?>
                                <div class="image-container">
                                    <img class="id-card-image" src="data:image/jpeg;base64,<?php echo base64_encode($decrypted_image); ?>" alt="รูปบัตรประจำตัวของ <?php echo htmlspecialchars($member['name']); ?>">
                                </div>
                            <?php else: ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-triangle"></i> ไม่สามารถถอดรหัสภาพได้
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="error-message">
                                <i class="fas fa-image"></i> ไม่มีไฟล์รูปภาพสำหรับสมาชิกคนนี้
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="card">
                    <div class="empty-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <p class="error-message">ไม่พบข้อมูลทีมที่เลือก</p>
                        <a href="?" class="back-to-teams"><i class="fas fa-arrow-left"></i> กลับไปหน้ารายชื่อทีมทั้งหมด</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card">
                <h2 class="card-title"><i class="fas fa-list"></i> รายชื่อทีมทั้งหมด (<?php echo count($filtered_teams); ?> ทีม)</h2>
                
                <?php if (!empty($filtered_teams)): ?>
                    <div class="team-list">
                        <?php foreach($filtered_teams as $team): ?>
                            <a href="?team=<?php echo urlencode($team['file_path']); ?>" class="team-card">
                                <div class="team-name">
                                    <i class="fas fa-flag"></i> <?php echo htmlspecialchars($team['team_name']); ?>
                                </div>
                                <div class="member-count">
                                    <i class="fas fa-user-friends"></i> จำนวนสมาชิก: <?php echo count($team['members']); ?> คน
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p class="error-message">ไม่พบข้อมูลทีมที่ตรงกับการค้นหา</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>© <?php echo date('Y'); ?> ระบบดูข้อมูลทีมและรูปภาพ | ปรับปรุงล่าสุดเมื่อ: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>