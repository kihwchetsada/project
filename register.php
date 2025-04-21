<?php

// เพิ่มการเชื่อมต่อฐานข้อมูล
include 'upload_script.php';
include 'db_connect.php'; // ไฟล์เชื่อมต่อฐานข้อมูล
require_once 'key_storage.php';

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn instanceof PDO) {
    throw new Exception('Database connection is not a PDO instance');
}

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
        $competition_type = $_POST['competition_type'] ?? '';
        $team_name = trim($_POST['team_name'] ?? '');
        $coach_name = trim($_POST['coach_name'] ?? '');
        $coach_phone = trim($_POST['coach_phone'] ?? '');
        $leader_school = trim($_POST['leader_school'] ?? '');

        if (empty($competition_type) || empty($team_name) || empty($coach_name) || empty($coach_phone) || empty($leader_school)) {
            $team_error = 'กรุณากรอกข้อมูลทีมให้ครบถ้วน';
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
                // เริ่มทำงานกับฐานข้อมูล
                try {
                    // เริ่มต้น Transaction
                    $conn->beginTransaction();

                    // เพิ่มข้อมูลทีม
                    $stmt = $conn->prepare("INSERT INTO teams (competition_type, team_name, coach_name, coach_phone, leader_school, created_at) 
                    VALUES (:competition_type, :team_name, :coach_name, :coach_phone, :leader_school, NOW())");

                    $stmt->execute([
                        ':competition_type' => $competition_type,
                        ':team_name' => $team_name,
                        ':coach_name' => $coach_name,
                        ':coach_phone' => $coach_phone,
                        ':leader_school' => $leader_school
                    ]);

                    $team_id = $conn->lastInsertId();

                    // เพิ่มข้อมูลสมาชิก
                    for ($i = 1; $i <= 8; $i++) {
                        if (!empty($_POST["member_name_$i"])) {
                            $member_name = $_POST["member_name_$i"];
                            $member_game_name = $_POST["member_game_name_$i"] ?? '';
                            $member_age = $_POST["member_age_$i"] ?? null;
                            $member_phone = $_POST["member_phone_$i"] ?? '';
                            $member_position = $_POST["member_position_$i"] ?? '';
                            $id_card_image = '';
                            $encryption_key = '';
                            $iv = '';
                            $tag = '';

                            // จัดการไฟล์รูปภาพ
                            $file_field = "member_id_card_$i";
                            if (!empty($_FILES[$file_field]['name'])) {
                                $upload_dir = 'uploads/';
                                $keys_dir = 'keys/';

                                if (!is_dir($upload_dir)) {
                                    mkdir($upload_dir, 0755, true);
                                }
                                if (!is_dir($keys_dir)) {
                                    mkdir($keys_dir, 0755, true);
                                }

                                $image_data = file_get_contents($_FILES[$file_field]['tmp_name']);

                                // ตรวจสอบว่า OpenSSL รองรับ GCM mode
                                if (!in_array('aes-256-gcm', openssl_get_cipher_methods())) {
                                    // หากไม่รองรับ GCM ให้ใช้ CBC แทน
                                    $cipher = 'aes-256-cbc';
                                    $encryption_key_raw = random_bytes(32);
                                    $iv_length = openssl_cipher_iv_length($cipher);
                                    $iv_raw = random_bytes($iv_length);
                                    
                                    $encrypted_image = openssl_encrypt(
                                        $image_data,
                                        $cipher,
                                        $encryption_key_raw,
                                        OPENSSL_RAW_DATA,
                                        $iv_raw
                                    );
                                    $tag_raw = null; // ไม่มี tag ใน CBC mode
                                } else {
                                    // ใช้ GCM mode
                                    $cipher = 'aes-256-gcm';
                                    $encryption_key_raw = random_bytes(32);
                                    $iv_length = openssl_cipher_iv_length($cipher);
                                    $iv_raw = random_bytes($iv_length);
                                    $tag_raw = ''; // ต้องประกาศตัวแปรก่อน

                                    $encrypted_image = openssl_encrypt(
                                        $image_data,
                                        $cipher,
                                        $encryption_key_raw,
                                        OPENSSL_RAW_DATA,
                                        $iv_raw,
                                        $tag_raw
                                    );
                                }

                                // ตรวจสอบการเข้ารหัส
                                if ($encrypted_image === false) {
                                    throw new Exception('การเข้ารหัสล้มเหลว: ' . openssl_error_string());
                                }

                                // ตรวจสอบด้วยการถอดรหัสเพื่อยืนยันว่าถูกต้อง (เฉพาะการตรวจสอบ)
                                if ($cipher === 'aes-256-gcm') {
                                    $decrypted_test = openssl_decrypt(
                                        $encrypted_image,
                                        $cipher,
                                        $encryption_key_raw,
                                        OPENSSL_RAW_DATA,
                                        $iv_raw,
                                        $tag_raw
                                    );
                                } else {
                                    $decrypted_test = openssl_decrypt(
                                        $encrypted_image,
                                        $cipher,
                                        $encryption_key_raw,
                                        OPENSSL_RAW_DATA,
                                        $iv_raw
                                    );
                                }

                                if ($decrypted_test === false) {
                                    throw new Exception('การทดสอบถอดรหัสล้มเหลว กรุณาตรวจสอบการตั้งค่า OpenSSL');
                                }

                                $timestamp = time();
                                $encrypted_filename = $upload_dir . $team_id . '_id_card_' . $i . '.enc';
                                
                                // บันทึกไฟล์เข้ารหัส
                                if (file_put_contents($encrypted_filename, $encrypted_image) === false) {
                                    throw new Exception('ไม่สามารถบันทึกไฟล์เข้ารหัสได้');
                                }
                                
                                // เก็บ path ของไฟล์
                                $id_card_image = $encrypted_filename;
                                
                                // เก็บคีย์เข้ารหัสเป็น base64 เพื่อเก็บในฐานข้อมูล
                                $encryption_key = base64_encode($encryption_key_raw);
                                $iv = base64_encode($iv_raw);
                                $tag = ($tag_raw !== null) ? base64_encode($tag_raw) : '';
                            }

                            // เพิ่มข้อมูลสมาชิกลงฐานข้อมูล
                            $stmt = $conn->prepare("INSERT INTO team_members (team_id, member_name, game_name, age, phone, position, id_card_image, encryption_key, iv, tag) 
                                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$team_id, $member_name, $member_game_name, $member_age, $member_phone, $member_position, $id_card_image, $encryption_key, $iv, $tag]);
                        }
                    }

                    // ยืนยัน Transaction
                    $conn->commit();
                    $team_success = true;
                    
                } catch (PDOException $e) {
                    // ถ้าเกิดข้อผิดพลาด ให้ยกเลิก Transaction
                    $conn->rollBack();
                    $team_error = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
                }
            }
        }
    }
}

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
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>ลงทะเบียนการแข่งขัน</h1>
            <p>กรอกข้อมูลทีมของคุณเพื่อเข้าร่วมการแข่งขัน</p>
        </div>
    </header>
    
    <div class="container">
        <?php if ($team_success): ?>
            <div class="form-container success-message">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>ลงทะเบียนสำเร็จ!</h2>
                <p>ขอบคุณสำหรับการลงทะเบียน เราได้รับข้อมูลของทีม <?php echo htmlspecialchars($team_name); ?> เรียบร้อยแล้ว</p>
                <p>ทางทีมงานจะติดต่อกลับไปที่หมายเลข <?php echo htmlspecialchars($coach_phone); ?> เพื่อยืนยันการลงทะเบียน</p>
                <div style="margin-top: 2rem;">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> กลับสู่หน้าหลัก
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="progress-container">
                <div class="progress-steps">
                    <div class="step active">
                        <div class="step-icon">1</div>
                        <div class="step-text">กรอกข้อมูลทีม</div>
                    </div>
                    <div class="step">
                        <div class="step-icon">2</div>
                        <div class="step-text">ดูข้อมูลสมาชิก</div>
                    </div>
                    <div class="step">
                        <div class="step-icon">3</div>
                        <div class="step-text">รอตรวจสอบ การอนุมัติทีม</div>
                    </div>
                    <div class="step">
                        <div class="step-icon">4</div>
                        <div class="step-text">เสร็จสิ้น</div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($team_error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($team_error); ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="post" enctype="multipart/form-data" id="registrationForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="form-container">
                    <h3 class="section-title"><i class="fas fa-users-cog"></i> ข้อมูลทีม</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="competition_type">ประเภทการแข่งขัน <span class="required">*</span></label>
                            <select id="competition_type" name="competition_type" class="form-control" required>
                                <option value=""> -- เลือกประเภท -- </option>
                                <option value="รุ่นเยาวชน">รุ่นเยาวชน</option>
                                <option value="รุ่นประชาชน">รุ่นประชาชน</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="team_name">ชื่อทีม <span class="required">*</span></label>
                            <input type="text" id="team_name" name="team_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="coach_name">ชื่อผู้ควบคุมทีม <span class="required">*</span></label>
                            <input type="text" id="coach_name" name="coach_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="coach_phone">เบอร์โทรผู้ควบคุมทีม <span class="required">*</span></label>
                            <input type="tel" id="coach_phone" name="coach_phone" class="form-control" pattern="[0-9]{9,10}" placeholder="0xxxxxxxxx" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="leader_school">สังกัด/โรงเรียน <span class="required">*</span></label>
                            <input type="text" id="leader_school" name="leader_school" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-container">
                    <h3 class="section-title"><i class="fas fa-users"></i> ข้อมูลสมาชิก</h3>
                    <p style="margin-bottom: 1.5rem;">กรุณากรอกข้อมูลสมาชิกในทีมของคุณ (จำเป็นต้องมีอย่างน้อย 5 คน, สูงสุด 8 คน)</p>
                    
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <div class="member-card <?php echo $i > 5 ? 'optional' : ''; ?>">
                            <div class="member-number"><?php echo $i; ?></div>
                            <h4>
                                สมาชิกคนที่ <?php echo $i; ?> 
                                <?php if ($i <= 5): ?>
                                    <span class="required-tag">จำเป็น</span>
                                <?php else: ?>
                                    <span class="optional-tag">ไม่จำเป็น</span>
                                <?php endif; ?>
                            </h4>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="member_name_<?php echo $i; ?>">ชื่อ-นามสกุล <span class="required">*</span></label>
                                    <input type="text" id="member_name_<?php echo $i; ?>" name="member_name_<?php echo $i; ?>" class="form-control" <?php echo $i <= 5 ? 'required' : ''; ?>>
                                </div>
                                
                                <div class="form-group">
                                    <label for="member_game_name_<?php echo $i; ?>">ชื่อในเกม</label>
                                    <input type="text" id="member_game_name_<?php echo $i; ?>" name="member_game_name_<?php echo $i; ?>" class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="member_age_<?php echo $i; ?>">อายุ</label>
                                    <input type="number" id="member_age_<?php echo $i; ?>" name="member_age_<?php echo $i; ?>" min="7" max="99" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="member_phone_<?php echo $i; ?>">เบอร์โทรศัพท์</label>
                                    <input type="tel" id="member_phone_<?php echo $i; ?>" name="member_phone_<?php echo $i; ?>" pattern="[0-9]{9,10}" placeholder="0xxxxxxxxx" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="member_position_<?php echo $i; ?>">ตำแหน่ง</label>
                                    <input type="text" id="member_position_<?php echo $i; ?>" name="member_position_<?php echo $i; ?>" class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="member_id_card_<?php echo $i; ?>">รูปบัตรประชาชน <span class="required">*</span></label>
                                    <div class="file-input-container">
                                        <div class="file-input-preview" id="preview_id_card_<?php echo $i; ?>">
                                            <i class="fas fa-id-card"></i>
                                        </div>
                                        <input type="file" id="member_id_card_<?php echo $i; ?>" name="member_id_card_<?php echo $i; ?>" class="form-control custom-file-input" accept="image/*" <?php echo $i <= 5 ? 'required' : ''; ?>>
                                        <div class="id-preview">* กรุณาอัพโหลดภาพถ่ายบัตรประชาชนที่ชัดเจน</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" name="submit_team" class="btn btn-success btn-block">
                            <i class="fas fa-check-circle"></i> ลงทะเบียนทีม
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ระบบลงทะเบียนการแข่งขัน</p>
        </div>
    </footer>

    <script>
        // สำหรับแสดงตัวอย่างรูปภาพก่อนอัพโหลด
        document.addEventListener('DOMContentLoaded', function() {
            for (let i = 1; i <= 8; i++) {
                const fileInput = document.getElementById(`member_id_card_${i}`);
                const preview = document.getElementById(`preview_id_card_${i}`);
                
                if (fileInput && preview) {
                    fileInput.addEventListener('change', function() {
                        if (this.files && this.files[0]) {
                            const reader = new FileReader();
                            
                            reader.onload = function(e) {
                                while (preview.firstChild) {
                                    preview.removeChild(preview.firstChild);
                                }
                                
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                preview.appendChild(img);
                            };
                            
                            reader.readAsDataURL(this.files[0]);
                        } else {
                            while (preview.firstChild) {
                                preview.removeChild(preview.firstChild);
                            }
                            
                            const icon = document.createElement('i');
                            icon.className = 'fas fa-id-card';
                            preview.appendChild(icon);
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>