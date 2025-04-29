<?php
/**
 * key_storage.php - ไฟล์สำหรับจัดการการเก็บและเรียกใช้คีย์เข้ารหัสแยกจากฐานข้อมูล
 * สร้างไว้ในไดเร็กทอรี่แยกต่างหากเพื่อความปลอดภัย
 */

// เริ่ม session สำหรับใช้งาน
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// กำหนดค่าเบื้องต้น (ถ้ายังไม่มีค่ากำหนดไว้)
if (!defined('KEYS_DIRECTORY')) {
    define('KEYS_DIRECTORY', 'secure_keys');
}

define('KEYS_EXTENSION', '.key');
define('KEY_PREFIX', 'key_');
define('IV_PREFIX', 'iv_');
define('TAG_PREFIX', 'tag_');

/**
 * สร้างไดเร็กทอรี่สำหรับเก็บคีย์หากยังไม่มี
 */
function ensure_keys_directory() {
    if (!is_dir(KEYS_DIRECTORY)) {
        if (!@mkdir(KEYS_DIRECTORY, 0700, true)) { // ใช้ 0700 เพื่อให้เฉพาะเจ้าของเข้าถึงได้
            throw new Exception('ไม่สามารถสร้างไดเร็กทอรี่สำหรับเก็บคีย์: ' . KEYS_DIRECTORY);
        }
        
        // สร้างไฟล์ .htaccess เพื่อป้องกันการเข้าถึงจากเว็บ
        $htaccess_content = "# Disable directory browsing\nOptions -Indexes\n\n# Deny access to all files\nDeny from all";
        file_put_contents(KEYS_DIRECTORY . '/.htaccess', $htaccess_content);
    }
    
    // ตรวจสอบสิทธิ์การเขียน
    if (!is_writable(KEYS_DIRECTORY)) {
        throw new Exception('ไม่มีสิทธิ์เขียนไฟล์ในไดเร็กทอรี่: ' . KEYS_DIRECTORY);
    }
}

/**
 * บันทึกคีย์เข้ารหัสลงในไฟล์
 * 
 * @param string $identifier รหัสอ้างอิงเพื่อใช้ในการสร้างชื่อไฟล์ (เช่น team_id + member_id)
 * @param string $key คีย์เข้ารหัสในรูปแบบ binary
 * @param string $iv ค่า IV ในรูปแบบ binary
 * @param string $tag ค่า tag ในรูปแบบ binary (ใช้กับ GCM mode)
 * @return string รหัสอ้างอิงของคีย์ที่เก็บ (จะเก็บในฐานข้อมูลแทนคีย์จริง)
 */

 define('CIPHER_PREFIX', 'cipher_'); // <--- เพิ่มตรงนี้

function save_encryption_keys($identifier, $key, $iv, $tag = '', $cipher = 'aes-256-cbc') {
    ensure_keys_directory();
    
    $key_id = uniqid('', true) . '_' . hash('sha256', $identifier . time() . random_bytes(16));

    $key_file = KEYS_DIRECTORY . '/' . KEY_PREFIX . $key_id . KEYS_EXTENSION;
    $iv_file = KEYS_DIRECTORY . '/' . IV_PREFIX . $key_id . KEYS_EXTENSION;
    $tag_file = KEYS_DIRECTORY . '/' . TAG_PREFIX . $key_id . KEYS_EXTENSION;
    $cipher_file = KEYS_DIRECTORY . '/' . CIPHER_PREFIX . $key_id . KEYS_EXTENSION; // <--- เพิ่มไฟล์ cipher

    if (file_put_contents($key_file, $key) === false) {
        throw new Exception('ไม่สามารถบันทึกไฟล์คีย์ได้');
    }
    chmod($key_file, 0600);

    if (file_put_contents($iv_file, $iv) === false) {
        @unlink($key_file);
        throw new Exception('ไม่สามารถบันทึกไฟล์ IV ได้');
    }
    chmod($iv_file, 0600);

    if (!empty($tag)) {
        if (file_put_contents($tag_file, $tag) === false) {
            @unlink($key_file);
            @unlink($iv_file);
            throw new Exception('ไม่สามารถบันทึกไฟล์ tag ได้');
        }
        chmod($tag_file, 0600);
    }

    if (file_put_contents($cipher_file, $cipher) === false) {
        @unlink($key_file);
        @unlink($iv_file);
        if (!empty($tag)) @unlink($tag_file);
        throw new Exception('ไม่สามารถบันทึกไฟล์ cipher ได้');
    }
    chmod($cipher_file, 0600);

    return $key_id;
}

function get_encryption_keys($key_id) {
    if (empty($key_id)) {
        throw new Exception('ไม่พบรหัสอ้างอิงของคีย์');
    }
    
    $key_file = KEYS_DIRECTORY . '/' . KEY_PREFIX . $key_id . KEYS_EXTENSION;
    $iv_file = KEYS_DIRECTORY . '/' . IV_PREFIX . $key_id . KEYS_EXTENSION;
    $tag_file = KEYS_DIRECTORY . '/' . TAG_PREFIX . $key_id . KEYS_EXTENSION;
    $cipher_file = KEYS_DIRECTORY . '/' . CIPHER_PREFIX . $key_id . KEYS_EXTENSION; // <--- อ่าน cipher

    if (!file_exists($key_file) || !file_exists($iv_file) || !file_exists($cipher_file)) {
        throw new Exception('ไม่พบไฟล์คีย์, IV หรือ Cipher ที่ต้องการ');
    }
    
    $key = file_get_contents($key_file);
    $iv = file_get_contents($iv_file);
    $tag = file_exists($tag_file) ? file_get_contents($tag_file) : '';
    $cipher = file_get_contents($cipher_file); // <--- โหลด cipher

    if ($key === false || $iv === false || $cipher === false) {
        throw new Exception('ไม่สามารถอ่านไฟล์คีย์, IV หรือ Cipher ได้');
    }
    
    return [
        'key' => $key,
        'iv' => $iv,
        'tag' => $tag,
        'cipher' => trim($cipher)
    ];
}

?>