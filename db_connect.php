<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // ข้อมูลการเชื่อมต่อฐานข้อมูล
    $db_host = 'localhost';     // ชื่อโฮสต์หรือที่อยู่ IP ของเซิร์ฟเวอร์ฐานข้อมูล
    $db_name = 'rmuti_esport_surin';   // ชื่อฐานข้อมูล
    $db_user = 'certificate';          // ชื่อผู้ใช้ฐานข้อมูล
    $db_pass = 'certificate1234';               // รหัสผ่านฐานข้อมูล
    $db_charset = 'utf8mb4';    // ชุดอักขระที่ใช้ (utf8mb4 รองรับ emoji และอักขระพิเศษ)

    // กำหนดค่า DSN (Data Source Name)
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";

    // กำหนดค่าตัวเลือกของ PDO
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // สร้าง CSRF token พร้อมกำหนดอายุ
    if (!isset($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token_time'] = 0;
    }

    if (empty($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || time() - $_SESSION['csrf_token_time'] > 3600) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    // เริ่มการเชื่อมต่อกับฐานข้อมูล
    try {
        $conn = new PDO($dsn, $db_user, $db_pass, $options);
    } catch (PDOException $e) {
        die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
    }

?>
