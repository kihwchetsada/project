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

// Fetch announcements from database
$sql = "SELECT id, title, description, date, category, priority FROM announcements ORDER BY date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประกาศทั้งหมด</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .announcement-card {
            transition: all 0.3s ease;
            transform-origin: center;
        }
        .announcement-card:hover {
            transform: scale(1.03);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <div class="container mx-auto px-4 py-12 flex-grow">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center justify-between mb-10">
                <h1 class="text-4xl font-bold text-gray-800 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mr-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    ประกาศทั้งหมด
                </h1>
                <div class="flex items-center space-x-4">
                    <input type="text" placeholder="ค้นหาประกาศ" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        ค้นหา
                    </button>
                </div>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php 
                        // Determine priority color and icon
                        $priorityDetails = match($row['priority']) {
                            'สูง' => ['color' => 'bg-red-100 text-red-800', 'icon' => '🔴'],
                            'ปานกลาง' => ['color' => 'bg-yellow-100 text-yellow-800', 'icon' => '🟡'],
                            'ต่ำ' => ['color' => 'bg-green-100 text-green-800', 'icon' => '🟢'],
                            default => ['color' => 'bg-gray-100 text-gray-800', 'icon' => '⚪']
                        };
                        ?>
                        <div class="announcement-card bg-white rounded-2xl shadow-lg overflow-hidden">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-2xl"><?php echo $priorityDetails['icon']; ?></span>
                                        <h2 class="text-xl font-semibold text-gray-800">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </h2>
                                    </div>
                                    <span class="<?php echo $priorityDetails['color']; ?> px-3 py-1 rounded-full text-xs font-medium">
                                        <?php echo htmlspecialchars($row['priority']); ?>
                                    </span>
                                </div>
                                <p class="text-gray-600 mb-4 line-clamp-3">
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </p>
                                <div class="flex justify-between items-center text-sm text-gray-500">
                                    <div class="flex items-center space-x-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span><?php echo htmlspecialchars($row['date']); ?></span>
                                    </div>
                                    <span class="bg-blue-50 text-blue-800 px-2 py-1 rounded-md">
                                        <?php echo htmlspecialchars($row['category']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-16 bg-white rounded-2xl shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 mx-auto text-gray-300 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-5a2 2 0 00-2 2v5a2 2 0 002 2h5M6 9h12" />
                    </svg>
                    <p class="text-2xl text-gray-600">ไม่มีประกาศในขณะนี้</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-gray-800 text-white py-6">
        <div class="container mx-auto px-4 text-center">
            <p>&copy;<?php echo date('Y'); ?> ระบบประกาศ. สงวนลิขสิทธิ์</p>
        </div>
    </footer>

    <?php $conn->close(); ?>
    
</body>
</html>