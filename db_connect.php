<?php
/**
 * ไฟล์สำหรับการเชื่อมต่อกับฐานข้อมูล
 * ใช้ PDO สำหรับความปลอดภัยและประสิทธิภาพ
 */

// เริ่ม session สำหรับใช้งาน
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ข้อมูลการเชื่อมต่อฐานข้อมูล
$db_host = 'localhost';     // ชื่อโฮสต์หรือที่อยู่ IP ของเซิร์ฟเวอร์ฐานข้อมูล
$db_name = 'tournament_registration';   // ชื่อฐานข้อมูล
$db_user = 'root';          // ชื่อผู้ใช้ฐานข้อมูล
$db_pass = '';              // รหัสผ่านฐานข้อมูล
$db_charset = 'utf8mb4';    // ชุดอักขระที่ใช้ (utf8mb4 รองรับ emoji และอักขระพิเศษ)

// กำหนดค่า DSN (Data Source Name)
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";

// กำหนดค่าตัวเลือกของ PDO
$options = [
    // แสดงข้อผิดพลาดในรูปแบบ exception
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // รูปแบบการดึงข้อมูลเป็น associative array
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // ไม่แปลงข้อมูลเป็น native PHP types
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// เริ่มการเชื่อมต่อกับฐานข้อมูล
try {
    $conn = new PDO($dsn, $db_user, $db_pass, $options);
    // echo "เชื่อมต่อฐานข้อมูลสำเร็จ";
} catch (PDOException $e) {
    // กำหนดการรายงานข้อผิดพลาด
    // ในสภาพแวดล้อมการผลิตจริง ควรเก็บ log แทนการแสดงข้อผิดพลาดตรงๆ
    // throw new PDOException($e->getMessage(), (int)$e->getCode());
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
}
?>