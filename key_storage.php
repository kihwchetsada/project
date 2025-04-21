<?php
/**
 * key_storage.php - ไฟล์สำหรับจัดการการเก็บและเรียกใช้คีย์เข้ารหัสแยกจากฐานข้อมูล
 * สร้างไว้ในไดเร็กทอรี่แยกต่างหากเพื่อความปลอดภัย
 */

// กำหนดค่าเบื้องต้น
define('KEYS_DIRECTORY', 'secure_keys');
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
 * @return array รหัสอ้างอิงของคีย์ที่เก็บ (จะเก็บในฐานข้อมูลแทนคีย์จริง)
 */
function save_encryption_keys($identifier, $key, $iv, $tag = '') {
    ensure_keys_directory();
    
    // สร้างชื่อไฟล์ที่ไม่ซ้ำกัน
    $key_id = uniqid('', true) . '_' . hash('sha256', $identifier . time() . random_bytes(16));
    
    // บันทึกคีย์ลงในไฟล์แยกกัน
    $key_file = KEYS_DIRECTORY . '/' . KEY_PREFIX . $key_id . KEYS_EXTENSION;
    $iv_file = KEYS_DIRECTORY . '/' . IV_PREFIX . $key_id . KEYS_EXTENSION;
    $tag_file = KEYS_DIRECTORY . '/' . TAG_PREFIX . $key_id . KEYS_EXTENSION;
    
    // บันทึกไฟล์
    if (file_put_contents($key_file, $key) === false) {
        throw new Exception('ไม่สามารถบันทึกไฟล์คีย์ได้');
    }
    chmod($key_file, 0600); // เฉพาะเจ้าของอ่านเขียนได้
    
    if (file_put_contents($iv_file, $iv) === false) {
        // ลบไฟล์คีย์หากการบันทึก IV ล้มเหลว
        @unlink($key_file);
        throw new Exception('ไม่สามารถบันทึกไฟล์ IV ได้');
    }
    chmod($iv_file, 0600);
    
    // บันทึก tag หากมี
    if (!empty($tag)) {
        if (file_put_contents($tag_file, $tag) === false) {
            // ลบไฟล์ที่เกี่ยวข้องหากการบันทึก tag ล้มเหลว
            @unlink($key_file);
            @unlink($iv_file);
            throw new Exception('ไม่สามารถบันทึกไฟล์ tag ได้');
        }
        chmod($tag_file, 0600);
    }
    
    return $key_id;
}

/**
 * อ่านคีย์เข้ารหัสจากไฟล์
 * 
 * @param string $key_id รหัสอ้างอิงของคีย์ที่เก็บในฐานข้อมูล
 * @return array ข้อมูลคีย์เข้ารหัส, IV และ tag ในรูปแบบ binary
 */
function get_Encryption_keys($key_id) {
    if (empty($key_id)) {
        throw new Exception('ไม่พบรหัสอ้างอิงของคีย์');
    }
    
    $key_file = KEYS_DIRECTORY . '/' . KEY_PREFIX . $key_id . KEYS_EXTENSION;
    $iv_file = KEYS_DIRECTORY . '/' . IV_PREFIX . $key_id . KEYS_EXTENSION;
    $tag_file = KEYS_DIRECTORY . '/' . TAG_PREFIX . $key_id . KEYS_EXTENSION;
    
    if (!file_exists($key_file) || !file_exists($iv_file)) {
        throw new Exception('ไม่พบไฟล์คีย์หรือ IV ที่ต้องการ');
    }
    
    $key = file_get_contents($key_file);
    $iv = file_get_contents($iv_file);
    $tag = file_exists($tag_file) ? file_get_contents($tag_file) : '';
    
    if ($key === false || $iv === false) {
        throw new Exception('ไม่สามารถอ่านไฟล์คีย์หรือ IV ได้');
    }
    
    return [
        'key' => $key,
        'iv' => $iv,
        'tag' => $tag
    ];
}

/**
 * ลบคีย์เข้ารหัสออกจากระบบ
 * 
 * @param string $key_id รหัสอ้างอิงของคีย์ที่ต้องการลบ
 * @return boolean ผลลัพธ์การลบ
 */
function delete_encryption_keys($key_id) {
    if (empty($key_id)) {
        return false;
    }
    
    $key_file = KEYS_DIRECTORY . '/' . KEY_PREFIX . $key_id . KEYS_EXTENSION;
    $iv_file = KEYS_DIRECTORY . '/' . IV_PREFIX . $key_id . KEYS_EXTENSION;
    $tag_file = KEYS_DIRECTORY . '/' . TAG_PREFIX . $key_id . KEYS_EXTENSION;
    
    $result_key = file_exists($key_file) ? @unlink($key_file) : true;
    $result_iv = file_exists($iv_file) ? @unlink($iv_file) : true;
    $result_tag = file_exists($tag_file) ? @unlink($tag_file) : true;
    
    return ($result_key && $result_iv && $result_tag);
}