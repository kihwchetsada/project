<?php
// Database connection configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "announcements_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to support Thai language
$conn->set_charset("utf8");

// Initialize filter variables
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 9;
$offset = ($page - 1) * $items_per_page;

// Prepare SQL query with filters
$sql = "SELECT id, title, description, date, category, priority FROM announcements WHERE 1=1";

// Add search filter if provided
if (!empty($search_query)) {
    $search_query = $conn->real_escape_string($search_query);
    $sql .= " AND (title LIKE '%$search_query%' OR description LIKE '%$search_query%')";
}

// Add category filter if provided
if (!empty($category_filter)) {
    $category_filter = $conn->real_escape_string($category_filter);
    $sql .= " AND category = '$category_filter'";
}

// Add priority filter if provided
if (!empty($priority_filter)) {
    $priority_filter = $conn->real_escape_string($priority_filter);
    $sql .= " AND priority = '$priority_filter'";
}

// Count total records for pagination
$count_sql = str_replace("SELECT id, title, description, date, category, priority", "SELECT COUNT(*)", $sql);
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_row()[0];
$total_pages = ceil($total_records / $items_per_page);

// Complete the SQL query with ordering and pagination
$sql .= " ORDER BY date DESC LIMIT $offset, $items_per_page";
$result = $conn->query($sql);

// Get all available categories for filter dropdown
$categories_sql = "SELECT DISTINCT category FROM announcements ORDER BY category";
$categories_result = $conn->query($categories_sql);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}

// Define priority options
$priorities = ['สูง', 'ปานกลาง', 'ต่ำ'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประกาศข่าวสาร | เกียรติบัตร</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/s.css">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .announcement-card {
            transition: all 0.3s ease;
            transform-origin: center;
        }
        .announcement-card:hover {
            transform: scale(1.03);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        /* Skeleton loading animation */
        @keyframes pulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 0.3; }
        }
        .skeleton {
            animation: pulse 1.5s infinite;
        }
    </style>
     <div class="navbar">
        <div class="logo">
            <img src="img/logo.jpg" alt="ROV Tournament Hub Logo">
            <h2>ROV Tournament Hub</h2>
        </div>
        <nav>
            <a href="index.php">หน้าหลัก</a>
            <a href="schedule.php">ตารางการแข่งขัน</a>
            <a href="register.php">สมัครทีม</a>
            <a href="annunciate.php">ประกาศ</a>
            <a href="contact.php">ติดต่อเรา</a>
            <a href="login.php">เข้าสู่ระบบ</a>
        </nav>
    </div> 
</head>
<body class="flex flex-col">
    
    <div class="container mx-auto px-4 py-8 flex-grow" x-data="{ showFilters: false }">
        <div class="max-w-6xl mx-auto">
            
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    ประกาศทั้งหมด
                    <span class="ml-3 text-sm font-normal text-gray-500 mt-1">(<?php echo $total_records; ?> รายการ)</span>
                </h1>
                <div class="flex items-center">
                    <form action="" method="GET" class="flex flex-wrap md:flex-nowrap gap-2">
                        <input type="text" name="search" placeholder="ค้นหาประกาศ" value="<?php echo htmlspecialchars($search_query); ?>" 
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 flex-grow">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition whitespace-nowrap">
                            ค้นหา
                        </button>
                        <button type="button" @click="showFilters = !showFilters" 
                                class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            ตัวกรอง
                        </button>
                    </form>
                </div>
            </div>
            <!-- Filters -->
            <div x-show="showFilters" x-transition class="bg-white p-4 rounded-lg shadow-md mb-6">
                <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่</label>
                        <select name="category" id="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category_filter == $cat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">ความสำคัญ</label>
                        <select name="priority" id="priority" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($priorities as $pri): ?>
                                <option value="<?php echo htmlspecialchars($pri); ?>" <?php echo ($priority_filter == $pri) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pri); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition w-full">ใช้ตัวกรอง</button>
                    </div>
                </form>
            </div>

            <?php if (!empty($search_query) || !empty($category_filter) || !empty($priority_filter)): ?>
                <div class="bg-blue-50 p-3 rounded-lg mb-6 flex justify-between items-center">
                    <div class="text-blue-800">
                        <span class="font-medium">กำลังกรอง:</span>
                        <?php if (!empty($search_query)): ?>
                            <span class="ml-2">คำค้นหา "<?php echo htmlspecialchars($search_query); ?>"</span>
                        <?php endif; ?>
                        <?php if (!empty($category_filter)): ?>
                            <span class="ml-2">หมวดหมู่: <?php echo htmlspecialchars($category_filter); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($priority_filter)): ?>
                            <span class="ml-2">ความสำคัญ: <?php echo htmlspecialchars($priority_filter); ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="?" class="text-blue-600 hover:text-blue-800 font-medium">ล้างตัวกรอง</a>
                </div>
            <?php endif; ?>

            <!-- Announcements Grid -->
            <?php if ($result->num_rows > 0): ?>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php 
                        // Determine priority color and icon
                        $priorityDetails = match($row['priority']) {
                            'สูง' => ['color' => 'bg-red-100 text-red-800', 'icon' => '🔴', 'badge' => 'border-red-300 bg-red-50'],
                            'ปานกลาง' => ['color' => 'bg-yellow-100 text-yellow-800', 'icon' => '🟡', 'badge' => 'border-yellow-300 bg-yellow-50'],
                            'ต่ำ' => ['color' => 'bg-green-100 text-green-800', 'icon' => '🟢', 'badge' => 'border-green-300 bg-green-50'],
                            default => ['color' => 'bg-gray-100 text-gray-800', 'icon' => '⚪', 'badge' => 'border-gray-300 bg-gray-50']
                        };
                        ?>
                        <div class="announcement-card bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-2xl"><?php echo $priorityDetails['icon']; ?></span>
                                        <h2 class="text-xl font-semibold text-gray-800 line-clamp-2">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </h2>
                                    </div>
                                    <span class="<?php echo $priorityDetails['color']; ?> px-3 py-1 rounded-full text-xs font-medium">
                                        <?php echo htmlspecialchars($row['priority']); ?>
                                    </span>
                                </div>
                                <p class="text-gray-600 mb-5 line-clamp-3">
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </p>
                                <div class="flex justify-between items-center text-sm text-gray-500 mb-4">
                                    <div class="flex items-center space-x-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span><?php echo htmlspecialchars($row['date']); ?></span>
                                    </div>
                                    <span class="border rounded-md px-2 py-1 text-xs <?php echo $priorityDetails['badge']; ?>">
                                        <?php echo htmlspecialchars($row['category']); ?>
                                    </span>
                                </div>
                                <a href="view_announcement.php?id=<?php echo $row['id']; ?>" class="block w-full bg-blue-50 hover:bg-blue-100 text-blue-700 text-center py-2 rounded-lg transition font-medium">
                                    อ่านเพิ่มเติม
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-10 flex justify-center">
                        <div class="inline-flex rounded-md shadow-sm">
                            <?php
                            $search_params = '';
                            if (!empty($search_query)) $search_params .= '&search=' . urlencode($search_query);
                            if (!empty($category_filter)) $search_params .= '&category=' . urlencode($category_filter);
                            if (!empty($priority_filter)) $search_params .= '&priority=' . urlencode($priority_filter);
                            ?>
                            
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $search_params; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">
                                    ก่อนหน้า
                                </a>
                            <?php else: ?>
                                <span class="px-4 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 rounded-l-md cursor-not-allowed">
                                    ก่อนหน้า
                                </span>
                            <?php endif; ?>

                            <?php
                            // Always show first page
                            if ($page > 3) {
                                echo '<a href="?page=1' . $search_params . '" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">1</a>';
                                
                                if ($page > 4) {
                                    echo '<span class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300">...</span>';
                                }
                            }

                            // Show pages around current page
                            for ($i = max(1, $page - 1); $i <= min($total_pages, $page + 1); $i++) {
                                if ($i == $page) {
                                    echo '<span class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600">' . $i . '</span>';
                                } else {
                                    echo '<a href="?page=' . $i . $search_params . '" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">' . $i . '</a>';
                                }
                            }

                            // Always show last page
                            if ($page < $total_pages - 2) {
                                if ($page < $total_pages - 3) {
                                    echo '<span class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300">...</span>';
                                }
                                echo '<a href="?page=' . $total_pages . $search_params . '" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">' . $total_pages . '</a>';
                            }
                            ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $search_params; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">
                                    ถัดไป
                                </a>
                            <?php else: ?>
                                <span class="px-4 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 rounded-r-md cursor-not-allowed">
                                    ถัดไป
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-16 bg-white rounded-2xl shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 mx-auto text-gray-300 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-5a2 2 0 00-2 2v5a2 2 0 002 2h5M6 9h12" />
                    </svg>
                    <p class="text-2xl text-gray-600 mb-2">ไม่พบประกาศ</p>
                    <p class="text-gray-500 mb-6">ไม่มีประกาศที่ตรงกับเงื่อนไขการค้นหาของคุณ</p>
                    <a href="?" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                        ดูประกาศทั้งหมด
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
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
                    <div class="absolute top-1 right-1 w-2 h-2 bg-yellow-300 rounded-full animate-ping"></div>
                    <div class="absolute bottom-1 left-1 w-1 h-1 bg-white rounded-full animate-pulse"></div>
                </button>
            </div>
            <div class="border-t border-gray-700 pt-6 text-center">
                <p class="text-gray-400">&copy; <?php echo date('Y'); ?> ระบบประกาศ. สงวนลิขสิทธิ์</p>
            </div>
        </div>
    </footer>
    <script>
        // Function to show loading skeleton while images and content load
        document.addEventListener('DOMContentLoaded', function() {
            // Fade in the content
            document.body.classList.add('opacity-100');
            document.body.classList.remove('opacity-0');
        });
    </script>
    <?php $conn->close(); ?>
</body>
</html>