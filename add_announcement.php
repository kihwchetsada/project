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
        $success = "✅ บันทึกข้อมูลเรียบร้อยแล้ว";
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
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/add_announcement.css">
</head>
<body class="elegant-bg min-h-screen py-8 px-4">
    <div class="max-w-4xl mx-auto">
        <!-- Header Section -->
        <div class="text-center mb-12 fade-in">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl shadow-lg mb-6">
                <i class="fas fa-bullhorn text-2xl text-blue-600"></i>
            </div>
            <h1 class="text-4xl font-bold text-gray-800 mb-3">เพิ่มประกาศใหม่</h1>
            <p class="text-lg text-gray-600">สร้างประกาศที่มีประสิทธิภาพและน่าสนใจ</p>
        </div>

        <!-- Main Form Card -->
        <div class="bg-white rounded-3xl card-shadow p-8 md:p-12 slide-up">
            <?php if (!empty($error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl mb-8 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3 text-lg"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-8">
                <!-- Title Field -->
                <div class="space-y-2">
                    <label class="flex items-center text-gray-700 font-semibold text-lg">
                        <span class="icon-wrapper">
                            <i class="fas fa-heading text-white text-sm"></i>
                        </span>
                        หัวข้อประกาศ
                    </label>
                    <input type="text" name="title" required 
                        class="form-field w-full px-6 py-4 rounded-xl bg-gray-50 focus:bg-white focus:outline-none text-lg"
                        placeholder="กรอกหัวข้อประกาศที่ชัดเจนและน่าสนใจ">
                </div>

                <!-- Description Field -->
                <div class="space-y-2">
                    <label class="flex items-center text-gray-700 font-semibold text-lg">
                        <span class="icon-wrapper">
                            <i class="fas fa-align-left text-white text-sm"></i>
                        </span>
                        รายละเอียด
                    </label>
                    <textarea name="description" required rows="5" 
                            class="form-field w-full px-6 py-4 rounded-xl bg-gray-50 focus:bg-white focus:outline-none resize-none text-lg"
                            placeholder="อธิบายรายละเอียดประกาศอย่างชัดเจน"></textarea>
                </div>

                <!-- Category Field -->
                <div class="space-y-2">
                    <label class="flex items-center text-gray-700 font-semibold text-lg">
                        <span class="icon-wrapper">
                            <i class="fas fa-tag text-white text-sm"></i>
                        </span>
                        หมวดหมู่
                    </label>
                    <select name="category" required
                        class="form-field w-full px-6 py-4 rounded-xl bg-gray-50 focus:bg-white focus:outline-none text-lg">
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        <option value="ข่าวสาร">ข่าวสาร</option>
                        <option value="กิจกรรม">กิจกรรม</option>
                        <option value="ประชาสัมพันธ์">ประชาสัมพันธ์</option>
                    </select>
                </div>

                <!-- Priority Field -->
                <div class="space-y-4">
                    <label class="flex items-center text-gray-700 font-semibold text-lg">
                        <span class="icon-wrapper">
                            <i class="fas fa-exclamation text-white text-sm"></i>
                        </span>
                        ระดับความสำคัญ
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="priority-card bg-white border border-gray-200 rounded-xl p-6 text-center hover:border-blue-300">
                            <input type="radio" name="priority" value="สูง" class="sr-only" required>
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-fire text-red-600 text-xl"></i>
                            </div>
                            <h3 class="font-semibold text-gray-800 text-lg">ความสำคัญสูง</h3>
                            <p class="text-gray-600 text-sm mt-2">ต้องแจ้งเร่งด่วน</p>
                        </label>
                        
                        <label class="priority-card bg-white border border-gray-200 rounded-xl p-6 text-center hover:border-blue-300">
                            <input type="radio" name="priority" value="ปานกลาง" class="sr-only">
                            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-balance-scale text-yellow-600 text-xl"></i>
                            </div>
                            <h3 class="font-semibold text-gray-800 text-lg">ความสำคัญปานกลาง</h3>
                            <p class="text-gray-600 text-sm mt-2">ประกาศทั่วไป</p>
                        </label>
                        
                        <label class="priority-card bg-white border border-gray-200 rounded-xl p-6 text-center hover:border-blue-300">
                            <input type="radio" name="priority" value="ต่ำ" class="sr-only">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-leaf text-green-600 text-xl"></i>
                            </div>
                            <h3 class="font-semibold text-gray-800 text-lg">ความสำคัญต่ำ</h3>
                            <p class="text-gray-600 text-sm mt-2">ข้อมูลเพิ่มเติม</p>
                        </label>
                    </div>
                </div>

                <!-- Image Upload Field -->
                <div class="space-y-4">
                    <label class="flex items-center text-gray-700 font-semibold text-lg">
                        <span class="icon-wrapper">
                            <i class="fas fa-image text-white text-sm"></i>
                        </span>
                        รูปภาพประกอบ (ไม่บังคับ)
                    </label>
                    <div class="relative">
                        <input type="file" name="image" accept="image/*" id="imageInput" class="hidden" onchange="showFileName(this)">
                        <label for="imageInput" class="upload-area block w-full rounded-xl p-12 text-center cursor-pointer">
                            <div class="space-y-4">
                                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto">
                                    <i class="fas fa-cloud-upload-alt text-blue-600 text-2xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-700 mb-2">คลิกเพื่อเลือกรูปภาพ</h3>
                                    <p class="text-gray-500">หรือลากไฟล์มาวางที่นี่</p>
                                </div>
                                <div class="flex items-center justify-center space-x-6 text-sm text-gray-500">
                                    <span class="flex items-center">
                                        <i class="far fa-file-image mr-2"></i>
                                        JPG, PNG, GIF
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-weight-hanging mr-2"></i>
                                        สูงสุด 5MB
                                    </span>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div id="fileName" class="hidden bg-green-50 border border-green-200 rounded-xl p-4">
                        <div class="flex items-center text-green-700">
                            <i class="fas fa-check-circle mr-3 text-lg"></i>
                            <span class="font-semibold">ไฟล์ที่เลือก: </span>
                            <span class="ml-2 text-green-600"></span>
                        </div>
                    </div>
                </div>

                <!-- Submit Section -->
                <div class="pt-8 border-t border-gray-100">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button type="submit" class="btn-primary flex-1 text-white px-8 py-4 rounded-xl font-semibold text-lg flex items-center justify-center">
                            <i class="fas fa-save mr-3"></i>
                            บันทึกประกาศ
                        </button>
                        <a href="backend/organizer_dashboard.php" 
                        class="flex items-center justify-center px-8 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-all text-lg">
                            <i class="fas fa-arrow-left mr-3"></i>
                            ย้อนกลับ
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showFileName(input) {
            const fileName = input.files[0]?.name;
            const fileNameContainer = document.getElementById('fileName');
            const span = fileNameContainer.querySelector('span:last-child');
            
            if (fileName) {
                span.textContent = fileName;
                fileNameContainer.classList.remove('hidden');
            } else {
                fileNameContainer.classList.add('hidden');
                span.textContent = '';
            }
        }

        // Priority selection handling
        document.addEventListener('DOMContentLoaded', function() {
            const priorityInputs = document.querySelectorAll('input[name="priority"]');
            const priorityCards = document.querySelectorAll('.priority-card');
            
            priorityInputs.forEach((input, index) => {
                input.addEventListener('change', function() {
                    priorityCards.forEach(card => card.classList.remove('selected'));
                    if (this.checked) {
                        priorityCards[index].classList.add('selected');
                    }
                });
            });

            // Drag and drop functionality
            const uploadArea = document.querySelector('.upload-area');
            const fileInput = document.getElementById('imageInput');

            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    showFileName(fileInput);
                }
            });

            // Form validation feedback
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i>กำลังบันทึก...';
                submitBtn.disabled = true;
            });
        });
    </script>
</body>
</html>