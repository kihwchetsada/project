<?php
session_start();
include 'db_connect.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

if (!$conn instanceof PDO) {
    throw new Exception('Database connection is not a PDO instance');
}

$team_success = false;
$team_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_team'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $team_error = 'CSRF token ไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
    } else {
        $tournament_id = $_POST['tournament_id'] ?? '';
        $team_name = trim($_POST['team_name'] ?? '');
        $coach_name = trim($_POST['coach_name'] ?? '');
        $coach_phone = trim($_POST['coach_phone'] ?? '');
        $leader_school = trim($_POST['leader_school'] ?? '');

        if (empty($tournament_id) || empty($team_name) || empty($coach_name) || empty($coach_phone) || empty($leader_school)) {
            $team_error = 'กรุณากรอกข้อมูลทีมให้ครบถ้วน';
        } else {
            $member_count = 0;
            for ($i = 1; $i <= 8; $i++) {
                if (!empty($_POST["member_name_$i"])) {
                    $member_count++;
                }
            }

            if ($member_count < 5) {
                $team_error = 'กรุณากรอกข้อมูลสมาชิกอย่างน้อย 5 คน';
            } else {
                try {
                    $conn->beginTransaction();

                    $stmt = $conn->prepare("INSERT INTO teams (tournament_id, team_name, coach_name, coach_phone, leader_school, created_at) 
                    VALUES (:tournament_id, :team_name, :coach_name, :coach_phone, :leader_school, NOW())");

                    $stmt->execute([
                        ':tournament_id' => $_POST['tournament_id'],
                        ':team_name' => $team_name,
                        ':coach_name' => $coach_name,
                        ':coach_phone' => $coach_phone,
                        ':leader_school' => $leader_school
                    ]);

                    $team_id = $conn->lastInsertId();

                    for ($i = 1; $i <= 8; $i++) {
                        if (!empty($_POST["member_name_$i"])) {
                            $member_name = $_POST["member_name_$i"];
                            $member_game_name = $_POST["member_game_name_$i"] ?? '';
                            $member_age = $_POST["member_age_$i"] ?? null;
                            $member_phone = $_POST["member_phone_$i"] ?? '';
                            $member_birthdate = $_POST["member_birthdate_$i"] ?? null;
                            $member_position = $_POST["member_position_$i"] ?? '';

                            $stmt = $conn->prepare("INSERT INTO team_members 
                            (team_id, member_name, game_name, age, phone, position, birthdate) 
                            VALUES (:team_id, :member_name, :game_name, :age, :phone, :position, :birthdate)");

                        $stmt->execute([
                            ':team_id' => $team_id,
                            ':member_name' => $member_name,
                            ':game_name' => $member_game_name,
                            ':age' => $member_age,
                            ':phone' => $member_phone,
                            ':position' => $member_position,
                            ':birthdate' => !empty($member_birthdate) ? $member_birthdate : null
                        ]);

                        }
                    }
                    $conn->commit();
                    $team_success = true;
                } catch (PDOException $e) {
                    $conn->rollBack();
                    $team_error = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
                }
            }
        }
    }
}
$sql = "SELECT id, tournament_name FROM tournaments";
$result = $conn->query($sql);
// ดึงรายการ tournaments จากฐานข้อมูล
$tournaments = [];
try {
    $stmt = $conn->query("SELECT id, tournament_name FROM tournaments");
    $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tournaments = [];
}


if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// เพิ่มตัวแปรเพื่อตรวจสอบการยอมรับ PDPA
$pdpa_accepted = isset($_SESSION['pdpa_accepted']) && $_SESSION['pdpa_accepted'] === true;

// ถ้ามีการส่งค่า pdpa_accept จากฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pdpa_accept'])) {
    $_SESSION['pdpa_accepted'] = true;
    $pdpa_accepted = true;
    
    // ถ้ามี redirect url ให้ redirect กลับไปที่หน้านั้น
    if (isset($_POST['redirect_url'])) {
        header('Location: ' . $_POST['redirect_url']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนทีม</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/s.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#e0f2fe',
                            100: '#bae6fd',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1'
                        },
                        success: {
                            50: '#ecfdf5',
                            500: '#10b981',
                            600: '#059669'
                        },
                        danger: {
                            50: '#fef2f2',
                            500: '#ef4444',
                            600: '#dc2626'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans text-gray-800">
    <header class="bg-blue-600/40 backdrop-blur-sm from-primary-600 to-primary-700 text-white py-8 shadow-md text-center">
        <div class="container mx-auto px-4 max-w-5xl">
            <h1 class="text-3xl font-bold mb-2">ลงทะเบียนการแข่งขัน</h1>
            <p class="text-primary-50">กรอกข้อมูลทีมของคุณเพื่อเข้าร่วมการแข่งขัน</p>
        </div>
    </header>
    
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <?php if ($team_success): ?>
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="text-success-500 text-6xl mb-4">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="text-2xl font-bold text-success-600 mb-">ลงทะเบียนสำเร็จ!</h2>
                <p class="mb-2 text-lg">ขอบคุณสำหรับการลงทะเบียน เราได้รับข้อมูลของทีม <?php echo htmlspecialchars($team_name); ?> เรียบร้อยแล้ว</p>
                <p class="mb-8 text-gray-600">ทางทีมงานจะติดต่อกลับไปที่หมายเลข <?php echo htmlspecialchars($coach_phone); ?> เพื่อยืนยันการลงทะเบียน</p>
                <div class="mt-8">
                    <a href="backend/participant_dashboard.php" class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-home mr-2"></i> กลับสู่หน้าหลัก
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php if (!empty($team_error)): ?>
                <div class="bg-danger-50 border-l-4 border-danger-500 p-4 mb-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-danger-500 mr-3 text-lg"></i>
                        <p class="text-danger-600"><?php echo htmlspecialchars($team_error); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <form action="" method="post" enctype="multipart/form-data" id="registrationForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="bg-white/60 backdrop-blur-sm rounded-lg shadow-lg p-6 mb-8">
                    <h3 class="text-xl font-bold text-black-800 pb-4 mb-6 border-b border-black-200">
                        <i class="fas fa-users-cog mr-2 text-black-600"></i> ข้อมูลทีม
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="tournament_id" class="block mb-2 font-medium text-gray-700">
                                ประเภทการแข่งขัน <span class="text-danger-500">*</span>
                            </label>
                            <select id="tournament_id" name="tournament_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200" required>
                                <option value=""> -- เลือกทัวร์นาเมนต์ -- </option>
                                <?php foreach ($tournaments as $t): ?>
                                    <option value="<?php echo $t['id']; ?>">
                                        <?php echo htmlspecialchars($t['tournament_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="team_name" class="block mb-2 font-medium text-gray-700">
                                ชื่อทีม <span class="text-danger-500">*</span>
                            </label>
                            <input type="text" id="team_name" name="team_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200" required>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="coach_name" class="block mb-2 font-medium text-gray-700">
                                ชื่อผู้ควบคุมทีม <span class="text-danger-500">*</span>
                            </label>
                            <input type="text" id="coach_name" name="coach_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200" required>
                        </div>
                        
                        <div>
                            <label for="coach_phone" class="block mb-2 font-medium text-gray-700">
                                เบอร์โทรผู้ควบคุมทีม <span class="text-danger-500">*</span>
                            </label>
                            <input type="tel" id="coach_phone" name="coach_phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200" pattern="[0-9]{9,10}" placeholder="0xxxxxxxxx" required>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="leader_school" class="block mb-2 font-medium text-gray-700">
                            สังกัด/โรงเรียน
                        </label>
                        <input type="text" id="leader_school" name="leader_school" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                    </div>
                </div>
                
                <div class="bg-white/60 backdrop-blur-sm rounded-lg shadow-lg p-6 mb-8">
                    <h3 class="text-xl font-bold text-black-800 pb-4 mb-6 border-b border-black-200">
                        <i class="fas fa-users mr-2 text-black-600"></i> ข้อมูลสมาชิก
                    </h3>
                    <p class="mb-6 text-danger-600">กรุณากรอกข้อมูลสมาชิกในทีมของคุณ (จำเป็นต้องมีอย่างน้อย 5 คน, สูงสุด 8 คน)</p>
                    
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-6 mb-6 relative <?php echo $i > 5 ? 'border-dashed' : ''; ?>">
                        <div class="absolute top-4 right-4 w-8 h-8 bg-<?php echo $i <= 5 ? 'primary' : 'gray'; ?>-600 text-white rounded-full flex items-center justify-center font-bold">
                            <?php echo $i; ?>
                        </div>
                        <h4 class="text-lg font-medium mb-4 text-gray-800">
                            สมาชิกคนที่ <?php echo $i; ?> 
                            <?php if ($i <= 5): ?>
                                <span class="inline-block bg-primary-100 text-primary-700 text-xs font-medium px-2 py-1 rounded ml-2">จำเป็น</span>
                            <?php else: ?>
                                <span class="inline-block bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded ml-2">ไม่จำเป็น</span>
                            <?php endif; ?>
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <div>
                                <label for="member_name_<?php echo $i; ?>" class="block mb-2 font-medium text-gray-700">
                                    ชื่อ-นามสกุล <span class="text-danger-500">*</span>
                                </label>
                                <input type="text" id="member_name_<?php echo $i; ?>" name="member_name_<?php echo $i; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200" <?php echo $i <= 5 ? 'required' : ''; ?>>
                            </div>

                            <div>
                                <label for="member_game_name_<?php echo $i; ?>" class="block mb-2 font-medium text-gray-700">
                                    ชื่อในเกม
                                </label>
                                <input type="text" id="member_game_name_<?php echo $i; ?>" name="member_game_name_<?php echo $i; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                            <div>
                                <label for="member_age_<?php echo $i; ?>" class="block mb-2 font-medium text-gray-700">
                                    อายุ
                                </label>
                                <input type="number" id="member_age_<?php echo $i; ?>" name="member_age_<?php echo $i; ?>" min="7" max="99" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>

                            <div>
                                <label for="member_phone_<?php echo $i; ?>" class="block mb-2 font-medium text-gray-700">
                                    เบอร์โทรศัพท์
                                </label>
                                <input type="tel" id="member_phone_<?php echo $i; ?>" name="member_phone_<?php echo $i; ?>" pattern="[0-9]{9,10}" placeholder="0xxxxxxxxx" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>

                            <div>
                                <select name="member_position_<?php echo $i; ?>" id="member_position_<?php echo $i; ?>" class="w-full px-5 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                                    <option value="">เลือกตำแหน่งที่เล่น</option>
                                    <option value="เลน Dark Slayer / ออฟเลน">เลน Dark Slayer / ออฟเลน</option>
                                    <option value="เลนกลาง">เลนกลาง / เมท</option>
                                    <option value="เลน Abyssal Dragon">เลน Abyssal Dragon / แครี่</option>
                                    <option value="ซัพพอร์ต">ซัพพอร์ต / แทงค์</option>
                                    <option value="ฟาร์มป่า">ฟาร์มป่า / แอสซาซิน</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="member_birthdate_<?php echo $i; ?>" class="block mb-2 font-medium text-gray-700">
                                วันเดือนปีเกิด <span class="text-danger-500">*</span>
                            </label>
                            <input type="date" id="member_birthdate_<?php echo $i; ?>" name="member_birthdate_<?php echo $i; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200" <?php echo $i <= 5 ? 'required' : ''; ?>>
                        </div>
                    </div>
                <?php endfor; ?>
                
                    <div class="mt-8 text-center">
                        <div class="mb-6">
                            <div class="flex items-start space-x-2">
                                <input type="checkbox" id="pdpa_consent" name="pdpa_consent" value="1" required class="mt-1">
                                <label for="pdpa_consent" class="text-lg text-gray-700">
                                    ข้าพเจ้ายินยอมให้จัดเก็บและใช้ข้อมูลส่วนบุคคลตาม
                                    <button type="button" onclick="openPdpaModal()" class="text-danger-600 underline hover:text-primary-800">
                                        นโยบายความเป็นส่วนตัว
                                    </button>
                                </label>
                            </div>
                        </div>

                        <button type="submit" name="submit_team" class="px-8 py-3 bg-success-500 hover:bg-success-600 text-white font-medium rounded-lg transition-colors duration-200 inline-flex items-center">
                            <i class="fas fa-check-circle mr-2"></i> ลงทะเบียนทีม
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <!-- PDPA Modal -->
    <div id="pdpaModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 <?php echo !$pdpa_accepted ? 'block' : 'hidden'; ?>">
        <div class="bg-white rounded-lg shadow-lg max-w-4xl w-full p-6 relative">
            <h2 class="text-4xl font-bold mb-4 text-primary-700">นโยบายความเป็นส่วนตัว (PDPA)</h2>
            <div class="max-h-[60vh] overflow-y-auto text-xl text-gray-700 space-y-4 pr-2">
                <p>เว็บไซต์นี้ให้ความสำคัญกับความเป็นส่วนตัวของผู้ใช้งาน และปฏิบัติตาม พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA) อย่างเคร่งครัด</p>
                <p><strong>1. ข้อมูลที่เราเก็บ:</strong> ชื่อ, เบอร์โทร, วันเกิด, ข้อมูลการแข่งขัน</p>
                <p><strong>2. วัตถุประสงค์:</strong> ใช้สำหรับลงทะเบียน ติดต่อ ยืนยัน ออกเกียรติบัตร และการบริหารจัดการแข่งขัน</p>
                <p><strong>3. การจัดเก็บข้อมูล:</strong> จะเก็บข้อมูลไว้ภายในระยะเวลาที่จำเป็นและมีมาตรการรักษาความปลอดภัย</p>
                <p><strong>4. สิทธิของท่าน:</strong> ท่านสามารถขอเข้าถึง ลบ แก้ไข หรือถอนความยินยอมได้ทุกเมื่อ</p>
                <p><strong>5. การเปิดเผยข้อมูล:</strong> จะไม่เปิดเผยแก่บุคคลภายนอก เว้นแต่มีข้อกำหนดทางกฎหมาย</p>
            </div>
            <div class="text-right mt-6">
                <form method="post" action="">
                    <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                    <button type="submit" name="pdpa_accept" value="1" class="px-6 py-3 bg-success-600 text-xl text-white rounded-lg hover:bg-success-700 transition">
                        ยอมรับและดำเนินการต่อ
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <footer class="bg-gray-800 text-white py-6 mt-12">
        <div class="container mx-auto px-4 text-center text-sm">
            <p>&copy; <?php echo date('Y'); ?> ระบบลงทะเบียนการแข่งขัน</p>
        </div>
    </footer>

    <script>
        // สำหรับแสดงตัวอย่างรูปภาพก่อนอัพโหลด
        document.addEventListener('DOMContentLoaded', function() {
            for (let i = 1; i <= 8; i++) {
                const fileInput = document.getElementById(`member_id_card_${i}`);
                const preview = document.getElementById(`preview_id_card_${i}`);
                
                if (fileInput && preview) {
                    fileInput.addEventListener('change', function() {
                        if (this.files && this.files[0]) {
                            const reader = new FileReader();
                            
                            reader.onload = function(e) {
                                while (preview.firstChild) {
                                    preview.removeChild(preview.firstChild);
                                }
                                
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                preview.appendChild(img);
                            };
                            
                            reader.readAsDataURL(this.files[0]);
                        } else {
                            while (preview.firstChild) {
                                preview.removeChild(preview.firstChild);
                            }
                            
                            const icon = document.createElement('i');
                            icon.className = 'fas fa-id-card';
                            preview.appendChild(icon);
                        }
                    });
                }
            }
            
            // ถ้า PDPA modal แสดงอยู่ ไม่ให้เลื่อนหน้าเว็บ
            if (!document.getElementById('pdpaModal').classList.contains('hidden')) {
                document.body.style.overflow = 'hidden';
            }
            });

        function openPdpaModal() {
            document.getElementById('pdpaModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closePdpaModal() {
            document.getElementById('pdpaModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    </script>
</body>
</html>