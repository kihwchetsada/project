<?php
// รวมโค้ด PHP จากไฟล์เดิม
include 'upload_script.php';

// เพิ่มโค้ดสำหรับการจัดการข้อมูลทีม
$team_success = false;
$team_error = '';

// ตรวจสอบการส่งฟอร์มข้อมูลทีม
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_team'])) {
    // ตรวจสอบ CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $team_error = 'CSRF token ไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
    } else {
        // ตรวจสอบข้อมูลทีม
        $team_name = trim($_POST['team_name'] ?? '');
        $leader_name = trim($_POST['leader_name'] ?? '');
        $leader_phone = trim($_POST['leader_phone'] ?? '');
        $leader_school = trim($_POST['leader_school'] ?? '');
        
        if (empty($team_name) || empty($leader_name) || empty($leader_phone) || empty($leader_school)) {
            $team_error = 'กรุณากรอกข้อมูลหัวหน้าทีมให้ครบถ้วน';
        } else {
            // นับจำนวนสมาชิกที่มีข้อมูล
            $member_count = 0;
            for ($i = 1; $i <= 8; $i++) {
                if (!empty($_POST["member_name_$i"])) {
                    $member_count++;
                }
            }
            
            if ($member_count < 5) {
                $team_error = 'กรุณากรอกข้อมูลสมาชิกอย่างน้อย 5 คน';
            } else {
                // บันทึกข้อมูลทีม
                $team_data = [
                    'team_name' => $team_name,
                    'leader' => [
                        'name' => $leader_name,
                        'phone' => $leader_phone,
                        'school' => $leader_school
                    ],
                    'members' => []
                ];
                
                // รวบรวมข้อมูลสมาชิก
                for ($i = 1; $i <= 8; $i++) {
                    if (!empty($_POST["member_name_$i"])) {
                        // จัดการการอัปโหลดรูปภาพบัตรประชาชน
                        $id_card_image = '';
                        $file_field = "member_id_card_$i";
                        
                        if (!empty($_FILES[$file_field]['name'])) {
                            // โค้ดการจัดการไฟล์รูปภาพบัตรประชาชนคล้ายกับการอัปโหลดภาพเดิม
                            // (สามารถใช้ฟังก์ชันจาก upload_script.php หรือปรับให้เหมาะสม)
                            $id_card_image = 'id_card_' . $i . '_' . time() . '.jpg';
                            // บันทึกไฟล์และการเข้ารหัสถ้าจำเป็น
                        }
                        
                        $team_data['members'][] = [
                            'name' => $_POST["member_name_$i"],
                            'game_name' => $_POST["member_game_name_$i"],
                            'age' => $_POST["member_age_$i"],
                            'position' => $_POST["member_position_$i"],
                            'phone' => $_POST["member_phone_$i"],
                            'id_card_image' => $id_card_image
                        ];
                    }
                }
                
                // บันทึกข้อมูลทีมลงในไฟล์หรือฐานข้อมูล (ตัวอย่างเป็น JSON)
                $team_data_file = 'teams/' . $team_name . '_' . time() . '.json';
                if (!is_dir('teams')) {
                    mkdir('teams', 0755, true);
                }
                
                if (file_put_contents($team_data_file, json_encode($team_data, JSON_PRETTY_PRINT))) {
                    $team_success = true;
                } else {
                    $team_error = 'ไม่สามารถบันทึกข้อมูลทีมได้ กรุณาลองใหม่อีกครั้ง';
                }
            }
        }
    }
}

// สร้าง CSRF token ถ้ายังไม่มี
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนทีม</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link rel="stylesheet" href="../css/img.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #f72585;
            --background-color: #f8f9fa;
            --text-color: #333;
            --border-color: #d1d5db;
            --success-color: #10b981;
            --error-color: #ef4444;
            --hover-color: #3b82f6;
        }
        
        body {
            font-family: 'Kanit', 'Prompt', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
            margin: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        h1 {
            text-align: center;
            color: var(--secondary-color);
            margin-bottom: 30px;
            font-size: 2.5rem;
            position: relative;
        }
        
        h1:after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background: var(--accent-color);
            margin: 10px auto 0;
            border-radius: 2px;
        }
        
        h2 {
            color: var(--primary-color);
            border-left: 5px solid var(--accent-color);
            padding-left: 15px;
            margin-top: 30px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(67, 97, 238, 0.05);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: -10px;
        }
        
        .form-group {
            flex: 1 0 250px;
            margin: 10px;
        }
        
        .form-group.full-width {
            flex: 1 0 100%;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        input:focus, select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.25);
            outline: none;
        }
        
        .member-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .member-number {
            position: absolute;
            top: -10px;
            left: -10px;
            background: var(--accent-color);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .member-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .member-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .required-mark {
            color: var(--error-color);
            margin-left: 3px;
        }
        
        .member-toggle {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .member-toggle:hover {
            background: var(--hover-color);
        }
        
        .file-upload {
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload:hover {
            border-color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .file-upload i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .file-label {
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .file-upload input[type="file"] {
            display: none;
        }
        
        .file-name {
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--primary-color);
            word-break: break-all;
        }
        
        .submit-button {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            border-radius: 8px;
            cursor: pointer;
            display: block;
            margin: 30px auto 0;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .submit-button:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .submit-button i {
            margin-right: 10px;
        }
        
        .success, .error {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }
        
        .error {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--error-color);
            color: var(--error-color);
        }
        
        .success i, .error i {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        
        .info-text {
            font-size: 0.9rem;
            color: #666;
            margin: 5px 0 15px;
        }
        
        .toggle-members {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 1rem;
            border-radius: 8px;
            cursor: pointer;
            margin: 20px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .toggle-members:hover {
            background: var(--hover-color);
        }
        
        .toggle-members i {
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .form-group {
                flex: 1 0 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ลงทะเบียนทีมแข่งขัน</h1>
        
        <?php if ($team_success): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <span>ลงทะเบียนทีมสำเร็จ! ทีมของคุณได้รับการบันทึกแล้ว</span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($team_error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <span>เกิดข้อผิดพลาด: <?php echo htmlspecialchars($team_error); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($encryption_success): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <span>อัปโหลดและเข้ารหัสรูปภาพสำเร็จ!</span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($encryption_error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <span>เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ: <?php echo htmlspecialchars($encryption_error); ?></span>
            </div>
        <?php endif; ?>
        
        <form action="" method="post" enctype="multipart/form-data" id="teamRegistrationForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <!-- ส่วนข้อมูลทีมและหัวหน้าทีม -->
            <div class="form-section">
                <h2><i class="fas fa-users-crown"></i> ข้อมูลทีมและหัวหน้าทีม</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="team_name">ชื่อทีม <span class="required-mark">*</span></label>
                        <input type="text" id="team_name" name="team_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="leader_name">ชื่อ-สกุล (หัวหน้าทีม) <span class="required-mark">*</span></label>
                        <input type="text" id="leader_name" name="leader_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="leader_phone">เบอร์โทรศัพท์ <span class="required-mark">*</span></label>
                        <input type="tel" id="leader_phone" name="leader_phone" pattern="[0-9]{10}" required>
                        <p class="info-text">กรุณากรอกหมายเลขโทรศัพท์ 10 หลัก</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="leader_school">สังกัด/โรงเรียน <span class="required-mark">*</span></label>
                        <input type="text" id="leader_school" name="leader_school" required>
                    </div>
                </div>
            </div>
            
            <!-- ส่วนข้อมูลสมาชิกในทีม -->
            <div class="form-section">
                <h2><i class="fas fa-users"></i> ข้อมูลสมาชิกในทีม (อย่างน้อย 5 คน สูงสุด 8 คน)</h2>
                <p class="info-text">กรุณากรอกข้อมูลสมาชิกทีมอย่างน้อย 5 คน สามารถเพิ่มได้สูงสุด 8 คน</p>
                
                <!-- สมาชิกคนที่ 1 (ต้องมี) -->
                <div class="member-card">
                    <div class="member-number">1</div>
                    <div class="member-header">
                        <div class="member-title">สมาชิกคนที่ 1</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="member_name_1">ชื่อ-สกุล <span class="required-mark">*</span></label>
                            <input type="text" id="member_name_1" name="member_name_1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="member_game_name_1">ชื่อในเกม <span class="required-mark">*</span></label>
                            <input type="text" id="member_game_name_1" name="member_game_name_1" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="member_age_1">อายุ <span class="required-mark">*</span></label>
                            <input type="number" id="member_age_1" name="member_age_1" min="7" max="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="member_position_1">ตำแหน่งที่เล่น <span class="required-mark">*</span></label>
                            <input type="text" id="member_position_1" name="member_position_1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="member_phone_1">เบอร์โทรศัพท์ <span class="required-mark">*</span></label>
                            <input type="tel" id="member_phone_1" name="member_phone_1" pattern="[0-9]{10}" required>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="member_id_card_1">รูปภาพบัตรประชาชน <span class="required-mark">*</span></label>
                        <div class="file-upload" id="dropZone_1">
                            <i class="fas fa-id-card"></i>
                            <div class="file-label">ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</div>
                            <input type="file" name="member_id_card_1" id="member_id_card_1" accept="image/jpeg,image/png,image/gif" required>
                            <div class="file-name" id="fileName_1"></div>
                        </div>
                        <p class="info-text">รองรับไฟล์: JPEG, PNG, GIF (สูงสุด 5MB)</p>
                    </div>
                </div>
                
                <!-- สมาชิกคนที่ 2-5 (ต้องมี) -->
                <?php for ($i = 2; $i <= 5; $i++) : ?>
                <div class="member-card">
                    <div class="member-number"><?php echo $i; ?></div>
                    <div class="member-header">
                        <div class="member-title">สมาชิกคนที่ <?php echo $i; ?></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="member_name_<?php echo $i; ?>">ชื่อ-สกุล <span class="required-mark">*</span></label>
                            <input type="text" id="member_name_<?php echo $i; ?>" name="member_name_<?php echo $i; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="member_game_name_<?php echo $i; ?>">ชื่อในเกม <span class="required-mark">*</span></label>
                            <input type="text" id="member_game_name_<?php echo $i; ?>" name="member_game_name_<?php echo $i; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="member_age_<?php echo $i; ?>">อายุ <span class="required-mark">*</span></label>
                            <input type="number" id="member_age_<?php echo $i; ?>" name="member_age_<?php echo $i; ?>" min="7" max="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="member_position_<?php echo $i; ?>">ตำแหน่งที่เล่น <span class="required-mark">*</span></label>
                            <input type="text" id="member_position_<?php echo $i; ?>" name="member_position_<?php echo $i; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="member_phone_<?php echo $i; ?>">เบอร์โทรศัพท์ <span class="required-mark">*</span></label>
                            <input type="tel" id="member_phone_<?php echo $i; ?>" name="member_phone_<?php echo $i; ?>" pattern="[0-9]{10}" required>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="member_id_card_<?php echo $i; ?>">รูปภาพบัตรประชาชน <span class="required-mark">*</span></label>
                        <div class="file-upload" id="dropZone_<?php echo $i; ?>">
                            <i class="fas fa-id-card"></i>
                            <div class="file-label">ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</div>
                            <input type="file" name="member_id_card_<?php echo $i; ?>" id="member_id_card_<?php echo $i; ?>" accept="image/jpeg,image/png,image/gif" required>
                            <div class="file-name" id="fileName_<?php echo $i; ?>"></div>
                        </div>
                        <p class="info-text">รองรับไฟล์: JPEG, PNG, GIF (สูงสุด 5MB)</p>
                    </div>
                </div>
                <?php endfor; ?>
                
                <!-- สมาชิกเพิ่มเติม (ไม่บังคับ) -->
                <button type="button" id="toggleAdditionalMembers" class="toggle-members">
                    <i class="fas fa-plus-circle"></i> เพิ่มสมาชิก (ไม่บังคับ)
                </button>
                
                <div id="additionalMembers" style="display: none;">
                    <?php for ($i = 6; $i <= 8; $i++) : ?>
                    <div class="member-card">
                        <div class="member-number"><?php echo $i; ?></div>
                        <div class="member-header">
                            <div class="member-title">สมาชิกคนที่ <?php echo $i; ?> (ไม่บังคับ)</div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="member_name_<?php echo $i; ?>">ชื่อ-สกุล</label>
                                <input type="text" id="member_name_<?php echo $i; ?>" name="member_name_<?php echo $i; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="member_game_name_<?php echo $i; ?>">ชื่อในเกม</label>
                                <input type="text" id="member_game_name_<?php echo $i; ?>" name="member_game_name_<?php echo $i; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="member_age_<?php echo $i; ?>">อายุ</label>
                                <input type="number" id="member_age_<?php echo $i; ?>" name="member_age_<?php echo $i; ?>" min="7" max="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="member_position_<?php echo $i; ?>">ตำแหน่งที่เล่น</label>
                                <input type="text" id="member_position_<?php echo $i; ?>" name="member_position_<?php echo $i; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="member_phone_<?php echo $i; ?>">เบอร์โทรศัพท์</label>
                                <input type="tel" id="member_phone_<?php echo $i; ?>" name="member_phone_<?php echo $i; ?>" pattern="[0-9]{10}">
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="member_id_card_<?php echo $i; ?>">รูปภาพบัตรประชาชน</label>
                            <div class="file-upload" id="dropZone_<?php echo $i; ?>">
                                <i class="fas fa-id-card"></i>
                                <div class="file-label">ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</div>
                                <input type="file" name="member_id_card_<?php echo $i; ?>" id="member_id_card_<?php echo $i; ?>" accept="image/jpeg,image/png,image/gif">
                                <div class="file-name" id="fileName_<?php echo $i; ?>"></div>
                            </div>
                            <p class="info-text">รองรับไฟล์: JPEG, PNG, GIF (สูงสุด 5MB)</p>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <button type="submit" name="submit_team" class="submit-button">
                <i class="fas fa-paper-plane"></i> ส่งใบสมัคร
            </button>
        </form>
        
        <!-- ส่วนแสดงข้อมูลดีบัก (ซ่อนไว้ในกรณีปกติ) -->
        <div class="debug-info" id="debugInfo" style="display: none;">
            <h3>ข้อมูลการตรวจสอบ:</h3>
            <ul>
                <?php 
                // ตรวจสอบการตั้งค่า PHP
                $upload_max = ini_get('upload_max_filesize');
                $post_max = ini_get('post_max_size');
                $file_uploads = ini_get('file_uploads');
                ?>
                <li>upload_max_filesize: <?php echo $upload_max; ?></li>
                <li>post_max_size: <?php echo $post_max; ?></li>
                <li>file_uploads: <?php echo $file_uploads ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?></li>
                <li>อัพโหลดดิเร็กทอรี่: <?php echo is_writable('uploads') ? 'เขียนได้' : 'เขียนไม่ได้'; ?></li>
            </ul>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // สคริปต์สำหรับปุ่มแสดง/ซ่อนสมาชิกเพิ่มเติม
            const toggleButton = document.getElementById('toggleAdditionalMembers');
            const additionalMembers = document.getElementById('additionalMembers');
            
            toggleButton.addEventListener('click', function() {
                if (additionalMembers.style.display === 'none') {
                    additionalMembers.style.display = 'block';
                    toggleButton.innerHTML = '<i class="fas fa-minus-circle"></i> ซ่อนสมาชิกเพิ่มเติม';
                } else {
                    additionalMembers.style.display = 'none';
                    toggleButton.innerHTML = '<i class="fas fa-plus-circle"></i> เพิ่มสมาชิก (ไม่บังคับ)';
                }
            });
            
            // สคริปต์สำหรับการอัปโหลดไฟล์แบบลากและวาง
            for (let i = 1; i <= 8; i++) {
                const dropZone = document.getElementById(`dropZone_${i}`);
                const fileInput = document.getElementById(`member_id_card_${i}`);
                const fileName = document.getElementById(`fileName_${i}`);
                
                if (dropZone && fileInput && fileName) {
                    // คลิกที่ drop zone เพื่อเลือกไฟล์
                    dropZone.addEventListener('click', function() {
                        fileInput.click();
                    });
                    
                    // แสดงชื่อไฟล์เมื่อเลือกไฟล์
                    fileInput.addEventListener('change', function() {
                        if (this.files && this.files[0]) {
                            fileName.textContent = this.files[0].name;
                            
                            // เปลี่ยนสีของ drop zone เมื่อมีการเลือกไฟล์
                            dropZone.style.borderColor = 'var(--success-color)';
                            dropZone.style.backgroundColor = 'rgba(16, 185, 129, 0.57)';
                        }
                    });
                    
                    // รองรับการลากไฟล์มาวาง
                    dropZone.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        dropZone.style.borderColor = 'var(--primary-color)';
                        dropZone.style.backgroundColor = 'rgba(67, 97, 238, 0.1)';
                    });
                    
                    dropZone.addEventListener('dragleave', function() {
                        if (!fileInput.files || !fileInput.files[0]) {
                            dropZone.style.borderColor = 'var(--border-color)';
                            dropZone.style.backgroundColor = '';
                        }
                    });
                    
                    dropZone.addEventListener('drop', function(e) {
                        e.preventDefault();
                        
                        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                            fileInput.files = e.dataTransfer.files;
                            fileName.textContent = e.dataTransfer.files[0].name;
                            
                            // เปลี่ยนสีของ drop zone เมื่อมีการวางไฟล์
                            dropZone.style.borderColor = 'var(--success-color)';
                            dropZone.style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
                        }
                    });
                }
            }
            
            // ตรวจสอบการส่งฟอร์ม
            const form = document.getElementById('teamRegistrationForm');
            
            form.addEventListener('submit', function(e) {
                // ตรวจสอบจำนวนสมาชิกที่กรอกข้อมูล
                let memberCount = 0;
                
                for (let i = 1; i <= 8; i++) {
                    const memberName = document.getElementById(`member_name_${i}`);
                    if (memberName && memberName.value.trim() !== '') {
                        memberCount++;
                    }
                }
                
                if (memberCount < 5) {
                    e.preventDefault();
                    alert('กรุณากรอกข้อมูลสมาชิกอย่างน้อย 5 คน');
                    return false;
                }
                
                // ตรวจสอบขนาดไฟล์
                for (let i = 1; i <= 8; i++) {
                    const fileInput = document.getElementById(`member_id_card_${i}`);
                    const memberName = document.getElementById(`member_name_${i}`);
                    
                    if (memberName && memberName.value.trim() !== '') {
                        if (fileInput && fileInput.files && fileInput.files[0]) {
                            if (fileInput.files[0].size > 5 * 1024 * 1024) { // 5MB
                                e.preventDefault();
                                alert(`ไฟล์รูปภาพบัตรประชาชนของสมาชิกคนที่ ${i} มีขนาดใหญ่เกินไป (สูงสุด 5MB)`);
                                return false;
                            }
                        }
                    }
                }
                
                return true;
            });
        });
    </script>
</body>
</html>