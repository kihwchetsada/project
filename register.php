<?php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "competition_system";

// สร้างการเชื่อมต่อ
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $e->getMessage());
}

// ฟังก์ชันสำหรับ validate ข้อมูล
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

function getCompetitions($conn) {
    try {
        $sql = "SELECT id, name, type FROM competitions WHERE status = 'active'";
        $result = $conn->query($sql);
        
        if ($result === false) {
            throw new Exception("Error executing query: " . $conn->error);
        }
        
        $competitions = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $competitions[] = $row;
            }
        }
        return $competitions;
    } catch (Exception $e) {
        error_log("Database error in getCompetitions: " . $e->getMessage());
        return [];
    }
}

$errors = [];
$success = false;

// ดึงข้อมูลการแข่งขัน
try {
    $competitions = getCompetitions($conn);
    if (empty($competitions)) {
        $errors[] = "ไม่พบข้อมูลการแข่งขันที่เปิดรับสมัคร หรือเกิดข้อผิดพลาดในการดึงข้อมูล";
    }
} catch (Exception $e) {
    $errors[] = "เกิดข้อผิดพลาดในการดึงข้อมูลการแข่งขัน: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate team data
        $team_name = validateInput($_POST['team_name'] ?? '');
        $competition_id = isset($_POST['competition_id']) ? (int)$_POST['competition_id'] : 0;
        $advisor = validateInput($_POST['advisor'] ?? '');
        $contact = validateInput($_POST['contact'] ?? '');
        $email = validateInput($_POST['email'] ?? '');

        if (empty($team_name)) {
            $errors[] = "กรุณากรอกชื่อทีม";
        }

        // ตรวจสอบ competition_id
        if ($competition_id <= 0) {
            $errors[] = "กรุณาเลือกรายการแข่งขัน";
        } else {
            // ตรวจสอบว่า competition_id มีอยู่จริง
            $sql_check = "SELECT id FROM competitions WHERE id = ? AND status = 'active'";
            $stmt_check = $conn->prepare($sql_check);
            if (!$stmt_check) {
                throw new Exception("Error preparing check statement: " . $conn->error);
            }
            
            $stmt_check->bind_param("i", $competition_id);
            if (!$stmt_check->execute()) {
                throw new Exception("Error executing check statement: " . $stmt_check->error);
            }
            
            $result_check = $stmt_check->get_result();
            if ($result_check->num_rows === 0) {
                $errors[] = "รายการแข่งขันที่เลือกไม่ถูกต้องหรือไม่ได้เปิดรับสมัคร";
            }
            $stmt_check->close();
        }

        if (empty($advisor)) {
            $errors[] = "กรุณากรอกชื่อผู้ดูแลทีม";
        }

        if (!validatePhone($contact)) {
            $errors[] = "รูปแบบเบอร์โทรไม่ถูกต้อง";
        }

        if (!validateEmail($email)) {
            $errors[] = "รูปแบบอีเมลไม่ถูกต้อง";
        }

        // Validate team members
        $member_names = $_POST['member_name'] ?? [];
        $roles = $_POST['role'] ?? [];
        
        if (count(array_filter($member_names)) < 5) {
            $errors[] = "ต้องมีสมาชิกทีมอย่างน้อย 5 คน";
        }

        if (empty($errors)) {
            $conn->begin_transaction();

            // บันทึกข้อมูลทีม
            $sql_team = "INSERT INTO teams (team_name, competition_id, advisor, contact, email) 
                        VALUES (?, ?, ?, ?, ?)";
            $stmt_team = $conn->prepare($sql_team);
            
            if (!$stmt_team) {
                throw new Exception("Error preparing team statement: " . $conn->error);
            }

            $stmt_team->bind_param("sisss", $team_name, $competition_id, $advisor, $contact, $email);
            
            if (!$stmt_team->execute()) {
                throw new Exception("Error executing team statement: " . $stmt_team->error);
            }

            $team_id = $stmt_team->insert_id;

            // บันทึกข้อมูลสมาชิก
            $sql_member = "INSERT INTO team_members (team_id, player_name, role) VALUES (?, ?, ?)";
            $stmt_member = $conn->prepare($sql_member);

            if (!$stmt_member) {
                throw new Exception("Error preparing member statement: " . $conn->error);
            }

            for ($i = 0; $i < count($member_names); $i++) {
                if (!empty($member_names[$i]) && !empty($roles[$i])) {
                    $member_name = validateInput($member_names[$i]);
                    $role = validateInput($roles[$i]);
                    
                    $stmt_member->bind_param("iss", $team_id, $member_name, $role);
                    
                    if (!$stmt_member->execute()) {
                        throw new Exception("Error inserting member: " . $stmt_member->error);
                    }
                }
            }

            $conn->commit();
            $success = true;
            $stmt_member->close();
            $stmt_team->close();

        }
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROV Tournament Hub</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link rel="stylesheet" href="css/r.css">
</head>
<body>
    <h2 style="text-align: center;">สมัครทีมแข่งขัน</h2>
    
    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success">บันทึกข้อมูลสำเร็จ!</div>
    <?php endif; ?>

    <?php if (empty($competitions)): ?>
        <div class="error">ขณะนี้ยังไม่มีรายการแข่งขันที่เปิดรับสมัคร</div>
    <?php else: ?>
        <form method="POST" onsubmit="return validateForm()">
            <label for="team_name" class="required">ชื่อทีม</label>
            <input type="text" id="team_name" name="team_name" required 
                value="<?php echo htmlspecialchars($_POST['team_name'] ?? ''); ?>">

            <label for="competition_id" class="required">รายการแข่งขัน</label>
            <select id="competition_id" name="competition_id" required>
                <option value="">-- เลือกรายการแข่งขัน --</option>
                <?php foreach ($competitions as $competition): ?>
                    <option value="<?php echo htmlspecialchars($competition['id']); ?>"
                        <?php echo (isset($_POST['competition_id']) && $_POST['competition_id'] == $competition['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($competition['name'] . ' - ' . $competition['type']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="advisor" class="required">ชื่อผู้ดูแลทีม</label>
            <input type="text" id="advisor" name="advisor" required
                value="<?php echo htmlspecialchars($_POST['advisor'] ?? ''); ?>">

            <label for="contact" class="required">เบอร์ติดต่อ (เช่น 0812345678)</label>
            <input type="tel" id="contact" name="contact" pattern="[0-9]{10}" required
                value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>">

            <label for="email" class="required">อีเมล</label>
            <input type="email" id="email" name="email" required
                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

            <h3>ข้อมูลสมาชิกทีม (สูงสุด 7 คน) *</h3>
            <div id="members">
                <div class="member">
                    <label for="member_name_1" class="required">ชื่อสมาชิก</label>
                    <input type="text" name="member_name[]" id="member_name_1" required
                        value="<?php echo htmlspecialchars($_POST['member_name'][0] ?? ''); ?>">
                    <label for="role_1" class="required">ตำแหน่ง</label>
                    <input type="text" name="role[]" id="role_1" required
                        value="<?php echo htmlspecialchars($_POST['role'][0] ?? ''); ?>">
                </div>
            </div>
            <button type="button" onclick="addMember()">เพิ่มสมาชิก</button>
            <button type="submit">สมัคร</button>
        </form>
    <?php endif; ?>

    <script>
        let memberCount = 1;

        function addMember() {
            if (memberCount < 7) {
                memberCount++;
                const membersDiv = document.getElementById('members');
                const memberDiv = document.createElement('div');
                memberDiv.className = 'member';
                memberDiv.innerHTML = `
                    <label for="member_name_${memberCount}" class="required">ชื่อสมาชิก</label>
                    <input type="text" name="member_name[]" id="member_name_${memberCount}" required>
                    <label for="role_${memberCount}" class="required">ตำแหน่ง</label>
                    <input type="text" name="role[]" id="role_${memberCount}" required>
                    <span class="remove-member" onclick="removeMember(this)">ลบ</span>
                `;
                membersDiv.appendChild(memberDiv);
            } else {
                alert('เพิ่มสมาชิกได้สูงสุด 7 คนเท่านั้น');
            }
        }

        function removeMember(element) {
            if (memberCount > 5) {
                element.parentElement.remove();
                memberCount--;
            } else {
                alert('ต้องมีสมาชิกอย่างน้อย 5 คน');
            }
        }

        function validateForm() {
            const memberInputs = document.querySelectorAll('input[name="member_name[]"]');
            let filledMembers = 0;
            memberInputs.forEach(input => {
                if (input.value.trim() !== '') filledMembers++;
            });

            if (filledMembers === 0) {
                alert('กรุณากรอกข้อมูลสมาชิกอย่างน้อย 1 คน');
                return false;
            }

            if (!confirm('ยืนยันการส่งข้อมูล?')) {
                return false;
            }

            return true;
        }
    </script>
</body>
</html>