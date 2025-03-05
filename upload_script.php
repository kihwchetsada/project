<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['csrf_token_time'])) {
    $_SESSION['csrf_token_time'] = 0;
}

// สร้าง CSRF token พร้อมกำหนดอายุ
if (empty($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || time() - $_SESSION['csrf_token_time'] > 3600) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// การตั้งค่าการจัดการข้อผิดพลาด
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/secure/error.log');

// ตรวจสอบและประมวลผลการอัปโหลด
$encryption_success = false;
$encryption_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // ตรวจสอบ CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('การร้องขอไม่ถูกต้อง');
        }

        // ตรวจสอบการอัปโหลดไฟล์
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('การอัปโหลดไฟล์ล้มเหลว');
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
            throw new Exception('ประเภทไฟล์ไม่ถูกต้อง');
        }

        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            throw new Exception('นามสกุลไฟล์ไม่ถูกต้อง');
        }

        // อ่านข้อมูลภาพ
        $image = file_get_contents($_FILES['image']['tmp_name']);

        // สร้างคีย์และ IV แบบปลอดภัย
        $encryption_key = random_bytes(32);
        $cipher = 'aes-256-gcm';
        $iv_length = openssl_cipher_iv_length($cipher);
        $iv = random_bytes($iv_length);

        // เข้ารหัสภาพด้วย AES-256-GCM
        $encrypted_image = openssl_encrypt(
            $image, 
            $cipher, 
            $encryption_key, 
            OPENSSL_RAW_DATA, 
            $iv, 
            $tag
        );

        // สร้างชื่อไฟล์แบบสุ่ม
        $encrypted_filename = bin2hex(random_bytes(16)) . '.enc';
        $key_filename = bin2hex(random_bytes(16)) . '_key.txt';
        $iv_filename = bin2hex(random_bytes(16)) . '_iv.txt';
        $tag_filename = bin2hex(random_bytes(16)) . '_tag.txt';

        // ตรวจสอบและสร้างโฟลเดอร์
        $upload_dir = 'uploads';
        $keys_dir = 'keys';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0750, true);
        }

        if (!is_dir($keys_dir)) {
            mkdir($keys_dir, 0750, true);
        }

        // บันทึกไฟล์เข้ารหัสและกำหนดสิทธิ์ไฟล์
        file_put_contents($upload_dir . '/' . $encrypted_filename, $encrypted_image);
        chmod($upload_dir . '/' . $encrypted_filename, 0640);

        file_put_contents($keys_dir . '/' . $key_filename, base64_encode($encryption_key));
        file_put_contents($keys_dir . '/' . $iv_filename, base64_encode($iv));
        file_put_contents($keys_dir . '/' . $tag_filename, base64_encode($tag));

        $encryption_success = true;
    } catch (Exception $e) {
        // บันทึก error log และแจ้งเตือนทางอีเมล
        error_log($e->getMessage());
        mail('chatsadaphon.wo@rmuti.ac.th', 'ระบบเข้ารหัสภาพเกิดข้อผิดพลาด', $e->getMessage());
        $encryption_error = $e->getMessage();
    }
}

// ลบไฟล์เก่าเกิน 7 วัน
$files = glob($upload_dir . '/*.enc');
$expire_time = 7 * 24 * 60 * 60; // 7 วัน

foreach ($files as $file) {
    if (filemtime($file) < time() - $expire_time) {
        unlink($file);
    }
}
?>
