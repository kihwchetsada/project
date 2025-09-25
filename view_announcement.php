<?php
// Database connection
require 'db_connect.php';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// รับ id จาก URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("ไม่พบประกาศที่ต้องการ");
}

// ดึงข้อมูลประกาศ
$sql = "SELECT a.id, a.title, a.description, a.created_at, a.category, a.priority, a.image_path,
               u.username AS creator_name
        FROM announcements a
        INNER JOIN users u ON a.user_id = u.id
        WHERE a.id = ? AND a.status = 'active'
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$announcement = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$announcement) {
    die("ไม่พบประกาศนี้");
}

// แปลงวันเวลา
$date_formatted = date('d/m/Y H:i', strtotime($announcement['created_at']));

// จัดการ priority ให้มีสีและ icon
$priorityDetails = match($announcement['priority']) {
    'สูง' => ['color' => 'bg-red-100 text-red-800', 'icon' => '🔴'],
    'ปานกลาง' => ['color' => 'bg-yellow-100 text-yellow-800', 'icon' => '🟡'],
    'ต่ำ' => ['color' => 'bg-green-100 text-green-800', 'icon' => '🟢'],
    default => ['color' => 'bg-gray-100 text-gray-800', 'icon' => '⚪']
};
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($announcement['title']); ?> | ROV Tournament Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/s.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #f5f7fa; }
    </style>
</head>
<body class="flex flex-col min-h-screen">

    <!-- Navbar -->
    <div class="navbar">
        <div class="logo">
            <img src="img/logo.jpg" alt="ROV Tournament Hub Logo">
            <h2>ROV Tournament Hub</h2>
        </div>
        <nav>
            <a href="index.php">หน้าหลัก</a>
            <a href="schedule.php">ตารางการแข่งขัน</a>
            <a href="register_user.php">สมัครทีม</a>
            <a href="annunciate.php">ประกาศ</a>
            <a href="contact.php">ติดต่อเรา</a>
            <a href="login.php">เข้าสู่ระบบ</a>
        </nav>
    </div>

    <!-- Main content -->
    <div class="container mx-auto px-4 py-10 flex-grow">
        <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-4 flex items-center gap-2">
                <span><?php echo $priorityDetails['icon']; ?></span>
                <?php echo htmlspecialchars($announcement['title']); ?>
            </h1>
            <div class="flex flex-wrap gap-4 text-gray-600 text-sm mb-6 items-center"> <span class="flex items-center gap-1">
                    📅 <?php echo $date_formatted; ?>
                </span>
                <span class="flex items-center gap-1">
                    📂 <?php echo htmlspecialchars($announcement['category']); ?>
                </span>

                <span class="flex items-center gap-1">
                    👤 <?php echo htmlspecialchars($announcement['creator_name']); ?>
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $priorityDetails['color']; ?>">
                    <?php echo htmlspecialchars($announcement['priority']); ?>
                </span>
            </div>

            <?php if (!empty($announcement['image_path'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($announcement['image_path']); ?>" alt="ภาพประกาศ" class="w-full rounded-lg mb-6 shadow">
            <?php endif; ?>
            <div class="prose max-w-none text-gray-800 leading-relaxed text-lg mb-6">
                <?php echo nl2br(htmlspecialchars($announcement['description'])); ?>
            </div>

            <div class="mt-6">
                <a href="annunciate.php" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    ⬅ กลับไปหน้าประกาศ
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4">
            <div class="flex justify-center mb-8">
                <button onclick="window.location.href='backend/Certificate/index.php'" 
                        class="bg-gradient-to-r from-purple-600 via-blue-600 to-indigo-700 hover:from-purple-700 hover:via-blue-700 hover:to-indigo-800 text-white px-12 py-4 rounded-xl font-bold text-lg flex items-center space-x-3 transition-all duration-300 transform hover:scale-110 shadow-2xl hover:shadow-purple-500/25 border-2 border-transparent hover:border-purple-300 relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-400 to-pink-400 opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                    <span class="relative z-10 text-xl">🏆 เกียรติบัตร</span>
                </button>
            </div>
            <div class="border-t border-gray-700 pt-6 text-center">
                <p class="text-gray-400">&copy; <?php echo date('Y'); ?> ระบบประกาศ. สงวนลิขสิทธิ์</p>
            </div>
        </div>
    </footer>

</body>
</html>
