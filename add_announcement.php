<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "announcements_db");
$conn->set_charset("utf8");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $priority = trim($_POST['priority']);
    $status = 'active'; // ใส่สถานะเริ่มต้น
    $imagePath = null;  // ตั้งค่าเริ่มต้น

    // ตรวจสอบและจัดการไฟล์รูปภาพ
    if (!empty($_FILES['image']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $fileType = $_FILES['image']['type'];
        $fileSize = $_FILES['image']['size'];
        $tmpName = $_FILES['image']['tmp_name'];
        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetDir = '../uploads/';
        $targetPath = $targetDir . $fileName;

        // ตรวจสอบประเภทไฟล์และขนาด
        if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            if (move_uploaded_file($tmpName, $targetPath)) {
                $imagePath = $fileName;
            } else {
                die("❌ ไม่สามารถอัปโหลดรูปภาพได้");
            }
        } else {
            die("❌ ประเภทไฟล์หรือขนาดไฟล์ไม่ถูกต้อง (รองรับ JPG, PNG, GIF และไม่เกิน 5MB)");
        }
    }

    $stmt = $conn->prepare("INSERT INTO announcements (title, description, category, priority, image_path, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssss", $title, $description, $category, $priority, $imagePath, $status);

    if ($stmt->execute()) {
        header("Location: annunciate.php?added=1");
        exit;
    } else {
        $error = "เกิดข้อผิดพลาดในการบันทึก: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มประกาศใหม่</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-xl">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">📝 เพิ่มประกาศใหม่</h2>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="block text-gray-700 mb-1">หัวข้อประกาศ</label>
                <input type="text" name="title" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-1">รายละเอียด</label>
                <textarea name="description" required rows="5" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-1">หมวดหมู่</label>
                <input type="text" name="category" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-1">ความสำคัญ</label>
                <select name="priority" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="สูง">สูง</option>
                    <option value="ปานกลาง">ปานกลาง</option>
                    <option value="ต่ำ">ต่ำ</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="flex items-center text-gray-700 font-semibold mb-4">
                    <i class="fas fa-image text-blue-600 mr-2"></i>
                    เลือกรูปภาพ
                </label>
                <div class="relative">
                    <input type="file" name="image" accept="image/*" id="imageInput" class="hidden" onchange="showFileName(this)">
                    <label for="imageInput" class="block w-full border-2 border-dashed border-gray-300 rounded-xl p-8 text-center 
                                              cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all duration-300 
                                              group bg-gray-50">
                        <div class="group-hover:animate-pulse-slow">
                            <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 group-hover:text-blue-500 transition-colors duration-300 mb-4"></i>
                            <p class="text-gray-600 text-lg font-medium mb-2">คลิกเพื่ออัปโหลดรูปภาพ</p>
                            <p class="text-gray-400">รองรับไฟล์ JPG, PNG, GIF (ขนาดสูงสุด 5MB)</p>
                        </div>
                    </label>
                </div>
                <div id="fileName" class="mt-3 text-green-600 font-medium hidden flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span></span>
                </div>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">บันทึกประกาศ</button>
            <a href="annunciate.php" class="ml-4 text-gray-600 hover:underline">← กลับ</a>
        </form>
    </div>

    <script>
        function showFileName(input) {
            const fileName = input.files[0]?.name;
            const fileNameContainer = document.getElementById('fileName');
            const span = fileNameContainer.querySelector('span');
            if (fileName) {
                span.textContent = fileName;
                fileNameContainer.classList.remove('hidden');
            } else {
                fileNameContainer.classList.add('hidden');
                span.textContent = '';
            }
        }
    </script>
</body>
</html>
