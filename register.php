<?php
// รวมโค้ด PHP จากไฟล์เดิม
include 'upload_script.php';

// เพิ่มโค้ดสำหรับการจัดการข้อมูลทีม
$team_success = false;
$team_error = '';

// ตรวจสอบการส่งฟอร์มข้อมูลทีม
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_team'])) {
    // ตรวจสอบ CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $team_error = 'CSRF token ไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
    } else {
        // ตรวจสอบข้อมูลทีม
        $competition_type = $_POST['competition_type'] ?? '';
        $team_name = trim($_POST['team_name'] ?? '');
        $coach_name = trim($_POST['coach_name'] ?? '');
        $coach_phone = trim($_POST['coach_phone'] ?? '');
        $leader_school = trim($_POST['leader_school'] ?? '');

        if (empty($competition_type) || empty($team_name) || empty($coach_name) || empty($coach_phone) || empty($leader_school)) {
            $team_error = 'กรุณากรอกข้อมูลทีมให้ครบถ้วน';
        } else {
            // นับจำนวนสมาชิกที่มีข้อมูล
            $member_count = 0;
            for ($i = 1; $i <= 8; $i++) {
                if (!empty($_POST["member_name_$i"])) {
                    $member_count++;
                }
            }

            if ($member_count < 5) {
                $team_error = 'กรุณากรอกข้อมูลสมาชิกอย่างน้อย 5 คน';
            } else {
                // บันทึกข้อมูลทีม
                $team_data = [
                    'competition_type' => $competition_type,
                    'team_name' => $team_name,
                    'coach' => [
                        'name' => $coach_name,
                        'phone' => $coach_phone,
                        'school' => $leader_school
                    ],
                    'members' => []
                ];

                // รวบรวมข้อมูลสมาชิก
                for ($i = 1; $i <= 8; $i++) {
                    if (!empty($_POST["member_name_$i"])) {
                        $id_card_image = '';
                        $file_field = "member_id_card_$i";

                        if (!empty($_FILES[$file_field]['name'])) {
                            $upload_dir = 'uploads/';
                            $keys_dir = 'keys/';

                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            if (!is_dir($keys_dir)) {
                                mkdir($keys_dir, 0755, true);
                            }

                            $image_data = file_get_contents($_FILES[$file_field]['tmp_name']);

                            $cipher = 'aes-256-gcm';
                            $encryption_key = random_bytes(32);
                            $iv_length = openssl_cipher_iv_length($cipher);
                            $iv = random_bytes($iv_length);

                            $tag = '';
                            $encrypted_image = openssl_encrypt(
                                $image_data,
                                $cipher,
                                $encryption_key,
                                OPENSSL_RAW_DATA,
                                $iv,
                                $tag
                            );

                            $timestamp = time();
                            $encrypted_filename = $upload_dir . $team_name . '_id_card_' . $i . '.enc';
                            $key_filename = $keys_dir . $team_name . '_key_' . $i . '.txt';
                            $iv_filename = $keys_dir . $team_name . '_iv_' . $i . '.txt';
                            $tag_filename = $keys_dir . $team_name . '_tag_' . $i . '.txt';

                            file_put_contents($encrypted_filename, $encrypted_image);
                            file_put_contents($key_filename, base64_encode($encryption_key));
                            file_put_contents($iv_filename, base64_encode($iv));
                            file_put_contents($tag_filename, base64_encode($tag));

                            $id_card_image = $encrypted_filename;
                        }

                        $team_data['members'][] = [
                            'name' => $_POST["member_name_$i"],
                            'game_name' => $_POST["member_game_name_$i"] ?? '',
                            'age' => $_POST["member_age_$i"] ?? '',
                            'phone' => $_POST["member_phone_$i"] ?? '',
                            'position' => $_POST["member_position_$i"] ?? '',
                            'id_card_image' => $id_card_image
                        ];
                    }
                }

                $team_data_file = 'teams/' . $team_name . '_' . time() . '.json';
                if (!is_dir('teams')) {
                    mkdir('teams', 0755, true);
                }

                if (file_put_contents($team_data_file, json_encode($team_data, JSON_PRETTY_PRINT))) {
                    $team_success = true;
                } else {
                    $team_error = 'ไม่สามารถบันทึกข้อมูลทีมได้ กรุณาลองใหม่อีกครั้ง';
                }
            }
        }
    }
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนทีม</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #2ecc71;
            --secondary-dark: #27ae60;
            --accent-color: #f1c40f;
            --text-color: #333;
            --light-color: #f9f9f9;
            --grey-color: #ecf0f1;
            --grey-dark: #bdc3c7;
            --error-color: #e74c3c;
            --success-color: #27ae60;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Prompt', 'Sarabun', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--light-color);
            padding: 0;
        }
        
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        header h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }
        
        header p {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .form-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .section-title {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
            margin-bottom: 1rem;
        }
        
        .form-group {
            flex: 1 0 200px;
            padding: 0 10px;
            margin-bottom: 1rem;
        }
        
        .form-group.full-width {
            flex: 1 0 100%;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group label .required {
            color: var(--error-color);
            margin-left: 4px;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            border: 1px solid var(--grey-dark);
            border-radius: 5px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .file-input-container {
            position: relative;
            display: flex;
            flex-direction: column;
        }
        
        .file-input-preview {
            width: 100%;
            height: 120px;
            background-color: var(--grey-color);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            border: 2px dashed var(--grey-dark);
            overflow: hidden;
        }
        
        .file-input-preview i {
            font-size: 2rem;
            color: var(--grey-dark);
        }
        
        .file-input-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .custom-file-input {
            cursor: pointer;
        }
        
        .member-card {
            background-color: var(--grey-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            border-left: 4px solid var(--primary-color);
        }
        
        .member-card.optional {
            border-left-color: var(--accent-color);
        }
        
        .member-number {
            position: absolute;
            top: -10px;
            left: -10px;
            background-color: var(--primary-color);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .member-card.optional .member-number {
            background-color: var(--accent-color);
        }
        
        .required-tag {
            background-color: var(--primary-dark);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
            vertical-align: middle;
        }
        
        .optional-tag {
            background-color: var(--accent-color);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
            vertical-align: middle;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-success {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-2px);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 4px solid var(--error-color);
            color: var(--error-color);
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            border-left: 4px solid var(--success-color);
            color: var(--success-color);
        }
        
        .progress-container {
            margin-bottom: 2rem;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            right: -50%;
            width: 100%;
            height: 3px;
            background-color: var(--grey-dark);
            z-index: 0;
        }
        
        .step.active:not(:last-child)::after {
            background-color: var(--primary-color);
        }
        
        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--grey-dark);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .step.active .step-icon {
            background-color: var(--primary-color);
        }
        
        .step.completed .step-icon {
            background-color: var(--success-color);
        }
        
        .step-text {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--grey-dark);
        }
        
        .step.active .step-text {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .step.completed .step-text {
            color: var(--success-color);
        }
        
        footer {
            background-color: var(--primary-dark);
            color: white;
            text-align: center;
            padding: 1.5rem 0;
            margin-top: 3rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .form-group {
                flex: 1 0 100%;
            }
            
            .progress-steps {
                flex-wrap: wrap;
            }
            
            .step {
                flex: 0 0 50%;
                margin-bottom: 1rem;
            }
            
            .step:not(:last-child)::after {
                display: none;
            }
        }

        /* เพิ่มสำหรับการแสดงผลทำเสร็จ */
        .success-message {
            text-align: center;
            padding: 3rem 0;
        }
        
        .success-icon {
            font-size: 5rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }
        
        .id-preview {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: var(--grey-dark);
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>ลงทะเบียนการแข่งขัน</h1>
            <p>กรอกข้อมูลทีมของคุณเพื่อเข้าร่วมการแข่งขัน</p>
        </div>
    </header>
    
    <div class="container">
        <?php if ($team_success): ?>
            <div class="form-container success-message">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>ลงทะเบียนสำเร็จ!</h2>
                <p>ขอบคุณสำหรับการลงทะเบียน เราได้รับข้อมูลของทีม <?php echo htmlspecialchars($team_name); ?> เรียบร้อยแล้ว</p>
                <p>ทางทีมงานจะติดต่อกลับไปที่หมายเลข <?php echo htmlspecialchars($coach_phone); ?> เพื่อยืนยันการลงทะเบียน</p>
                <div style="margin-top: 2rem;">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> กลับสู่หน้าหลัก
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="progress-container">
                <div class="progress-steps">
                    <div class="step active">
                        <div class="step-icon">1</div>
                        <div class="step-text">ข้อมูลทีม</div>
                    </div>
                    <div class="step">
                        <div class="step-icon">2</div>
                        <div class="step-text">ข้อมูลสมาชิก</div>
                    </div>
                    <div class="step">
                        <div class="step-icon">3</div>
                        <div class="step-text">ตรวจสอบ</div>
                    </div>
                    <div class="step">
                        <div class="step-icon">4</div>
                        <div class="step-text">เสร็จสิ้น</div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($team_error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($team_error); ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="post" enctype="multipart/form-data" id="registrationForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="form-container">
                    <h3 class="section-title"><i class="fas fa-users-cog"></i> ข้อมูลทีม</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="competition_type">ประเภทการแข่งขัน <span class="required">*</span></label>
                            <select id="competition_type" name="competition_type" class="form-control" required>
                                <option value="">-- เลือกประเภท --</option>
                                <option value="รุ่นเยาวชน">รุ่นเยาวชน</option>
                                <option value="รุ่นประชาชน">รุ่นประชาชน</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="team_name">ชื่อทีม <span class="required">*</span></label>
                            <input type="text" id="team_name" name="team_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="coach_name">ชื่อผู้ควบคุมทีม <span class="required">*</span></label>
                            <input type="text" id="coach_name" name="coach_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="coach_phone">เบอร์โทรผู้ควบคุมทีม <span class="required">*</span></label>
                            <input type="tel" id="coach_phone" name="coach_phone" class="form-control" pattern="[0-9]{9,10}" placeholder="0xxxxxxxxx" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="leader_school">สังกัด/โรงเรียน <span class="required">*</span></label>
                            <input type="text" id="leader_school" name="leader_school" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-container">
                    <h3 class="section-title"><i class="fas fa-users"></i> ข้อมูลสมาชิก</h3>
                    <p style="margin-bottom: 1.5rem;">กรุณากรอกข้อมูลสมาชิกในทีมของคุณ (จำเป็นต้องมีอย่างน้อย 5 คน, สูงสุด 8 คน)</p>
                    
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <div class="member-card <?php echo $i > 5 ? 'optional' : ''; ?>">
                            <div class="member-number"><?php echo $i; ?></div>
                            <h4>
                                สมาชิกคนที่ <?php echo $i; ?> 
                                <?php if ($i <= 5): ?>
                                    <span class="required-tag">จำเป็น</span>
                                <?php else: ?>
                                    <span class="optional-tag">ไม่จำเป็น</span>
                                <?php endif; ?>
                            </h4>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="member_name_<?php echo $i; ?>">ชื่อ-นามสกุล <span class="required">*</span></label>
                                    <input type="text" id="member_name_<?php echo $i; ?>" name="member_name_<?php echo $i; ?>" class="form-control" <?php echo $i <= 5 ? 'required' : ''; ?>>
                                </div>
                                
                                <div class="form-group">
                                    <label for="member_game_name_<?php echo $i; ?>">ชื่อในเกม</label>
                                    <input type="text" id="member_game_name_<?php echo $i; ?>" name="member_game_name_<?php echo $i; ?>" class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="member_age_<?php echo $i; ?>">อายุ</label>
                                    <input type="number" id="member_age_<?php echo $i; ?>" name="member_age_<?php echo $i; ?>" min="7" max="99" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="member_phone_<?php echo $i; ?>">เบอร์โทรศัพท์</label>
                                    <input type="tel" id="member_phone_<?php echo $i; ?>" name="member_phone_<?php echo $i; ?>" pattern="[0-9]{9,10}" placeholder="0xxxxxxxxx" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="member_position_<?php echo $i; ?>">ตำแหน่ง</label>
                                    <input type="text" id="member_position_<?php echo $i; ?>" name="member_position_<?php echo $i; ?>" class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="member_id_card_<?php echo $i; ?>">รูปบัตรประชาชน <span class="required">*</span></label>
                                    <div class="file-input-container">
                                        <div class="file-input-preview" id="preview_id_card_<?php echo $i; ?>">
                                            <i class="fas fa-id-card"></i>
                                        </div>
                                        <input type="file" id="member_id_card_<?php echo $i; ?>" name="member_id_card_<?php echo $i; ?>" class="form-control custom-file-input" accept="image/*" <?php echo $i <= 5 ? 'required' : ''; ?>>
                                        <div class="id-preview">* กรุณาอัพโหลดภาพถ่ายบัตรประชาชนที่ชัดเจน</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" name="submit_team" class="btn btn-success btn-block">
                            <i class="fas fa-check-circle"></i> ลงทะเบียนทีม
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <footer>
        <div class="container">
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
        });
    </script>
</body>
</html>