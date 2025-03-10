<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "competition_system";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $e->getMessage());
}

require_once 'backend/upload_script.php';

function handleFileUpload($tmp_name, $name) {
    $target_dir = "uploads/";
    $encrypted_filename = md5(time() . $name) . '.' . pathinfo($name, PATHINFO_EXTENSION);
    $target_file = $target_dir . $encrypted_filename;

    if (move_uploaded_file($tmp_name, $target_file)) {
        return $encrypted_filename;
    } else {
        throw new Exception("Sorry, there was an error uploading your file.");
    }
}

function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

$success = false;


function getCompetitions($conn) {
    try {
        $sql = "SELECT id, name FROM competitions_1 WHERE is_open = 1 
                AND CURDATE() BETWEEN start_date AND end_date";
        $result = $conn->query($sql);
        
        if ($result === false) {
            throw new Exception($conn->error);
        }
        
        $competitions = [];
        while($row = $result->fetch_assoc()) {
            $competitions[] = $row;
        }
        return $competitions;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

$competitions = getCompetitions($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $team_name = validateInput($_POST['team_name'] ?? '');
        $competition_id = isset($_POST['competition_id']) ? (int)$_POST['competition_id'] : 0;
        $advisor = validateInput($_POST['advisor'] ?? '');
        $contact = validateInput($_POST['contact'] ?? '');
        $email = validateInput($_POST['email'] ?? '');
        
        $member_names = $_POST['member_name'] ?? [];
        $id_card_images = $_FILES['id_card_image'] ?? [];
        
        $conn->begin_transaction();
        
        $sql_team = "INSERT INTO teams (team_name, competition_id, advisor, contact, email, registration_date) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt_team = $conn->prepare($sql_team);
        $stmt_team->bind_param("sisss", $team_name, $competition_id, $advisor, $contact, $email);
        $stmt_team->execute();
        $team_id = $stmt_team->insert_id;
        
        $sql_member = "INSERT INTO team_members (team_id, player_name, id_card_image) VALUES (?, ?, ?)";
        $stmt_member = $conn->prepare($sql_member);
        
        
        for ($i = 0; $i < count($member_names); $i++) {
            if (!empty($member_names[$i]) && !empty($id_card_images['tmp_name'][$i])) {
                $encrypted_filename = handleFileUpload($id_card_images['tmp_name'][$i], $id_card_images['name'][$i]);
                
                $stmt_member->bind_param("iss", $team_id, $member_names[$i], $encrypted_filename);
                $stmt_member->execute();
            }
        }
        
        $conn->commit();
        $success = true;
        echo "ลงทะเบียนสำเร็จ";

    } catch (Exception $e) {
        $conn->rollback();
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROV Tournament Hub - ลงทะเบียนทีม</title>
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="css/r.css">
</head>
<body>
    <div class="container">
        <img src="img/logo.jpg" alt="ROV Tournament Logo" class="tournament-logo" width="100" height="100">
        <h2>สมัครทีมแข่งขัน ROV</h2>
        
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                บันทึกข้อมูลสำเร็จ! ขอบคุณที่สมัครเข้าร่วมการแข่งขัน
            </div>
        <?php endif; ?>

        <?php if (empty($competitions)): ?>
            <div class="error">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                ขณะนี้ยังไม่มีรายการแข่งขันที่เปิดรับสมัคร กรุณาติดตามประกาศเร็วๆ นี้
            </div>
        <?php else: ?>
            <form method="POST" onsubmit="return validateForm()" enctype="multipart/form-data">
                <div class="form-section team-info">
                    <h3>ข้อมูลทีม</h3>
                    
                    <label for="team_name" class="required">ชื่อทีม</label>
                    <input type="text" id="team_name" name="team_name" required 
                        placeholder="กรอกชื่อทีมของคุณ"
                        value="<?php echo htmlspecialchars($_POST['team_name'] ?? ''); ?>">

                    <label for="competition_id" class="required">รายการแข่งขัน</label>
                    <select id="competition_id" name="competition_id" required>
                        <option value="">-- เลือกรายการแข่งขัน --</option>
                        <?php foreach ($competitions as $competition): ?>
                            <option value="<?php echo htmlspecialchars($competition['id']); ?>"
                                <?php echo (isset($_POST['competition_id']) && $_POST['competition_id'] == $competition['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($competition['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="advisor" class="required">ชื่อผู้ดูแลทีม / หัวหน้าทีม</label>
                    <input type="text" id="advisor" name="advisor" required
                        placeholder="ชื่อผู้ดูแลทีมหรือผู้จัดการทีม"
                        value="<?php echo htmlspecialchars($_POST['advisor'] ?? ''); ?>">

                    <label for="contact" class="required">เบอร์ติดต่อ</label>
                    <input type="tel" id="contact" name="contact" pattern="[0-9]{10}" required
                        placeholder="0812345678"
                        value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>">

                    <label for="email" class="required">อีเมล</label>
                    <input type="email" id="email" name="email" required
                        placeholder="example@email.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-section">
                    <h3>ข้อมูลสมาชิกทีม (ต้องมีอย่างน้อย 5 คน, สูงสุด 8 คน)</h3>
                    <div id="members">
                        <div class="member">
                            <div class="member-header">
                                <span class="member-number">สมาชิกคนที่ 1</span>
                            </div>
                            
                            <label for="member_name_1" class="required">ชื่อ-นามสกุล</label>
                            <input type="text" name="member_name[]" id="member_name_1" required
                                placeholder="ชื่อ-นามสกุลของผู้เล่น"
                                value="<?php echo htmlspecialchars($_POST['member_name'][0] ?? ''); ?>">
                            
                            <label for="game_name_1" class="required">ชื่อในเกม</label>
                            <input type="text" name="game_name[]" id="game_name_1" required
                                placeholder="ชื่อที่ใช้ในเกม ROV"
                                value="<?php echo htmlspecialchars($_POST['game_name'][0] ?? ''); ?>">
                            
                            <label for="role_1" class="required">ตำแหน่งที่เล่น</label>
                            <input type="text" name="role[]" id="role_1" required
                                placeholder="เช่น Midlane, Jungle, Roaming, ADC, Support"
                                value="<?php echo htmlspecialchars($_POST['role'][0] ?? ''); ?>">
                            
                            <label for="age_1" class="required">อายุ</label>
                            <input type="number" name="age[]" id="age_1" min="0" required
                                placeholder="อายุผู้เล่น"
                                value="<?php echo htmlspecialchars($_POST['age'][0] ?? ''); ?>">
                            
                            <label for="phone_1">เบอร์โทร</label>
                            <input type="tel" name="phone[]" id="phone_1" pattern="[0-9]{10}"
                                placeholder="0812345678 (ถ้ามี)"
                                value="<?php echo htmlspecialchars($_POST['phone'][0] ?? ''); ?>">
                            
                            <label for="id_card_image_1" class="required">ภาพบัตรประชาชน (JPG, JPEG, PNG เท่านั้น, ขนาดไม่เกิน 5MB)</label>
                            <input type="file" name="id_card_image[]" id="id_card_image_1" accept="image/jpeg,image/jpg,image/png" required 
                                onchange="previewImage(this, 'preview_1')">
                            <img id="preview_1" class="upload-preview" alt="ตัวอย่างภาพบัตรประชาชน">
                        </div>
                    </div>
                    <button type="button" onclick="addMember()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                        เพิ่มสมาชิก
                    </button>
                    <button type="submit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        ยืนยันการสมัคร
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>

        let memberCount = 1;

        function addMember() {
            if (memberCount < 8) {
                memberCount++;
                const membersDiv = document.getElementById('members');
                const memberDiv = document.createElement('div');
                memberDiv.className = 'member';
                memberDiv.innerHTML = `
                    <div class="member-header">
                        <span class="member-number">สมาชิกคนที่ ${memberCount}</span>
                        <span class="remove-member" onclick="removeMember(this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                            ลบ
                        </span>
                    </div>
                    
                    <label for="member_name_${memberCount}" class="required">ชื่อ-นามสกุล</label>
                    <input type="text" name="member_name[]" id="member_name_${memberCount}" required placeholder="ชื่อ-นามสกุลของผู้เล่น">
                    
                    <label for="game_name_${memberCount}" class="required">ชื่อในเกม</label>
                    <input type="text" name="game_name[]" id="game_name_${memberCount}" required placeholder="ชื่อที่ใช้ในเกม ROV">
                    
                    <label for="role_${memberCount}" class="required">ตำแหน่งที่เล่น</label>
                    <input type="text" name="role[]" id="role_${memberCount}" required placeholder="เช่น Midlane, Jungle, Roaming, ADC, Support">
                    
                    <label for="age_${memberCount}" class="required">อายุ</label>
                    <input type="number" name="age[]" id="age_${memberCount}" min="0" required placeholder="อายุผู้เล่น">
                    
                    <label for="phone_${memberCount}">เบอร์โทร</label>
                    <input type="tel" name="phone[]" id="phone_${memberCount}" pattern="[0-9]{10}" placeholder="0812345678 (ถ้ามี)">
                    
                    <label for="id_card_image_${memberCount}" class="required">ภาพบัตรประชาชน (JPG, JPEG, PNG เท่านั้น, ขนาดไม่เกิน 5MB)</label>
                    <input type="file" name="id_card_image[]" id="id_card_image_${memberCount}" accept="image/jpeg,image/jpg,image/png" required
                           onchange="previewImage(this, 'preview_${memberCount}')">
                    <img id="preview_${memberCount}" class="upload-preview" alt="ตัวอย่างภาพบัตรประชาชน">
                `;
                membersDiv.appendChild(memberDiv);
            } else {
                showNotification('เพิ่มสมาชิกได้สูงสุด 8 คนเท่านั้น', 'error');
            }
        }

        function removeMember(element) {
            if (memberCount > 5) {
                const memberDiv = element.closest('.member');
                memberDiv.remove();
                memberCount--;
                
                // Renumber the remaining members
                const members = document.querySelectorAll('.member');
                members.forEach((member, index) => {
                    member.querySelector('.member-number').textContent = `สมาชิกคนที่ ${index + 1}`;
                });
            } else {
                showNotification('ต้องมีสมาชิกอย่างน้อย 5 คน', 'error');
            }
        }

        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = type;
            notification.textContent = message;
            
            // Add icon based on type
            const icon = document.createElement('span');
            icon.innerHTML = type === 'error' 
                ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
            
            notification.prepend(icon);
            
            document.querySelector('.container').appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        function validateForm() {
            // ตรวจสอบจำนวนสมาชิก
            const memberInputs = document.querySelectorAll('input[name="member_name[]"]');
            let filledMembers = 0;
            memberInputs.forEach(input => {
                if (input.value.trim() !== '') filledMembers++;
            });

            if (filledMembers < 5) {
                showNotification('ต้องมีสมาชิกทีมอย่างน้อย 5 คน', 'error');
                return false;
            }
            
            // ตรวจสอบการอัปโหลดไฟล์
            let allFilesValid = true;
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach((input, index) => {
                const memberName = document.getElementsByName('member_name[]')[index].value;
                if (memberName.trim() !== '') {
                    if (!input.files[0]) {
                        showNotification(`กรุณาอัปโหลดภาพบัตรประชาชนของสมาชิกคนที่ ${index + 1}`, 'error');
                        allFilesValid = false;
                    } else {
                        const fileSize = input.files[0].size;
                        const fileType = input.files[0].type;
                        const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                        
                        if (!validTypes.includes(fileType)) {
                            showNotification(`ไฟล์บัตรประชาชนของสมาชิกคนที่ ${index + 1} ต้องเป็นไฟล์ภาพเท่านั้น (JPG, JPEG, PNG)`, 'error');
                            allFilesValid = false;
                        } else if (fileSize > 5242880) { // 5MB
                            showNotification(`ไฟล์บัตรประชาชนของสมาชิกคนที่ ${index + 1} มีขนาดใหญ่เกินไป (สูงสุด 5MB)`, 'error');
                            allFilesValid = false;
                        }
                    }
                }
            });
            
            if (!allFilesValid) {
                return false;
            }

            // ยืนยันการส่งข้อมูล
            return confirm('ยืนยันการส่งข้อมูล?');
        }
        
        // Initialize first member's image preview
        const firstImageInput = document.getElementById('id_card_image_1');
        if (firstImageInput) {
            firstImageInput.addEventListener('change', function() {
                previewImage(this, 'preview_1');
            });
        }
        
        // Add 4 more members by default (to have 5 total)
        for (let i = 0; i < 4; i++) {
            addMember();
        }

        // Smooth scrolling for form elements
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>