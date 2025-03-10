<?php
// รวมโค้ด PHP จากไฟล์เดิม
include 'upload_script.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัปโหลดและเข้ารหัสภาพ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link rel="stylesheet" href="../css/img.css">
</head>
<body>
    <div class="upload-container">
        <h1>อัปโหลดภาพแบบปลอดภัย</h1>
        
        <?php if ($encryption_success): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <span>อัปโหลดและเข้ารหัสสำเร็จ!</span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($encryption_error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <span>เกิดข้อผิดพลาด: <?php echo htmlspecialchars($encryption_error); ?></span>
            </div>
        <?php endif; ?>
        
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="file-upload" id="dropZone">
                <i class="fas fa-cloud-upload-alt"></i>
                <div class="file-label">ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</div>
                <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/gif" required>
                <div class="file-name" id="fileName"></div>
            </div>
            
            <button type="submit">
                <i class="fas fa-lock"></i>
                อัปโหลดและเข้ารหัสภาพ
            </button>
        </form>
        
        <div class="file-types">
            <i class="fas fa-info-circle"></i> รองรับไฟล์: JPEG, PNG, GIF (สูงสุด 5MB)
        </div>
        
        <!-- ส่วนแสดงข้อมูลดีบัก (ซ่อนไว้ในกรณีปกติ) -->
        <div class="debug-info" id="debugInfo">
            <h3>ข้อมูลการตรวจสอบ:</h3>
            <ul>
                <?php 
                // ตรวจสอบการตั้งค่า PHP
                $upload_max = ini_get('upload_max_filesize');
                $post_max = ini_get('post_max_size');
                $file_uploads = ini_get('file_uploads');
                ?>
                <li>upload_max_filesize: <?php echo $upload_max; ?></li>
                <li>post_max_size: <?php echo $post_max; ?></li>
                <li>file_uploads: <?php echo $file_uploads ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?></li>
                <li>ไดเร็กทอรี่ uploads: <?php echo is_dir($upload_dir) ? 'มีอยู่' : 'ไม่มี'; ?></li>
                <li>สิทธิ์การเขียน uploads: <?php echo is_writable($upload_dir) ? 'เขียนได้' : 'เขียนไม่ได้'; ?></li>
                <li>ไดเร็กทอรี่ keys: <?php echo is_dir($keys_dir) ? 'มีอยู่' : 'ไม่มี'; ?></li>
                <li>สิทธิ์การเขียน keys: <?php echo is_writable($keys_dir) ? 'เขียนได้' : 'เขียนไม่ได้'; ?></li>
                <li>OpenSSL: <?php echo function_exists('openssl_encrypt') ? 'ใช้งานได้' : 'ใช้งานไม่ได้'; ?></li>
                <li>OpenSSL เวอร์ชัน: <?php echo OPENSSL_VERSION_TEXT ?? 'ไม่ทราบ'; ?></li>
            </ul>
        </div>
        
        <?php if (!empty($encryption_error)): ?>
        <script>
            // แสดงข้อมูลดีบักเมื่อเกิดข้อผิดพลาด
            document.getElementById('debugInfo').style.display = 'block';
        </script>
        <?php endif; ?>
        
        <script>
            // อัพเดทชื่อไฟล์เมื่อผู้ใช้เลือกไฟล์
            document.getElementById('image').addEventListener('change', function(e) {
                const fileName = e.target.files[0] ? e.target.files[0].name : '';
                document.getElementById('fileName').textContent = fileName;
            });
            
            // ทำให้สามารถลากและวางไฟล์ได้
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('image');
            
            dropZone.addEventListener('click', () => {
                fileInput.click();
            });
            
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '#4361ee';
                dropZone.style.backgroundColor = 'rgba(67, 97, 238, 0.05)';
            });
            
            dropZone.addEventListener('dragleave', () => {
                dropZone.style.borderColor = '#d1d5db';
                dropZone.style.backgroundColor = 'transparent';
            });
            
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '#d1d5db';
                dropZone.style.backgroundColor = 'transparent';
                
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    document.getElementById('fileName').textContent = e.dataTransfer.files[0].name;
                }
            });
        </script>
    </div>
</body>
</html>