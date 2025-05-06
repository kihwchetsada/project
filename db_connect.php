<?php
    /**
     * ไฟล์สำหรับการเชื่อมต่อกับฐานข้อมูล
     * ใช้ PDO สำหรับความปลอดภัยและประสิทธิภาพ
     */

    // เริ่ม session สำหรับใช้งาน
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // กำหนดค่าคงที่สำหรับไดเร็กทอรี่ (ไม่รวม KEYS_DIRECTORY ที่ไม่ได้ใช้แล้ว)
    define('UPLOAD_DIR', 'uploads');

    // สร้างไดเร็กทอรี่ที่จำเป็น
    if (!is_dir(UPLOAD_DIR)) {
        if (!@mkdir(UPLOAD_DIR, 0755, true)) {
            error_log("ไม่สามารถสร้างไดเร็กทอรี่ uploads: " . error_get_last()['message']);
        }
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
        createTablesIfNotExist($conn);
    } catch (PDOException $e) {
        die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
    }

    function createTablesIfNotExist($conn) {
        $conn->exec("CREATE TABLE IF NOT EXISTS teams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            competition_type VARCHAR(100) NOT NULL,
            team_name VARCHAR(100) NOT NULL,
            coach_name VARCHAR(100) NOT NULL,
            coach_phone VARCHAR(20) NOT NULL,
            leader_school VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL,
            status VARCHAR(20) DEFAULT 'pending'
        )");

        $conn->exec("CREATE TABLE IF NOT EXISTS team_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            member_name VARCHAR(100) NOT NULL,
            game_name VARCHAR(100),
            age INT,
            phone VARCHAR(255),
            position VARCHAR(50),
            birthdate DATE,
            phone_key TEXT,
            phone_iv TEXT,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
        )");

        $conn->exec("CREATE TABLE IF NOT EXISTS registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            participant_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            tournament_id INT NOT NULL,
            image_path VARCHAR(255),
            registration_date DATETIME NOT NULL
        )");

        $conn->exec("CREATE TABLE IF NOT EXISTS encryption_metadata (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registration_id INT NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            encryption_method VARCHAR(50) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
        )");
    }

    function getEncryptionKey() {
        return random_bytes(32);
    }
?>
