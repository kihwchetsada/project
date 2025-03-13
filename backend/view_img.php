<?php
// ตั้งค่า timezone เป็นประเทศไทย
date_default_timezone_set('Asia/Bangkok');

// สคริปต์สำหรับถอดรหัสและแสดงรูปภาพ
session_start();

// กำหนดตำแหน่งที่เก็บไฟล์
$upload_dir = __DIR__ . '../uploads/..';
$keys_dir = __DIR__ . '../keys/..';
$teams_dir = __DIR__ . '../teams/..';

// ฟังก์ชันถอดรหัสรูปภาพ
function decryptImage($encrypted_file, $file_id) {
    global $keys_dir;
    
    $key_file = $keys_dir . $file_id . '_key.txt';
    $iv_file = $keys_dir . $file_id . '_iv.txt';
    $tag_file = $keys_dir . $file_id . '_tag.txt';

    if (!file_exists($encrypted_file) || !file_exists($key_file) || !file_exists($iv_file) || !file_exists($tag_file)) {
        return false;
    }

    // อ่าน Key, IV และ Tag
    $encryption_key = base64_decode(file_get_contents($key_file));
    $iv = base64_decode(file_get_contents($iv_file));
    $tag = base64_decode(file_get_contents($tag_file));

    // อ่านข้อมูลที่เข้ารหัสแล้ว
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

    return $decrypted_data !== false ? $decrypted_data : false;
}

// โหลดข้อมูลทีมล่าสุด
$team_files = glob($teams_dir . '../teams/*.json');
$team_data = null;

if ($team_files) {
    $latest_team_file = end($team_files);
    $team_data = json_decode(file_get_contents($latest_team_file), true);
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ดูข้อมูลทีมและรูปภาพ</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 900px; margin: auto; }
        .team-info, .member-info { margin-bottom: 20px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        img { max-width: 200px; display: block; margin-top: 10px; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>ข้อมูลทีม</h1>

        <?php if ($team_data): ?>
            <div class="team-info">
                <p><strong>ชื่อทีม:</strong> <?php echo htmlspecialchars($team_data['team_name']); ?></p>
                <p><strong>ประเภทการแข่งขัน:</strong> <?php echo htmlspecialchars($team_data['competition_type']); ?></p>
                <p><strong>โค้ช:</strong> <?php echo htmlspecialchars($team_data['coach']['name']); ?> (<?php echo htmlspecialchars($team_data['coach']['phone']); ?>)</p>
            </div>

            <h2>สมาชิกในทีม</h2>
            <?php foreach ($team_data['members'] as $member): ?>
                <div class="member-info">
                    <p><strong>ชื่อ:</strong> <?php echo htmlspecialchars($member['name']); ?></p>
                    <p><strong>ชื่อในเกม:</strong> <?php echo htmlspecialchars($member['game_name']); ?></p>
                    <p><strong>ตำแหน่ง:</strong> <?php echo htmlspecialchars($member['position']); ?></p>

                    <?php if (!empty($member['id_card_image'])): ?>
                        <?php
                        $file_path = __DIR__ . '../' . $member['id_card_image'];
                        $file_id = pathinfo($file_path, PATHINFO_FILENAME);
                        $decrypted_image = decryptImage($file_path, $file_id);
                        ?>
                        <?php if ($decrypted_image): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($decrypted_image); ?>" 
                                 alt="บัตรประชาชนของ <?php echo htmlspecialchars($member['name']); ?>">
                        <?php else: ?>
                            <p class="error">ไม่สามารถถอดรหัสภาพได้</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <p class="error">ไม่พบข้อมูลทีม</p>
        <?php endif; ?>
    </div>
</body>
</html>
