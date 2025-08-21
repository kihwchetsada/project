<?php
session_start();

// ✅ ตรวจสอบสิทธิ์ว่าเป็น organizer
if (!isset($_SESSION['userData']) || $_SESSION['userData']['role'] !== 'organizer') {
    die('❌ คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// ✅ เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ ถ้ามีการกด submit ฟอร์ม
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title       = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category    = $_POST['category'] ?? '';
    $priority    = $_POST['priority'] ?? '';
    $imagePath   = null;

    // ✅ ดึง organizer_id จาก session
    $organizer_id = $_SESSION['userData']['id'];

    // ✅ อัปโหลดรูป (ถ้ามี)
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName   = time() . "_" . basename($_FILES["image"]["name"]); // กันชื่อซ้ำ
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    // ✅ ใช้คอลัมน์ที่ตรงกับ DB จริง
    $sql = "INSERT INTO announcements (title, description, category, priority, image_path, organizer_id, status, views, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'active', 0, NOW(), NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("❌ SQL Error: " . $conn->error); // debug
    }

    $stmt->bind_param("sssssi", $title, $description, $category, $priority, $imagePath, $organizer_id);

    if ($stmt->execute()) {
        echo "<script>
                alert('✅ บันทึกประกาศสำเร็จ!');
                window.location.href = 'organizer_dashboard.php';
              </script>";
        exit();
    } else {
        echo "<script>
                alert('❌ เกิดข้อผิดพลาด: " . addslashes($stmt->error) . "');
                window.history.back();
              </script>";
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>เพิ่มประกาศใหม่</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="font-sarabun min-h-screen bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-800 py-8 px-4">
  
  <!-- Main Container -->
  <div class="max-w-2xl mx-auto">
    
    <!-- Header Section -->
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 backdrop-blur-sm rounded-full shadow-2xl mb-6 border border-white/30">
        <i class="fas fa-bullhorn text-3xl text-white animate-bounce"></i>
      </div>
      <h1 class="text-4xl font-bold text-white mb-3 drop-shadow-lg">เพิ่มประกาศใหม่</h1>
      <p class="text-blue-100 text-lg">สร้างประกาศสำคัญสำหรับสมาชิกของคุณ</p>
    </div>

    <!-- Form -->
    <div class="bg-white/95 rounded-3xl shadow-2xl p-8 border border-white/20">
      <form action="" method="POST" enctype="multipart/form-data" class="space-y-8">
        
        <!-- Title -->
        <div>
          <label class="text-gray-700 font-semibold mb-2 block">หัวข้อประกาศ</label>
          <input type="text" name="title" required class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-lg">
        </div>

        <!-- Description -->
        <div>
          <label class="text-gray-700 font-semibold mb-2 block">รายละเอียด</label>
          <textarea name="description" rows="5" required class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-lg"></textarea>
        </div>

        <!-- Category -->
        <div>
          <label class="text-gray-700 font-semibold mb-2 block">หมวดหมู่</label>
          <input type="text" name="category" required class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-lg">
        </div>

        <!-- Priority -->
        <div>
          <label class="text-gray-700 font-semibold mb-2 block">ระดับความสำคัญ</label>
          <div class="flex gap-4">
            <label><input type="radio" name="priority" value="สูง" required> สูง</label>
            <label><input type="radio" name="priority" value="ปานกลาง"> ปานกลาง</label>
            <label><input type="radio" name="priority" value="ต่ำ"> ต่ำ</label>
          </div>
        </div>

        <!-- Image Upload -->
        <div>
          <label class="text-gray-700 font-semibold mb-2 block">เลือกรูปภาพ</label>
          <input type="file" name="image" accept="image/*">
        </div>

        <!-- Buttons -->
        <div class="flex gap-4">
          <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold">
            <i class="fas fa-save mr-2"></i> บันทึกประกาศ
          </button>
          <a href="organizer_dashboard.php" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-xl font-semibold text-center">
            <i class="fas fa-arrow-left mr-2"></i> ย้อนกลับ
          </a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
