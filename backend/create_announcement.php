<?php
session_start();

// ✅ เช็กว่าเป็น organizer หรือไม่
if (!isset($_SESSION['userData']) || $_SESSION['userData']['role'] !== 'organizer') {
    header('Location: login.php');
    exit();
}

// ✅ เช็กว่าเป็น POST request หรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_announcement.php');
    exit();
}

// ✅ รับค่าจากฟอร์ม
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$priority = $_POST['priority'] ?? '';
$organizer_id = $_SESSION['userData']['id'];

// ✅ ตรวจสอบข้อมูลที่จำเป็น
$errors = [];

if (empty($title)) {
    $errors[] = 'กรุณากรอกหัวข้อประกาศ';
}

if (empty($description)) {
    $errors[] = 'กรุณากรอกรายละเอียด';
}

if (empty($category)) {
    $errors[] = 'กรุณากรอกหมวดหมู่';
}

if (!in_array($priority, ['สูง', 'ปานกลาง', 'ต่ำ'])) {
    $errors[] = 'กรุณาเลือกระดับความสำคัญ';
}

// ✅ การจัดการอัปโหลดรูปภาพ
$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/announcements/';
    
    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_tmp = $_FILES['image']['tmp_name'];
    $file_name = $_FILES['image']['name'];
    $file_size = $_FILES['image']['size'];
    $file_error = $_FILES['image']['error'];
    
    // ตรวจสอบขนาดไฟล์ (5MB)
    if ($file_size > 5 * 1024 * 1024) {
        $errors[] = 'ขนาดไฟล์ต้องไม่เกิน 5MB';
    }
    
    // ตรวจสอบประเภทไฟล์
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($file_tmp);
    
    if (!in_array($file_type, $allowed_types)) {
        $errors[] = 'รองรับเฉพาะไฟล์ JPG, PNG, GIF, WEBP เท่านั้น';
    }
    
    // ถ้าไม่มีข้อผิดพลาด ให้อัปโหลดไฟล์
    if (empty($errors)) {
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_filename = 'announcement_' . time() . '_' . uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file_tmp, $target_path)) {
            $image_path = $target_path;
        } else {
            $errors[] = 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์';
        }
    }
}

// ✅ ถ้ามีข้อผิดพลาด ให้กลับไปหน้าเดิม
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: add_announcement.php');
    exit();
}

// ✅ เชื่อมต่อฐานข้อมูล
try {
    $host = 'localhost';
    $dbname = 'your_database_name';
    $username = 'your_username';
    $password = 'your_password';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ✅ เตรียม SQL statement
    $sql = "INSERT INTO announcements (title, description, category, priority, image_path, organizer_id, created_at, updated_at) 
            VALUES (:title, :description, :category, :priority, :image_path, :organizer_id, NOW(), NOW())";
    
    $stmt = $pdo->prepare($sql);
    
    // ✅ ผูกค่าตัวแปร
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':priority', $priority);
    $stmt->bindParam(':image_path', $image_path);
    $stmt->bindParam(':organizer_id', $organizer_id);
    
    // ✅ ดำเนินการ execute
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'เพิ่มประกาศสำเร็จแล้ว!';
        header('Location: announcements.php');
        exit();
    } else {
        throw new Exception('ไม่สามารถบันทึกประกาศได้');
    }
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['errors'] = ['เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล'];
    header('Location: add_announcement.php');
    exit();
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    $_SESSION['errors'] = ['เกิดข้อผิดพลาดในการบันทึกข้อมูล'];
    header('Location: add_announcement.php');
    exit();
}

// ✅ ฟังก์ชันเสริม: ปรับขนาดรูปภาพ (ถ้าต้องการ)
function resizeImage($source, $destination, $max_width = 800, $max_height = 600) {
    $image_info = getimagesize($source);
    $width = $image_info[0];
    $height = $image_info[1];
    $mime = $image_info['mime'];
    
    // คำนวณขนาดใหม่
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = intval($width * $ratio);
    $new_height = intval($height * $ratio);
    
    // สร้าง canvas ใหม่
    $canvas = imagecreatetruecolor($new_width, $new_height);
    
    // โหลดรูปภาพต้นฉบับ
    switch ($mime) {
        case 'image/jpeg':
            $source_image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng($source);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            break;
        case 'image/gif':
            $source_image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // ปรับขนาดรูปภาพ
    imagecopyresampled($canvas, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // บันทึกรูปภาพ
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($canvas, $destination, 85);
            break;
        case 'image/png':
            imagepng($canvas, $destination);
            break;
        case 'image/gif':
            imagegif($canvas, $destination);
            break;
    }
    
    // ทำความสะอาด memory
    imagedestroy($canvas);
    imagedestroy($source_image);
    
    return true;
}

// ✅ ฟังก์ชันตรวจสอบและทำความสะอาดข้อมูล
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// ✅ ฟังก์ชันสร้าง slug สำหรับ URL
function createSlug($string) {
    $slug = preg_replace('/[^a-zA-Z0-9ก-๙\s]/', '', $string);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = strtolower($slug);
    return $slug;
}

// ✅ ฟังก์ชันตรวจสอบสิทธิ์
function checkPermission($required_role) {
    if (!isset($_SESSION['userData']) || $_SESSION['userData']['role'] !== $required_role) {
        return false;
    }
    return true;
}

// ✅ ฟังก์ชันบันทึก log
function logActivity($action, $details = '') {
    $log_file = 'logs/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['userData']['id'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $log_entry = "[$timestamp] User: $user_id | IP: $ip | Action: $action | Details: $details\n";
    
    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
    }
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// ✅ SQL สำหรับสร้างตาราง announcements
/*
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    priority ENUM('สูง', 'ปานกลาง', 'ต่ำ') NOT NULL,
    image_path VARCHAR(500) NULL,
    organizer_id INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE
);
*/
?>