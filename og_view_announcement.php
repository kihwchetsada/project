<?php
session_start();
$conn = new mysqli("localhost", "root", "", "announcements_db");
$conn->set_charset("utf8");

// ดึงข้อมูลทั้งหมด
$result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการประกาศ</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-8">

    <div class="max-w-6xl mx-auto bg-white shadow-lg rounded-2xl p-8">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">📢 จัดการประกาศ</h1>

        <!-- ปุ่มเพิ่มประกาศใหม่ -->
        <div class="mb-6">
            <a href="add_announcement.php" class="bg-green-500 hover:bg-green-600 text-white px-5 py-3 rounded-lg shadow-md">
                ➕ เพิ่มประกาศใหม่
            </a>
            <a href="backend/organizer_dashboard.php" class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-3 rounded-lg shadow-md">
                กลับไปหน้าหลัก
            </a>
        </div>

        <!-- ตารางรายการประกาศ -->
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
                <thead class="bg-gray-200 text-gray-700">
                    <tr>
                        <th class="py-3 px-4 text-left">#</th>
                        <th class="py-3 px-4 text-left">หัวข้อ</th>
                        <th class="py-3 px-4 text-left">หมวดหมู่</th>
                        <th class="py-3 px-4 text-left">ความสำคัญ</th>
                        <th class="py-3 px-4 text-left">สถานะ</th>
                        <th class="py-3 px-4 text-left">วันที่สร้าง</th>
                        <th class="py-3 px-4 text-center">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4"><?= $row['id'] ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($row['title']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($row['category']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($row['priority']) ?></td>
                                <td class="py-3 px-4">
                                    <?php if ($row['status'] === 'active'): ?>
                                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-lg">ใช้งาน</span>
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-700 px-3 py-1 rounded-lg">ปิด</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4"><?= $row['created_at'] ?></td>
                                <td class="py-3 px-4 text-center space-x-2">
                                    <a href="view_announcement.php?id=<?= $row['id'] ?>"  class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-3 py-1 rounded">👁 ดู</a>
                                    <a href="edit_announcement.php?id=<?= $row['id'] ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded">✏️ แก้ไข</a>
                                    <a href="delete_announcement.php?id=<?= $row['id'] ?>" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบประกาศนี้?');" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">🗑️ ลบ</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="py-6 text-center text-gray-500">ไม่มีประกาศในระบบ</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
