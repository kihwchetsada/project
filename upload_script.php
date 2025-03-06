<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// กำหนดค่าไดเร็กทอรี่ก่อน
$upload_dir = 'uploads';
$keys_dir = 'keys';

// สร้างไดเร็กทอรี่ตั้งแต่เริ่มต้น
if (!is_dir($upload_dir)) {
    if (!@mkdir($upload_dir, 0755, true)) {
        $create_dir_error = error_get_last();
        error_log("ไม่สามารถสร้างไดเร็กทอรี่ uploads: " . $create_dir_error['message']);
    }
}

if (!is_dir($keys_dir)) {
    if (!@mkdir($keys_dir, 0755, true)) {
        $create_dir_error = error_get_last();
        error_log("ไม่สามารถสร้างไดเร็กทอรี่ keys: " . $create_dir_error['message']);
    }
}

// ตรวจสอบสิทธิ์การเขียน
if (!is_writable($upload_dir)) {
    error_log("ไม่มีสิทธิ์เขียนไฟล์ในไดเร็กทอรี่ $upload_dir");
}

if (!is_writable($keys_dir)) {
    error_log("ไม่มีสิทธิ์เขียนไฟล์ในไดเร็กทอรี่ $keys_dir");
}

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
ini_set('display_errors', 1); // เปลี่ยนเป็น 1 เพื่อดีบัก
ini_set('log_errors', 1);
ini_set('error_log', 'error.log'); // เปลี่ยนเป็นไฟล์ที่สามารถเขียนได้

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

        // เข้ารหัสภาพ
        if ($cipher === 'aes-256-gcm') {
            $tag = '';
            $encrypted_image = openssl_encrypt(
                $image_data, 
                $cipher, 
                $encryption_key, 
                OPENSSL_RAW_DATA, 
                $iv, 
                $tag
            );
        } else {
            // ใช้ CBC mode ซึ่งไม่มี tag
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
        $timestamp = time();  // หรือใช้ date('YmdHis') เพื่อให้รูปแบบเหมือนวันที่และเวลา
        $encrypted_filename = $timestamp . '.enc';
        $key_filename = $timestamp . '_key.txt';
        $iv_filename = $timestamp . '_iv.txt';

        
        // บันทึกไฟล์เข้ารหัสและกำหนดสิทธิ์ไฟล์
        if (file_put_contents($upload_dir . '/' . $encrypted_filename, $encrypted_image) === false) {
            throw new Exception('ไม่สามารถบันทึกไฟล์เข้ารหัสได้');
        }
        @chmod($upload_dir . '/' . $encrypted_filename, 0644);

        if (file_put_contents($keys_dir . '/' . $key_filename, base64_encode($encryption_key)) === false) {
            // ลบไฟล์เข้ารหัสหากไม่สามารถบันทึกคีย์ได้
            @unlink($upload_dir . '/' . $encrypted_filename);
            throw new Exception('ไม่สามารถบันทึกคีย์เข้ารหัสได้');
        }
        @chmod($keys_dir . '/' . $key_filename, 0644);

        if (file_put_contents($keys_dir . '/' . $iv_filename, base64_encode($iv)) === false) {
            // ลบไฟล์ที่เกี่ยวข้องหากไม่สามารถบันทึก IV ได้
            @unlink($upload_dir . '/' . $encrypted_filename);
            @unlink($keys_dir . '/' . $key_filename);
            throw new Exception('ไม่สามารถบันทึก IV ได้');
        }
        @chmod($keys_dir . '/' . $iv_filename, 0644);

        // บันทึก tag เมื่อใช้ GCM mode
        if ($cipher === 'aes-256-gcm' && !empty($tag)) {
            $tag_filename = bin2hex(random_bytes(8)) . '_tag.txt';
            if (file_put_contents($keys_dir . '/' . $tag_filename, base64_encode($tag)) === false) {
                throw new Exception('ไม่สามารถบันทึก authentication tag ได้');
            }
            @chmod($keys_dir . '/' . $tag_filename, 0644);
        }

        $encryption_success = true;
    } catch (Exception $e) {
        // บันทึก error log
        error_log('ข้อผิดพลาดการเข้ารหัสภาพ: ' . $e->getMessage());
        
        // ถ้าต้องการส่งอีเมลแจ้งเตือน ให้เปิดคอมเมนต์บรรทัดนี้
        // mail('chatsadaphon.wo@rmuti.ac.th', 'ระบบเข้ารหัสภาพเกิดข้อผิดพลาด', $e->getMessage());
        
        $encryption_error = $e->getMessage();
    }
}

// ลบไฟล์เก่าเกิน 7 วัน เฉพาะเมื่อไดเร็กทอรี่มีอยู่จริง
if (is_dir($upload_dir)) {
    $files = glob($upload_dir . '/*.enc');
    if ($files) {
        $expire_time = 7 * 24 * 60 * 60; // 7 วัน
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < time() - $expire_time) {
                @unlink($file);
            }
        }
    }
}
?>