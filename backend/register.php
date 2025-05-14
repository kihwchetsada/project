<?php
// เพิ่มการแสดงข้อผิดพลาดสำหรับช่วงพัฒนา (ลบออกเมื่อใช้งานจริง)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// เพิ่มการเชื่อมต่อฐานข้อมูลและความปลอดภัย
require_once '../db_connect.php'; // ไฟล์เชื่อมต่อฐานข้อมูล
require_once '../key_storage.php'; // ไฟล์จัดการคีย์

// เริ่มต้นเซสชันถ้ายังไม่มี
if (session_status() === PHP_SESSION_NONE) {
    // ตั้งค่าความปลอดภัยสำหรับเซสชัน
    $session_options = [
        'cookie_httponly' => true, // ป้องกัน JavaScript เข้าถึงคุกกี้เซสชัน
        'cookie_secure' => true,   // ใช้เฉพาะ HTTPS (ควรเปิดใช้งานเมื่อเว็บมี HTTPS)
        'cookie_samesite' => 'Lax', // ป้องกัน CSRF แบบบางส่วน
        'use_strict_mode' => true  // เพิ่มความปลอดภัยของเซสชัน
    ];
    session_start($session_options);
    //session_regenerate_id(true); // สร้าง ID เซสชันใหม่เพื่อป้องกันการโจมตีแบบ session fixation
}

// กำหนด Content Security Policy (CSP)
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com; style-src 'self' https://cdnjs.cloudflare.com; img-src 'self' data:;");

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
$db_conn_error = false;
if (!isset($conn) || !$conn instanceof PDO) {
    error_log('Database connection is not a PDO instance or not set');
    $db_conn_error = true;
}

// กำหนดคอนสแตนต์สำหรับไดเร็กทอรี่อัปโหลด
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', __DIR__ . '/uploads');
}


// สร้างไดเร็กทอรี่ถ้ายังไม่มี
$upload_dir_error = false;
if (!file_exists(UPLOAD_DIR)) {
    try {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            error_log('Failed to create upload directory: ' . UPLOAD_DIR);
            $upload_dir_error = true;
        } else {
            // เปลี่ยนสิทธิ์ให้เขียนได้
            chmod(UPLOAD_DIR, 0755);
        }
    } catch (Exception $e) {
        error_log('Error creating upload directory: ' . $e->getMessage());
        $upload_dir_error = true;
    }
}

// ตรวจสอบสิทธิ์ในการเขียนไดเร็กทอรี่
if (!$upload_dir_error && !is_writable(UPLOAD_DIR)) {
    error_log('Upload directory is not writable: ' . UPLOAD_DIR);
    $upload_dir_error = true;
}

// เพิ่มโค้ดสำหรับการจัดการข้อมูลทีม
$team_success = false;
$team_error = '';
$debug_info = [];

// สร้าง CSRF token ถ้ายังไม่มี หรือเกิน 1 ชั่วโมง
if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || time() - $_SESSION['csrf_token_time'] > 3600) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// ตรวจสอบความพร้อมของระบบ
if ($db_conn_error) {
    $team_error = 'ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ';
    $debug_info[] = 'Database connection error';
} elseif ($upload_dir_error) {
    $team_error = 'ไม่สามารถสร้างหรือเขียนไดเร็กทอรี่สำหรับอัปโหลดไฟล์ได้ กรุณาติดต่อผู้ดูแลระบบ';
    $debug_info[] = 'Upload directory error';
} elseif (empty($team_name) || empty($coach_name) || empty($coach_phone)) {
    $team_error = 'กรุณากรอกข้อมูลทีมให้ครบถ้วน';
    $debug_info[] = 'Incomplete team information';
}

// ฟังก์ชันสำหรับตรวจสอบความปลอดภัยของข้อมูลนำเข้า
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// ฟังก์ชันสำหรับตรวจสอบเงื่อนไขของไฟล์ที่อัปโหลด
function validate_uploaded_file($file_field) {
    global $debug_info;
    
    if (empty($_FILES[$file_field]['name'])) {
        return ['valid' => false, 'error' => 'ไม่พบไฟล์ที่อัปโหลด'];
    }
    
    // ตรวจสอบข้อผิดพลาดการอัปโหลด
    if ($_FILES[$file_field]['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'ไฟล์มีขนาดเกินกว่าที่กำหนดใน php.ini',
            UPLOAD_ERR_FORM_SIZE => 'ไฟล์มีขนาดเกินกว่าที่กำหนดในฟอร์ม',
            UPLOAD_ERR_PARTIAL => 'ไฟล์ถูกอัปโหลดเพียงบางส่วน',
            UPLOAD_ERR_NO_FILE => 'ไม่มีไฟล์ที่อัปโหลด',
            UPLOAD_ERR_NO_TMP_DIR => 'ไม่พบโฟลเดอร์ชั่วคราว',
            UPLOAD_ERR_CANT_WRITE => 'ไม่สามารถเขียนไฟล์ไปยังดิสก์ได้',
            UPLOAD_ERR_EXTENSION => 'การอัปโหลดไฟล์ถูกหยุดโดย PHP extension'
        ];
        
        $error_code = $_FILES[$file_field]['error'];
        $error_message = isset($upload_errors[$error_code]) ? $upload_errors[$error_code] : 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุในการอัปโหลดไฟล์';
        $debug_info[] = "File upload error code: {$error_code} - {$error_message}";
        
        return ['valid' => false, 'error' => $error_message];
    }
    
    // ตรวจสอบขนาดไฟล์
    $max_file_size = 5 * 1024 * 1024; // 5MB
    if ($_FILES[$file_field]['size'] > $max_file_size) {
        return ['valid' => false, 'error' => 'ขนาดไฟล์เกินขีดจำกัด (สูงสุด 5MB)'];
    }
    
    // ตรวจสอบประเภทไฟล์จากเนื้อหาไฟล์
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $_FILES[$file_field]['tmp_name']);
    finfo_close($finfo);
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mime_type, $allowed_types)) {
        return ['valid' => false, 'error' => "ประเภทไฟล์ไม่ถูกต้อง: {$mime_type}"];
    }
    
    // ตรวจสอบนามสกุลไฟล์
    $file_extension = strtolower(pathinfo($_FILES[$file_field]['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['valid' => false, 'error' => "นามสกุลไฟล์ไม่ถูกต้อง: {$file_extension}"];
    }
    
    return ['valid' => true];
}

// ฟังก์ชันเข้ารหัสไฟล์อย่างง่าย (ใช้เฉพาะกรณีที่มีปัญหากับการเข้ารหัสแบบเดิม)
function simple_encrypt_file($file_content, $encryption_key) {
    $cipher = "aes-256-cbc";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($file_content, $cipher, $encryption_key, OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext_raw, $encryption_key, true);
    return $iv . $hmac . $ciphertext_raw;
}

// สร้าง CSRF token ถ้ายังไม่มี
if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || time() - $_SESSION['csrf_token_time'] > 3600) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// ตรวจสอบการส่งฟอร์มข้อมูลทีม
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_team']) && empty($team_error)) {
    // ตรวจสอบ CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $team_error = 'CSRF token ไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
        $debug_info[] = 'CSRF validation failed';
    } else {
        // ตรวจสอบข้อมูลทีม
        $competition_type = sanitize_input($_POST['competition_type'] ?? '');
        $team_name = sanitize_input($_POST['team_name'] ?? '');
        $coach_name = sanitize_input($_POST['coach_name'] ?? '');
        $coach_phone = sanitize_input($_POST['coach_phone'] ?? '');
        $leader_school = sanitize_input($_POST['leader_school'] ?? '');

        // ตรวจสอบความถูกต้องของข้อมูล
        $validation_errors = [];
        
        if (empty($competition_type)) {
            $validation_errors[] = 'กรุณาเลือกประเภทการแข่งขัน';
        }
        
        if (empty($team_name)) {
            $validation_errors[] = 'กรุณากรอกชื่อทีม';
        } elseif (strlen($team_name) > 50) {
            $validation_errors[] = 'ชื่อทีมต้องไม่เกิน 50 ตัวอักษร';
        }
        
        if (empty($coach_name)) {
            $validation_errors[] = 'กรุณากรอกชื่อผู้ฝึกสอน';
        }
        
        if (empty($coach_phone)) {
            $validation_errors[] = 'กรุณากรอกเบอร์โทรผู้ฝึกสอน';
        } elseif (!preg_match('/^[0-9]{10}$/', $coach_phone)) {
            $validation_errors[] = 'เบอร์โทรผู้ฝึกสอนต้องเป็นตัวเลข 10 หลัก';
        }
        
        if (empty($leader_school)) {
            $validation_errors[] = 'กรุณากรอกชื่อโรงเรียน/สถาบัน';
        }

        if (!empty($validation_errors)) {
            $team_error = implode('<br>', $validation_errors);
            $debug_info[] = 'Form validation errors: ' . implode(', ', $validation_errors);
        } else {
            // นับจำนวนสมาชิกที่มีข้อมูล
            $member_count = 0;
            $members_data = [];
            
            for ($i = 1; $i <= 8; $i++) {
                if (!empty($_POST["member_name_$i"])) {
                    $member_name = sanitize_input($_POST["member_name_$i"]);
                    $member_game_name = sanitize_input($_POST["member_game_name_$i"] ?? '');
                    $member_age = filter_var($_POST["member_age_$i"] ?? 0, FILTER_VALIDATE_INT);
                    $member_phone = sanitize_input($_POST["member_phone_$i"] ?? '');
                    $member_position = sanitize_input($_POST["member_position_$i"] ?? '');
                    
                    // ตรวจสอบข้อมูลสมาชิก
                    $member_valid = true;
                    
                    if ($i <= 5) {
                        $file_validation = validate_uploaded_file("member_id_card_$i");
                        if (!$file_validation['valid']) {
                            $member_valid = false;
                            $validation_errors[] = "สมาชิกคนที่ {$i}: " . $file_validation['error'];
                        }
                    }
                    
                    if (!empty($member_phone) && !preg_match('/^[0-9]{10}$/', $member_phone)) {
                        $member_valid = false;
                        $validation_errors[] = "เบอร์โทรของสมาชิกคนที่ $i ต้องเป็นตัวเลข 10 หลัก";
                    }
                    
                    if ($member_age !== false && ($member_age < 12 || $member_age > 60)) {
                        $member_valid = false;
                        $validation_errors[] = "อายุของสมาชิกคนที่ $i ต้องอยู่ระหว่าง 12-60 ปี";
                    }
                    
                    if ($member_valid) {
                        $member_count++;
                        $members_data[$i] = [
                            'name' => $member_name,
                            'game_name' => $member_game_name,
                            'age' => $member_age,
                            'phone' => $member_phone,
                            'position' => $member_position
                        ];
                    }
                }
            }

            if (!empty($validation_errors)) {
                $team_error = implode('<br>', $validation_errors);
                $debug_info[] = 'Member validation errors: ' . implode(', ', $validation_errors);
            } elseif ($member_count < 5) {
                $team_error = 'กรุณากรอกข้อมูลสมาชิกอย่างน้อย 5 คน';
                $debug_info[] = 'Not enough team members: ' . $member_count . ' (required 5)';
            } else {
                // เริ่มทำงานกับฐานข้อมูล
                try {
                    $debug_info[] = 'Starting database operations';
                    
                    // ตรวจสอบโครงสร้างฐานข้อมูล
                    try {
                        $table_check = $conn->query("SHOW TABLES LIKE 'teams'");
                        if ($table_check->rowCount() == 0) {
                            throw new Exception("ไม่พบตาราง 'teams' ในฐานข้อมูล");
                        }
                        
                        $table_check = $conn->query("SHOW TABLES LIKE 'team_members'");
                        if ($table_check->rowCount() == 0) {
                            throw new Exception("ไม่พบตาราง 'team_members' ในฐานข้อมูล");
                        }
                        
                        $debug_info[] = 'Database tables exist';
                    } catch (Exception $e) {
                        throw new Exception('ตรวจสอบโครงสร้างฐานข้อมูลล้มเหลว: ' . $e->getMessage());
                    }
                    
                    // เริ่มต้น Transaction
                    $conn->beginTransaction();
                    $debug_info[] = 'Transaction started';

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
                    $debug_info[] = 'Team created with ID: ' . $team_id;

                    // เพิ่มข้อมูลสมาชิก
                    foreach ($members_data as $i => $member) {
                        $id_card_image = '';
                        $key_id = '';

                        // จัดการไฟล์รูปภาพ
                        $file_field = "member_id_card_$i";
                        if (!empty($_FILES[$file_field]['name'])) {
                            try {
                                // อ่านข้อมูลไฟล์
                                $image_data = file_get_contents($_FILES[$file_field]['tmp_name']);
                                if ($image_data === false) {
                                    throw new Exception("ไม่สามารถอ่านไฟล์ {$_FILES[$file_field]['name']} ได้");
                                }
                                
                                $debug_info[] = "File read successfully for member $i: " . $_FILES[$file_field]['name'];
                                
                                // กำหนดชื่อไฟล์ที่ปลอดภัย
                                $secure_filename = $team_id . '_' . md5($member['name'] . microtime()) . '_' . $i . '.enc';
                                $encrypted_filename = UPLOAD_DIR . '/' . $secure_filename;
                                
                                // ทดลองใช้การเข้ารหัสอย่างง่าย
                                $encryption_key = bin2hex(random_bytes(16)); // 128-bit key
                                $encrypted_image = simple_encrypt_file($image_data, $encryption_key);
                                
                                // บันทึกไฟล์เข้ารหัส
                                if (file_put_contents($encrypted_filename, $encrypted_image) === false) {
                                    throw new Exception('ไม่สามารถบันทึกไฟล์เข้ารหัสได้: ' . $encrypted_filename);
                                }
                                
                                $debug_info[] = "Encrypted file saved for member $i: " . $encrypted_filename;
                                
                                // บันทึกคีย์เข้ารหัสด้วยฟังก์ชันอย่างง่าย (fallback)
                                try {
                                    // พยายามใช้ save_encryption_keys ถ้ามี
                                    if (function_exists('save_encryption_keys')) {
                                        $identifier = 'team_' . $team_id . '_member_' . $i;
                                        // Define a fallback mechanism to save encryption keys
                                        $key_file = UPLOAD_DIR . '/' . $identifier . '_key.txt';
                                        if (file_put_contents($key_file, $encryption_key) === false) {
                                            throw new Exception('Failed to save encryption key to file: ' . $key_file);
                                        }
                                        chmod($key_file, 0600); // Restrict file permissions
                                        $key_id = basename($key_file);
                                    } else {
                                        // ถ้าไม่มีฟังก์ชัน ให้บันทึกคีย์ลงในไฟล์
                                        $key_file = UPLOAD_DIR . '/' . $team_id . '_key_' . $i . '.txt';
                                        file_put_contents($key_file, $encryption_key);
                                        $key_id = basename($key_file);
                                        chmod($key_file, 0600); // เปลี่ยนสิทธิ์ให้อ่านได้เฉพาะเจ้าของ
                                    }
                                    
                                    $debug_info[] = "Encryption key saved for member $i with ID: " . $key_id;
                                } catch (Exception $e) {
                                    throw new Exception('ไม่สามารถบันทึกคีย์การเข้ารหัสได้: ' . $e->getMessage());
                                }
                                
                                // เก็บ path ของไฟล์
                                $id_card_image = $encrypted_filename;
                            } catch (Exception $e) {
                                throw new Exception('การเข้ารหัสไฟล์ล้มเหลว: ' . $e->getMessage());
                            }
                        }

                        // เพิ่มข้อมูลสมาชิกลงฐานข้อมูล
                        try {
                            $stmt = $conn->prepare("
                                INSERT INTO team_members 
                                    (team_id, member_name, game_name, age, phone, position, id_card_image, key_id) 
                                VALUES 
                                    (:team_id, :member_name, :game_name, :age, :phone, :position, :id_card_image, :key_id)
                            ");
                            
                            $stmt->execute([
                                ':team_id' => $team_id,
                                ':member_name' => $member['name'],
                                ':game_name' => $member['game_name'],
                                ':age' => $member['age'],
                                ':phone' => $member['phone'],
                                ':position' => $member['position'],
                                ':id_card_image' => $id_card_image,
                                ':key_id' => $key_id
                            ]);
                            
                            $debug_info[] = "Member $i data saved to database";
                        } catch (PDOException $e) {
                            throw new Exception("การบันทึกข้อมูลสมาชิกคนที่ $i ล้มเหลว: " . $e->getMessage());
                        }
                    }

                    // ยืนยัน Transaction
                    $conn->commit();
                    $debug_info[] = 'Transaction committed successfully';
                    $team_success = true;
                    
                    // เปลี่ยน CSRF token หลังจากสำเร็จเพื่อป้องกัน replay attack
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $_SESSION['csrf_token_time'] = time();
                    
                } catch (PDOException $e) {
                    // ถ้าเกิดข้อผิดพลาด ให้ยกเลิก Transaction
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                        $debug_info[] = 'Transaction rolled back due to PDO error';
                    }
                    error_log('Database error: ' . $e->getMessage());
                    $team_error = 'เกิดข้อผิดพลาดในฐานข้อมูล: ' . $e->getMessage();
                    $debug_info[] = 'PDO Error: ' . $e->getMessage();
                } catch (Exception $e) {
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                        $debug_info[] = 'Transaction rolled back due to general error';
                    }
                    error_log('Application error: ' . $e->getMessage());
                    $team_error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                    $debug_info[] = 'General Error: ' . $e->getMessage();
                }
            }
        }
    }
}

// กำหนดฟังก์ชันสำหรับแสดงข้อความ error และ success
function show_alert($message, $type = 'error') {
    $icon = ($type == 'error') ? 'exclamation-circle' : 'check-circle';
    return '<div class="alert alert-' . $type . '"><i class="fas fa-' . $icon . '"></i> ' . $message . '</div>';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <title>ลงทะเบียนทีม</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/register.css">
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
                
                <?php
                // ล้างข้อมูลใน session ที่ไม่จำเป็นหลังจากลงทะเบียนเสร็จ
                unset($_SESSION['csrf_token']);
                unset($_SESSION['csrf_token_time']);
                ?>
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
                <?php echo show_alert($team_error); ?>
                <?php if (isset($_GET['debug']) && $_GET['debug'] == 'true'): ?>
                    <div class="debug-info">
                        <h3>Debug Information</h3>
                        <pre><?php echo implode("\n", $debug_info); ?></pre>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="form-container">
                <form id="registerForm" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-section">
                        <h2>ข้อมูลทีม</h2>
                        
                        <div class="form-group">
                            <label for="competition_type">ประเภทการแข่งขัน *</label>
                            <select id="competition_type" name="competition_type" required>
                                <option value="">เลือกประเภทการแข่งขัน</option>
                                <option value="type1">ประเภทที่ 1 - เกมส์ ROV</option>
                                <option value="type2">ประเภทที่ 2 - เกมส์ FIFA</option>
                                <option value="type3">ประเภทที่ 3 - เกมส์ PUBG</option>
                            </select>
                            <div class="invalid-feedback">กรุณาเลือกประเภทการแข่งขัน</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="team_name">ชื่อทีม *</label>
                            <input type="text" id="team_name" name="team_name" maxlength="50" required>
                            <div class="invalid-feedback">กรุณากรอกชื่อทีม</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="coach_name">ชื่อผู้ฝึกสอน *</label>
                            <input type="text" id="coach_name" name="coach_name" required>
                            <div class="invalid-feedback">กรุณากรอกชื่อผู้ฝึกสอน</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="coach_phone">เบอร์โทรผู้ฝึกสอน *</label>
                            <input type="tel" id="coach_phone" name="coach_phone" pattern="[0-9]{10}" required>
                            <div class="invalid-feedback">กรุณากรอกเบอร์โทรผู้ฝึกสอน (ตัวเลข 10 หลัก)</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="leader_school">โรงเรียน/สถาบัน *</label>
                            <input type="text" id="leader_school" name="leader_school" required>
                            <div class="invalid-feedback">กรุณากรอกชื่อโรงเรียน/สถาบัน</div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2>ข้อมูลสมาชิก</h2>
                        <p class="helper-text">กรอกข้อมูลสมาชิกอย่างน้อย 5 คน (สูงสุด 8 คน)</p>
                        
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <div class="member-section <?php echo $i > 5 ? 'optional' : ''; ?>">
                                <h3><?php echo $i <= 5 ? "สมาชิกคนที่ $i *" : "สมาชิกสำรองคนที่ " . ($i - 5); ?></h3>
                                
                                <div class="form-group">
                                    <label for="member_name_<?php echo $i; ?>"><?php echo $i <= 5 ? "ชื่อ-นามสกุล *" : "ชื่อ-นามสกุล"; ?></label>
                                    <input type="text" id="member_name_<?php echo $i; ?>" name="member_name_<?php echo $i; ?>" <?php echo $i <= 5 ? 'required' : ''; ?>>
                                    <?php if ($i <= 5): ?>
                                        <div class="invalid-feedback">กรุณากรอกชื่อ-นามสกุล</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="member_game_name_<?php echo $i; ?>">ชื่อในเกม</label>
                                    <input type="text" id="member_game_name_<?php echo $i; ?>" name="member_game_name_<?php echo $i; ?>">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="member_age_<?php echo $i; ?>"><?php echo $i <= 5 ? "อายุ *" : "อายุ"; ?></label>
                                        <input type="number" id="member_age_<?php echo $i; ?>" name="member_age_<?php echo $i; ?>" min="12" max="60" <?php echo $i <= 5 ? 'required' : ''; ?>>
                                        <?php if ($i <= 5): ?>
                                            <div class="invalid-feedback">กรุณากรอกอายุ (12-60 ปี)</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group col-md-6">
                                        <label for="member_phone_<?php echo $i; ?>">เบอร์โทร</label>
                                        <input type="tel" id="member_phone_<?php echo $i; ?>" name="member_phone_<?php echo $i; ?>" pattern="[0-9]{10}">
                                        <div class="invalid-feedback">กรุณากรอกเบอร์โทรให้ถูกต้อง</div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="member_position_<?php echo $i; ?>">ตำแหน่ง</label>
                                    <input type="text" id="member_position_<?php echo $i; ?>" name="member_position_<?php echo $i; ?>">
                                </div>
                                
                                <?php if ($i <= 5): ?>
                                    <div class="form-group">
                                        <label for="member_id_card_<?php echo $i; ?>">รูปบัตรประชาชน/นักเรียน *</label>
                                        <div class="file-input-container">
                                            <input type="file" id="member_id_card_<?php echo $i; ?>" name="member_id_card_<?php echo $i; ?>" accept="image/jpeg,image/png,image/gif" required>
                                            <label for="member_id_card_<?php echo $i; ?>" class="file-input-button">
                                                <i class="fas fa-upload"></i> เลือกไฟล์
                                            </label>
                                            <span class="file-name" id="file_name_<?php echo $i; ?>">ยังไม่ได้เลือกไฟล์</span>
                                        </div>
                                        <div class="file-info">รองรับไฟล์ .jpg, .png, .gif ขนาดไม่เกิน 5MB</div>
                                        <div class="invalid-feedback">กรุณาอัปโหลดรูปภาพบัตรประชาชน/นักเรียน</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="form-section">
                        <h2>ยืนยันการลงทะเบียน</h2>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="agree_terms" name="agree_terms" required>
                            <label for="agree_terms">ข้าพเจ้ายอมรับ<a href="#" data-toggle="modal" data-target="#termsModal">เงื่อนไขและข้อตกลง</a>ในการเข้าร่วมการแข่งขัน *</label>
                            <div class="invalid-feedback">กรุณายอมรับเงื่อนไขและข้อตกลง</div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="reset" class="btn btn-outline">
                                <i class="fas fa-redo"></i> ล้างข้อมูล
                            </button>
                            <button type="submit" name="submit_team" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> ส่งข้อมูลการลงทะเบียน
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal เงื่อนไขและข้อตกลง -->
    <div class="modal" id="termsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>เงื่อนไขและข้อตกลงในการเข้าร่วมการแข่งขัน</h2>
                <span class="close" data-dismiss="modal">&times;</span>
            </div>
            <div class="modal-body">
                <h3>กฎกติกาและเงื่อนไข</h3>
                <ol>
                    <li>ผู้เข้าแข่งขันต้องมีอายุระหว่าง 12-60 ปี</li>
                    <li>ทีมต้องมีสมาชิกอย่างน้อย 5 คน และไม่เกิน 8 คน</li>
                    <li>แต่ละทีมสามารถมีผู้ฝึกสอนได้ 1 คน</li>
                    <li>ผู้เข้าแข่งขันต้องมีบัตรประจำตัวประชาชนหรือบัตรนักเรียนที่ยังไม่หมดอายุ</li>
                    <li>ผู้จัดงานขอสงวนสิทธิ์ในการเปลี่ยนแปลงกฎกติกาโดยไม่ต้องแจ้งให้ทราบล่วงหน้า</li>
                    <li>คำตัดสินของคณะกรรมการถือเป็นที่สิ้นสุด</li>
                </ol>
                
                <h3>ข้อมูลส่วนบุคคล</h3>
                <p>การลงทะเบียนเข้าร่วมการแข่งขัน ผู้สมัครยินยอมให้ผู้จัดงานเก็บข้อมูลส่วนบุคคลเพื่อวัตถุประสงค์ดังต่อไปนี้</p>
                <ul>
                    <li>ใช้ในการยืนยันตัวตนของผู้เข้าแข่งขัน</li>
                    <li>ใช้ในการติดต่อประสานงานระหว่างการแข่งขัน</li>
                    <li>ใช้ในการประกาศผลการแข่งขันและมอบรางวัล</li>
                </ul>
                <p>ผู้จัดงานจะเก็บรักษาข้อมูลของผู้สมัครเป็นความลับตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562</p>
                
                <button class="btn btn-primary btn-block" data-accept="terms">
                    <i class="fas fa-check-circle"></i> ยอมรับข้อตกลง
                </button>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ระบบลงทะเบียนการแข่งขัน. สงวนลิขสิทธิ์.</p>
            <p><small>เวอร์ชัน 1.0.5</small></p>
        </div>
    </footer>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../js/register.js"></script>
    <script>
        // สคริปต์สำหรับการตรวจสอบฟอร์ม
        document.addEventListener('DOMContentLoaded', function() {
            // แสดงชื่อไฟล์เมื่อมีการเลือกไฟล์
            for (let i = 1; i <= 8; i++) {
                const fileInput = document.getElementById(`member_id_card_${i}`);
                const fileNameSpan = document.getElementById(`file_name_${i}`);
                
                if (fileInput && fileNameSpan) {
                    fileInput.addEventListener('change', function() {
                        if (this.files.length > 0) {
                            const fileName = this.files[0].name;
                            const fileSize = Math.round(this.files[0].size / 1024); // แปลงเป็น KB
                            fileNameSpan.textContent = `${fileName} (${fileSize} KB)`;
                            
                            // ตรวจสอบขนาดไฟล์
                            if (this.files[0].size > 5 * 1024 * 1024) { // 5MB
                                alert('ไฟล์มีขนาดใหญ่เกินไป (เกิน 5MB)');
                                this.value = ''; // ล้างค่า
                                fileNameSpan.textContent = 'ยังไม่ได้เลือกไฟล์';
                            }
                        } else {
                            fileNameSpan.textContent = 'ยังไม่ได้เลือกไฟล์';
                        }
                    });
                }
            }
            
            // จัดการโมดัล
            const modal = document.getElementById('termsModal');
            const modalOpenButtons = document.querySelectorAll('[data-target="#termsModal"]');
            const modalCloseButtons = document.querySelectorAll('[data-dismiss="modal"]');
            const acceptButton = document.querySelector('[data-accept="terms"]');
            const agreeCheckbox = document.getElementById('agree_terms');
            
            modalOpenButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    modal.style.display = 'flex';
                });
            });
            
            modalCloseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            });
            
            if (acceptButton && agreeCheckbox) {
                acceptButton.addEventListener('click', function() {
                    agreeCheckbox.checked = true;
                    modal.style.display = 'none';
                });
            }
            
            // ปิดโมดัลเมื่อคลิกนอกกรอบ
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
            
            // การตรวจสอบแบบฟอร์ม
            const form = document.getElementById('registerForm');
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!this.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    this.classList.add('was-validated');
                    
                    // ตรวจสอบว่ามีสมาชิกอย่างน้อย 5 คน
                    let validMembers = 0;
                    for (let i = 1; i <= 5; i++) {
                        const memberName = document.getElementById(`member_name_${i}`);
                        if (memberName && memberName.value.trim() !== '') {
                            validMembers++;
                        }
                    }
                    
                    if (validMembers < 5) {
                        alert('กรุณากรอกข้อมูลสมาชิกอย่างน้อย 5 คน');
                        event.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>