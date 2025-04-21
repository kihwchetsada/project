<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once('key_storage.php');

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
            throw new Exception('เซิร์ฟเวอร์ไม่รองรับการเข้ารหัสแบบ AES-256-GCM');
        }

        // สร้าง IV (Initialization Vector) สำหรับการเข้ารหัส
        $iv = openssl_random_pseudo_bytes(12); // GCM mode ต้องการ IV ขนาด 12 bytes
        if ($iv === false) {
            throw new Exception('ไม่สามารถสร้าง IV สำหรับการเข้ารหัส');
        }

        // ดึงคีย์การเข้ารหัสจากไฟล์ key_storage.php
        $encryption_key = getEncryptionKey();
        if (empty($encryption_key)) {
            throw new Exception('ไม่พบคีย์การเข้ารหัส');
        }

        // เข้ารหัสข้อมูลภาพ
        $tag = '';
        $encrypted_data = openssl_encrypt(
            $image_data,
            'aes-256-gcm',
            $encryption_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16 // Authentication tag length
        );

        if ($encrypted_data === false) {
            throw new Exception('การเข้ารหัสล้มเหลว: ' . openssl_error_string());
        }

        // สร้างชื่อไฟล์ที่ไม่ซ้ำกัน
        $unique_id = bin2hex(random_bytes(16));
        $encrypted_filename = $unique_id . '.enc';

        // บันทึกข้อมูลที่เข้ารหัสแล้ว
        $encrypted_path = $upload_dir . '/' . $encrypted_filename;
        
        // เขียนไฟล์ด้วยรูปแบบที่ประกอบด้วย IV + Tag + ข้อมูลที่เข้ารหัสแล้ว
        $file_content = $iv . $tag . $encrypted_data;
        if (file_put_contents($encrypted_path, $file_content) === false) {
            throw new Exception('ไม่สามารถบันทึกไฟล์ที่เข้ารหัส');
        }

        // ดึงข้อมูลจากฟอร์ม
        $participant_name = $conn->real_escape_string($_POST['participant_name'] ?? '');
        $email = $conn->real_escape_string($_POST['email'] ?? '');
        $phone = $conn->real_escape_string($_POST['phone'] ?? '');
        $tournament_id = intval($_POST['tournament_id'] ?? 0);
        
        // ตรวจสอบความถูกต้องของข้อมูลเพิ่มเติม
        if (empty($participant_name)) {
            throw new Exception('กรุณาระบุชื่อผู้เข้าร่วม');
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('อีเมลไม่ถูกต้อง');
        }
        
        if (empty($phone)) {
            throw new Exception('กรุณาระบุเบอร์โทรศัพท์');
        }
        
        if ($tournament_id <= 0) {
            throw new Exception('กรุณาเลือกการแข่งขัน');
        }
        
        // เพิ่มข้อมูลลงในฐานข้อมูล
        $sql = "INSERT INTO registrations (participant_name, email, phone, tournament_id, image_path, registration_date) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('การเตรียม SQL statement ล้มเหลว: ' . $conn->error);
        }
        
        $stmt->bind_param("sssss", $participant_name, $email, $phone, $tournament_id, $encrypted_filename);
        
        if (!$stmt->execute()) {
            throw new Exception('การบันทึกข้อมูลลงฐานข้อมูลล้มเหลว: ' . $stmt->error);
        }
        
        $registration_id = $conn->insert_id;
        $stmt->close();
        
        // บันทึกข้อมูลการเข้ารหัสเพิ่มเติม (อาจเก็บในตารางแยก)
        $sql = "INSERT INTO encryption_metadata (registration_id, original_filename, mime_type, encryption_method) 
                VALUES (?, ?, ?, 'AES-256-GCM')";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('การเตรียม SQL statement สำหรับ metadata ล้มเหลว: ' . $conn->error);
        }
        
        $original_filename = $conn->real_escape_string($_FILES['image']['name']);
        $stmt->bind_param("iss", $registration_id, $original_filename, $mime_type);
        
        if (!$stmt->execute()) {
            throw new Exception('การบันทึกข้อมูล metadata ล้มเหลว: ' . $stmt->error);
        }
        
        $stmt->close();
        $conn->close();
        
        $encryption_success = true;
        
    } catch (Exception $e) {
        $encryption_error = $e->getMessage();
        error_log('ข้อผิดพลาด: ' . $encryption_error);
    }
}
?>