<?php
// เส้นทางของไฟล์ที่ถูกเข้ารหัส, คีย์, IV และ Tag
$encrypted_filename = 'uploads/your_encrypted_file.enc';
$key_filename = 'keys/your_encryption_key_key.txt';
$iv_filename = 'keys/your_encryption_iv.txt';
$tag_filename = 'keys/your_encryption_tag.txt';

try {
    // อ่านไฟล์ที่เข้ารหัส, คีย์, IV และ Tag
    $encrypted_image = file_get_contents($encrypted_filename);
    $encryption_key = base64_decode(file_get_contents($key_filename));
    $iv = base64_decode(file_get_contents($iv_filename));
    $tag = base64_decode(file_get_contents($tag_filename));

    // ถอดรหัสภาพด้วย AES-256-GCM
    $cipher = 'aes-256-gcm';
    $decrypted_image = openssl_decrypt(
        $encrypted_image, 
        $cipher, 
        $encryption_key, 
        OPENSSL_RAW_DATA, 
        $iv, 
        $tag
    );

    // ตรวจสอบว่าถอดรหัสสำเร็จหรือไม่
    if ($decrypted_image === false) {
        throw new Exception('ไม่สามารถถอดรหัสภาพได้');
    }

    // แสดงภาพในเบราว์เซอร์
    header('Content-Type: image/jpeg'); // ปรับตามประเภทของภาพ เช่น image/png
    echo $decrypted_image;
} catch (Exception $e) {
    echo 'ข้อผิดพลาด: ' . $e->getMessage();
}
?>
