<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db_connect.php';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset("utf8");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. รับค่า id จาก URL
if (!isset($_GET['id'])) {
    die("❌ ไม่พบ ID ของประกาศที่ต้องการแก้ไข");
}
$id = intval($_GET['id']);

// 2. ดึงข้อมูลประกาศเดิมมาแสดงในฟอร์ม
$stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$announcement = $result->fetch_assoc();
$stmt->close(); 

if (!$announcement) {
    die("❌ ไม่พบข้อมูลประกาศสำหรับ ID: " . $id);
}

// 3. ตรวจสอบเมื่อมีการส่งฟอร์ม (กดปุ่มบันทึก)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $priority = trim($_POST['priority']);
    $imagePath = $announcement['image_path']; // ใช้รูปเดิมเป็นค่าเริ่มต้น

    // 4. จัดการการอัปโหลดไฟล์รูปภาพใหม่ (ถ้ามี)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5 MB

        $fileType = $_FILES['image']['type'];
        $fileSize = $_FILES['image']['size'];
        $tmpName = $_FILES['image']['tmp_name'];

        // ป้องกันการเว้นวรรคในชื่อไฟล์
        $originalFileName = basename($_FILES['image']['name']);
        $safeFileName = str_replace(' ', '_', $originalFileName);
        $fileName = uniqid() . '_' . $safeFileName;
        
        $targetDir = 'uploads/';
        $targetPath = $targetDir . $fileName;

        if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }
            if (move_uploaded_file($tmpName, $targetPath)) {
                // อัปโหลดสำเร็จ, ลบไฟล์รูปเก่า (ถ้ามี)
                if (!empty($announcement['image_path']) && file_exists($targetDir . $announcement['image_path'])) {
                    unlink($targetDir . $announcement['image_path']);
                }
                $imagePath = $fileName; // อัปเดต path รูปใหม่
            } else {
                 $error = "❌ ไม่สามารถย้ายไฟล์ไปยังโฟลเดอร์ uploads ได้";
            }
        } else {
             $error = "❌ ประเภทไฟล์ไม่ได้รับอนุญาตหรือขนาดไฟล์ใหญ่เกิน 5MB";
        }
    }

    // 5. อัปเดตข้อมูลลงในฐานข้อมูล
    if (empty($error)) { 
        $stmt = $conn->prepare("UPDATE announcements SET title=?, description=?, category=?, priority=?, image_path=? WHERE id=?");
        $stmt->bind_param("sssssi", $title, $description, $category, $priority, $imagePath, $id);

        if ($stmt->execute()) {
            $success = "✅ บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว";
            // ดึงข้อมูลล่าสุดมาแสดงผลอีกครั้งหลังอัปเดต
            $stmt->close();
            $stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $announcement = $result->fetch_assoc();
            $stmt->close();
        } else {
            $error = "❌ เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขประกาศ | ID: <?= $id ?></title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f4f7f6;
        }
    </style>
</head>
<body class="flex justify-center items-center min-h-screen p-4 md:p-8">

    <div class="w-full max-w-3xl">
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
            <div class="flex items-center mb-6">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <h1 class="text-3xl font-bold text-gray-800">แก้ไขประกาศ</h1>
            </div>

            <?php if (!empty($success)): ?>
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg" role="alert">
                    <p class="font-bold">สำเร็จ!</p>
                    <p><?= htmlspecialchars($success) ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg" role="alert">
                    <p class="font-bold">เกิดข้อผิดพลาด</p>
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">หัวข้อประกาศ</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($announcement['title']) ?>" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">เนื้อหา</label>
                    <textarea id="description" name="description" required rows="6"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition"><?= htmlspecialchars($announcement['description']) ?></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่</label>
                        <input type="text" id="category" name="category" value="<?= htmlspecialchars($announcement['category']) ?>" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">ความสำคัญ</label>
                        <select id="priority" name="priority" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition bg-white">
                            <option value="สูง" <?= ($announcement['priority'] == 'สูง') ? 'selected' : '' ?>>สูง</option>
                            <option value="ปานกลาง" <?= ($announcement['priority'] == 'ปานกลาง') ? 'selected' : '' ?>>ปานกลาง</option>
                            <option value="ต่ำ" <?= ($announcement['priority'] == 'ต่ำ') ? 'selected' : '' ?>>ต่ำ</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพประกอบ</label>
                    <div class="flex items-center space-x-6">
                        <div class="shrink-0">
                            <p class="text-xs text-gray-500 mb-1">รูปภาพปัจจุบัน:</p>
                            <?php if (!empty($announcement['image_path']) && file_exists("uploads/" . $announcement['image_path'])): ?>
                                <img src="uploads/<?= htmlspecialchars($announcement['image_path']) ?>" alt="รูปภาพปัจจุบัน" class="h-20 w-20 object-cover rounded-md shadow-sm border">
                            <?php else: ?>
                                <div class="h-20 w-20 bg-gray-100 rounded-md flex items-center justify-center text-xs text-gray-400 border">ไม่มีรูป</div>
                            <?php endif; ?>
                        </div>
                        <label class="block">
                            <span class="sr-only">เลือกไฟล์ใหม่</span>
                            <input type="file" name="image" accept="image/*"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition"/>
                            <p class="text-xs text-gray-500 mt-1">อัปโหลดไฟล์ใหม่เพื่อแทนที่ (ขนาดไม่เกิน 5MB)</p>
                        </label>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6 flex items-center justify-between">
                     <a href="og_view_announcement.php" 
                       class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors duration-200 font-medium text-sm">
                        ← กลับไปหน้าจัดการ
                    </a>
                    <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                           <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>