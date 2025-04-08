<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// กำหนดค่าไดเร็กทอรี่ก่อน
$upload_dir = 'uploads';

// สร้างไดเร็กทอรี่ตั้งแต่เริ่มต้น
if (!is_dir($upload_dir)) {
    if (!@mkdir($upload_dir, 0755, true)) {
        $create_dir_error = error_get_last();
        error_log("ไม่สามารถสร้างไดเร็กทอรี่ uploads: " . $create_dir_error['message']);
    }
}

// ตรวจสอบสิทธิ์การเขียน
if (!is_writable($upload_dir)) {
    error_log("ไม่มีสิทธิ์เขียนไฟล์ในไดเร็กทอรี่ $upload_dir");
}

// ข้อมูลการเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tournament_registration"; // แก้เป็นฐานข้อมูลที่ถูกต้อง

// สร้าง CSRF token พร้อมกำหนดอายุ
if (!isset($_SESSION['csrf_token_time'])) {
    $_SESSION['csrf_token_time'] = 0;
}

if (empty($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || time() - $_SESSION['csrf_token_time'] > 3600) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// การตั้งค่าการจัดการข้อผิดพลาด
error_reporting(E_ALL);
ini_set('display_errors', 1); // เปลี่ยนเป็น 1 เพื่อดีบัก
ini_set('log_errors', 1);
ini_set('error_log', 'error.log'); // เปลี่ยนเป็นไฟล์ที่สามารถเขียนได้

// ตรวจสอบและประมวลผลการอัปโหลด
$encryption_success = false;
$encryption_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // เชื่อมต่อฐานข้อมูล
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception('เชื่อมต่อฐานข้อมูลล้มเหลว: ' . $conn->connect_error);
        }

        // ตรวจสอบ CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('การร้องขอไม่ถูกต้อง');
        }

        // ตรวจสอบการอัปโหลดไฟล์
        if (!isset($_FILES['image'])) {
            throw new Exception('ไม่พบไฟล์ที่อัปโหลด');
        }
        
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'ไฟล์มีขนาดใหญ่เกินค่า upload_max_filesize ใน php.ini',
                UPLOAD_ERR_FORM_SIZE => 'ไฟล์มีขนาดใหญ่เกินค่า MAX_FILE_SIZE ที่กำหนดในฟอร์ม HTML',
                UPLOAD_ERR_PARTIAL => 'ไฟล์ถูกอัปโหลดเพียงบางส่วน',
                UPLOAD_ERR_NO_FILE => 'ไม่มีไฟล์ถูกอัปโหลด',
                UPLOAD_ERR_NO_TMP_DIR => 'ไม่พบโฟลเดอร์ชั่วคราว',
                UPLOAD_ERR_CANT_WRITE => 'ไม่สามารถเขียนไฟล์ลงดิสก์',
                UPLOAD_ERR_EXTENSION => 'การอัปโหลดถูกหยุดโดย PHP extension'
            ];
            
            $error_code = $_FILES['image']['error'];
            $error_message = isset($upload_errors[$error_code]) ? $upload_errors[$error_code] : 'เกิดข้อผิดพลาดที่ไม่รู้จัก';
            throw new Exception('การอัปโหลดไฟล์ล้มเหลว: ' . $error_message);
        }

        // ตรวจสอบว่าไฟล์ถูกอัปโหลดผ่าน HTTP POST
        if (!is_uploaded_file($_FILES['image']['tmp_name'])) {
            throw new Exception('ไฟล์ไม่ได้ถูกอัปโหลดผ่าน HTTP POST');
        }

        // ตรวจสอบขนาดและประเภทไฟล์อย่างละเอียด
        $max_file_size = 5 * 1024 * 1024; // 5MB
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

        if ($_FILES['image']['size'] > $max_file_size) {
            throw new Exception('ขนาดไฟล์เกินขีดจำกัด (สูงสุด 5MB)');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('ประเภทไฟล์ไม่ถูกต้อง: ' . $mime_type);
        }

        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            throw new Exception('นามสกุลไฟล์ไม่ถูกต้อง: ' . $extension);
        }

        // อ่านข้อมูลภาพ
        $image_data = file_get_contents($_FILES['image']['tmp_name']);
        if ($image_data === false) {
            throw new Exception('ไม่สามารถอ่านข้อมูลภาพได้');
        }

        // ตรวจสอบว่า OpenSSL รองรับ GCM mode
        if (!in_array('aes-256-gcm', openssl_get_cipher_methods())) {
            // หากไม่รองรับ GCM ให้ใช้ CBC แทน
            $cipher = 'aes-256-cbc';
        } else {
            $cipher = 'aes-256-gcm';
        }

        // สร้างคีย์และ IV แบบปลอดภัย
        $encryption_key = random_bytes(32);
        $iv_length = openssl_cipher_iv_length($cipher);
        $iv = random_bytes($iv_length);
        $tag = '';

        // เข้ารหัสภาพ
        if ($cipher === 'aes-256-gcm') {
            $encrypted_image = openssl_encrypt(
                $image_data, 
                $cipher, 
                $encryption_key, 
                OPENSSL_RAW_DATA, 
                $iv, 
                $tag
            );
        } else {
            // ใช้ CBC mode หากไม่รองรับ GCM
            $encrypted_image = openssl_encrypt(
                $image_data, 
                $cipher, 
                $encryption_key, 
                OPENSSL_RAW_DATA, 
                $iv
            );
        }

        if ($encrypted_image === false) {
            throw new Exception('การเข้ารหัสล้มเหลว: ' . openssl_error_string());
        }

        // ใช้เวลาในการสร้างชื่อไฟล์
        date_default_timezone_set('Asia/Bangkok'); 
        $timestamp = time();  
        $encrypted_filename = $timestamp . '.enc';
        
        // บันทึกไฟล์เข้ารหัสและกำหนดสิทธิ์ไฟล์
        if (file_put_contents($upload_dir . '/' . $encrypted_filename, $encrypted_image) === false) {
            throw new Exception('ไม่สามารถบันทึกไฟล์เข้ารหัสได้');
        }
        @chmod($upload_dir . '/' . $encrypted_filename, 0644);

        // ข้อมูลสำหรับบันทึกลงฐานข้อมูล
        $key_data = base64_encode($encryption_key);
        $iv_data = base64_encode($iv);
        $tag_data = !empty($tag) ? base64_encode($tag) : '';
        $file_path = $upload_dir . '/' . $encrypted_filename;
        
        // สมมติว่าเราต้องการอัปเดตข้อมูลสมาชิกทีม
        // ในสถานการณ์จริงควรรับค่า team_id และ member_id จากฟอร์มหรือ session
        if (isset($_POST['team_id']) && isset($_POST['member_id'])) {
            $team_id = (int)$_POST['team_id'];
            $member_id = (int)$_POST['member_id'];
            
            // อัปเดตข้อมูลการเข้ารหัสในตาราง team_members
            $sql = "UPDATE team_members SET 
                    id_card_image = ?, 
                    encryption_key = ?, 
                    iv = ?, 
                    tag = ?,
                    updated_at = NOW()
                    WHERE member_id = ? AND team_id = ?";
                    
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('การเตรียมคำสั่ง SQL ล้มเหลว: ' . $conn->error);
            }
            
            $stmt->bind_param("ssssii", $file_path, $key_data, $iv_data, $tag_data, $member_id, $team_id);
        } else {
            // กรณีเพิ่มสมาชิกใหม่
            // ในสถานการณ์จริงควรรับค่าเหล่านี้จากฟอร์ม
            if (isset($_POST['team_id'])) {
                $team_id = (int)$_POST['team_id'];
                $member_name = $conn->real_escape_string($_POST['member_name'] ?? '');
                $game_name = $conn->real_escape_string($_POST['game_name'] ?? '');
                $age = (int)($_POST['age'] ?? 0);
                $phone = $conn->real_escape_string($_POST['phone'] ?? '');
                $position = $conn->real_escape_string($_POST['position'] ?? '');
                
                // เพิ่มข้อมูลสมาชิกใหม่
                $sql = "INSERT INTO team_members 
                        (team_id, member_name, game_name, age, phone, position, id_card_image, encryption_key, iv, tag) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('การเตรียมคำสั่ง SQL ล้มเหลว: ' . $conn->error);
                }
                
                $stmt->bind_param("issiisssss", $team_id, $member_name, $game_name, $age, $phone, $position, $file_path, $key_data, $iv_data, $tag_data);
            } else {
                throw new Exception('ไม่พบข้อมูล team_id ที่จำเป็น');
            }
        }
        
        if (!$stmt->execute()) {
            // ลบไฟล์เข้ารหัสหากไม่สามารถบันทึกข้อมูลลงฐานข้อมูลได้
            @unlink($upload_dir . '/' . $encrypted_filename);
            throw new Exception('การบันทึกข้อมูลลงฐานข้อมูลล้มเหลว: ' . $stmt->error);
        }
        
        $stmt->close();
        $conn->close();

        $encryption_success = true;
    } catch (Exception $e) {
        // บันทึก error log
        error_log('ข้อผิดพลาดการเข้ารหัสภาพ: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        error_log('OpenSSL errors: ' . openssl_error_string());
        
        $encryption_error = $e->getMessage();
        
        // ปิดการเชื่อมต่อฐานข้อมูลหากยังเปิดอยู่
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
    }
}

// ลบไฟล์เก่าเกิน 7 วัน เฉพาะเมื่อไดเร็กทอรี่มีอยู่จริง
if (is_dir($upload_dir)) {
    try {
        // เชื่อมต่อฐานข้อมูลเพื่อเรียกข้อมูลไฟล์เก่า
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception('เชื่อมต่อฐานข้อมูลล้มเหลว: ' . $conn->connect_error);
        }
        
        // คำนวณวันที่เก่ากว่า 7 วัน
        $expire_date = date('Y-m-d H:i:s', time() - (7 * 24 * 60 * 60));
        
        // ค้นหาไฟล์เก่าในฐานข้อมูล
        $sql = "SELECT member_id, id_card_image FROM team_members WHERE created_at < ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $expire_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // ลบไฟล์จากระบบไฟล์
            if (!empty($row['id_card_image']) && file_exists($row['id_card_image'])) {
                @unlink($row['id_card_image']);
            }
            
            // อัพเดทข้อมูลในฐานข้อมูล
            $update_sql = "UPDATE team_members SET id_card_image = NULL, encryption_key = NULL, iv = NULL, tag = NULL WHERE member_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $row['member_id']);
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log('ข้อผิดพลาดในการลบไฟล์เก่า: ' . $e->getMessage());
    }
}
?>