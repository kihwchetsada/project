<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "announcements_db");
$conn->set_charset("utf8");

// รับค่า id
if (!isset($_GET['id'])) {
    die("❌ ไม่พบ ID ประกาศ");
}
$id = intval($_GET['id']);

// ดึงข้อมูลเก่ามาแสดง
$stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$announcement = $result->fetch_assoc();

if (!$announcement) {
    die("❌ ไม่พบข้อมูลประกาศ");
}

// อัพเดทข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $priority = trim($_POST['priority']);

    $imagePath = $announcement['image_path']; // ค่ารูปเดิม

    if (!empty($_FILES['image']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024;

        $fileType = $_FILES['image']['type'];
        $fileSize = $_FILES['image']['size'];
        $tmpName = $_FILES['image']['tmp_name'];
        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetDir = '../uploads/';
        $targetPath = $targetDir . $fileName;

        if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            if (move_uploaded_file($tmpName, $targetPath)) {
                // ลบไฟล์เก่า (ถ้ามี)
                if (!empty($announcement['image_path']) && file_exists("../uploads/" . $announcement['image_path'])) {
                    unlink("../uploads/" . $announcement['image_path']);
                }
                $imagePath = $fileName;
            }
        }
    }

    $stmt = $conn->prepare("UPDATE announcements SET title=?, description=?, category=?, priority=?, image_path=? WHERE id=?");
    $stmt->bind_param("sssssi", $title, $description, $category, $priority, $imagePath, $id);

    if ($stmt->execute()) {
        $success = "✅ บันทึกประกาศเรียบร้อยแล้ว";
    } else {
        $error = "❌ เกิดข้อผิดพลาด: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขประกาศ</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8">
    <h1 class="text-2xl font-bold mb-4">แก้ไขประกาศ</h1>

        <?php if (!empty($success)): ?>
            <p class="mb-4 bg-green-100 text-green-800 px-4 py-2 rounded">
                <?= $success ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p class="mb-4 bg-red-100 text-red-800 px-4 py-2 rounded">
                <?= $error ?>
            </p>
        <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="text" name="title" value="<?= htmlspecialchars($announcement['title']) ?>" required class="border p-2 w-full">
        <textarea name="description" required class="border p-2 w-full"><?= htmlspecialchars($announcement['description']) ?></textarea>
        <input type="text" name="category" value="<?= htmlspecialchars($announcement['category']) ?>" required class="border p-2 w-full">
        <input type="text" name="priority" value="<?= htmlspecialchars($announcement['priority']) ?>" required class="border p-2 w-full">

        <p>รูปเดิม: 
            <?php if ($announcement['image_path']): ?>
                <img src="../uploads/<?= $announcement['image_path'] ?>" width="100">
            <?php else: ?>
                ไม่มีรูป
            <?php endif; ?>
        </p>
        <input type="file" name="image" accept="image/*">

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">บันทึก</button>
    </form>
    <div class="bg-white rounded-lg shadow-md p-6">
                <a href="og_view_announcement.php" 
                   class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors duration-200">
                    ← กลับไปหน้าจัดการประกาศ
                </a>
            </div>
</body>
</html>
