<?php
session_start();
$conn = new mysqli("localhost", "root", "", "announcements_db");
$conn->set_charset("utf8");

if (!isset($_GET['id'])) {
    die("❌ ไม่พบ ID ที่ต้องการลบ");
}
$id = intval($_GET['id']);

// ดึงรูปเพื่อจะลบไฟล์ด้วย
$stmt = $conn->prepare("SELECT image_path FROM announcements WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$announcement = $result->fetch_assoc();

if ($announcement) {
    // ลบรูปเก่า
    if (!empty($announcement['image_path']) && file_exists("../uploads/" . $announcement['image_path'])) {
        unlink("../uploads/" . $announcement['image_path']);
    }

    // ลบข้อมูล DB
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: og_view_announcement.php?deleted=1");
        exit;
    } else {
        echo "❌ ลบไม่สำเร็จ: " . $stmt->error;
    }
} else {
    echo "❌ ไม่พบข้อมูล";
}
?>
