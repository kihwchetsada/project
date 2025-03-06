<?php
// สคริปต์สำหรับการถอดรหัสและแสดงรูปภาพ
session_start();

// กำหนดค่าตำแหน่งที่เก็บไฟล์
$upload_dir = 'uploads/';
$keys_dir = 'keys/';

// ตรวจสอบการเข้าถึง (เพิ่มการตรวจสอบผู้ใช้ตามต้องการ)
$auth_error = '';
$images = [];
if (is_dir($upload_dir) && is_dir($keys_dir)) {
    $encrypted_files = glob($upload_dir . '*.enc');
    
    foreach ($encrypted_files as $encrypted_file) {
        $filename = basename($encrypted_file);
        $file_id = pathinfo($filename, PATHINFO_FILENAME); // ชื่อไฟล์ไม่รวมนามสกุล
        $key_file = $keys_dir . $file_id . '_key.txt';
        
        if (file_exists($key_file)) {
            // ดึงข้อมูลวันที่อัปโหลด
            $upload_date = date('d/m/Y H:i', filemtime($encrypted_file));
            
            // ขนาดไฟล์
            $file_size = round(filesize($encrypted_file) / 1024, 2); // ขนาดเป็น KB
            
            $images[] = [
                'id' => $file_id,
                'encrypted_file' => $encrypted_file,
                'key_file' => $key_file,
                'upload_date' => $upload_date,
                'file_size' => $file_size
            ];
        }
    }
    
    // เรียงลำดับตามวันที่อัปโหลดล่าสุด
    usort($images, function($a, $b) {
        return filemtime($b['encrypted_file']) - filemtime($a['encrypted_file']);
    });
}

// ฟังก์ชั่นสำหรับถอดรหัสรูปภาพ
function decryptImage($encrypted_file, $file_id) {
    global $keys_dir;
    $key_file = $keys_dir . $file_id . '_key.txt';
    $iv_file = $keys_dir . $file_id . '_iv.txt';
    $tag_file = $keys_dir . $file_id . '_tag.txt';
    
    if (!file_exists($encrypted_file) || !file_exists($key_file) || !file_exists($iv_file)) {
        return false;
    }
    
    // อ่าน key และ IV
    $encryption_key = base64_decode(file_get_contents($key_file));
    $iv = base64_decode(file_get_contents($iv_file));
    
    // อ่านข้อมูลที่เข้ารหัสแล้ว
    $encrypted_data = file_get_contents($encrypted_file);
    
    // ตรวจสอบว่ามี GCM tag หรือไม่
    if (file_exists($tag_file)) {
        $tag = base64_decode(file_get_contents($tag_file));
        $cipher = 'aes-256-gcm';
    } else {
        $cipher = 'aes-256-cbc';
    }
    
    // ถอดรหัสข้อมูล
    if ($cipher === 'aes-256-gcm') {
        $decrypted_data = openssl_decrypt(
            $encrypted_data,
            $cipher,
            $encryption_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    } else {
        $decrypted_data = openssl_decrypt(
            $encrypted_data,
            $cipher,
            $encryption_key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
    
    if ($decrypted_data === false) {
        return false;
    }
    
    return $decrypted_data;
}

// ดึงข้อมูลรายการรูปภาพที่เข้ารหัสไว้
if (is_dir($upload_dir) && is_dir($keys_dir)) {
    $encrypted_files = glob($upload_dir . '*.enc');
    
    foreach ($encrypted_files as $encrypted_file) {
        $filename = basename($encrypted_file);
        $file_id = pathinfo($filename, PATHINFO_FILENAME); // ชื่อไฟล์ไม่รวมนามสกุล
        $key_file = $keys_dir . $file_id . '.key';
        
        // ดึงข้อมูลวันที่อัปโหลด
        $upload_date = date('d/m/Y H:i', filemtime($encrypted_file));
        
        // ขนาดไฟล์
        $file_size = round(filesize($encrypted_file) / 1024, 2); // ขนาดเป็น KB
        
        $images[] = [
            'id' => $file_id,
            'encrypted_file' => $encrypted_file,
            'key_file' => $key_file,
            'upload_date' => $upload_date,
            'file_size' => $file_size
        ];
    }
    
    // เรียงลำดับตามวันที่อัปโหลดล่าสุด
    usort($images, function($a, $b) {
        return filemtime($b['encrypted_file']) - filemtime($a['encrypted_file']);
    });
}

// ถ้ามีการขอดูภาพ
$view_image = null;
$image_error = null;
$image_mime = null;

if (isset($_GET['view']) && !empty($_GET['view'])) {
    $image_id = $_GET['view'];
    
    // ตรวจสอบว่ามีรูปภาพที่ต้องการหรือไม่
    $image_found = false;
    foreach ($images as $img) {
        if ($img['id'] === $image_id) {
            $image_found = true;
            $decrypted_data = decryptImage($img['encrypted_file'], $img['id']);
            
            if ($decrypted_data !== false) {
                // ตรวจสอบ MIME type
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $image_mime = $finfo->buffer($decrypted_data);
                
                // ตรวจสอบว่าเป็นรูปภาพจริงหรือไม่
                if (strpos($image_mime, 'image/') === 0) {
                    $view_image = [
                        'id' => $img['id'],
                        'data' => base64_encode($decrypted_data),
                        'mime' => $image_mime,
                        'upload_date' => $img['upload_date']
                    ];
                } else {
                    $image_error = 'ไฟล์นี้ไม่ใช่รูปภาพที่รองรับ';
                }
            } else {
                $image_error = 'ไม่สามารถถอดรหัสรูปภาพได้';
            }
            break;
        }
    }
    
    if (!$image_found) {
        $image_error = 'ไม่พบรูปภาพที่ต้องการ';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบดูรูปภาพที่อัปโหลด</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/view_img.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>รูปภาพที่อัปโหลดไว้</h1>
            <a href="upload_img.php" class="upload-btn">
                <i class="fas fa-cloud-upload-alt"></i>
                อัปโหลดรูปภาพใหม่
            </a>
        </header>

        <?php if (!empty($auth_error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($auth_error); ?>
            </div>
        <?php elseif (empty($images)): ?>
            <div class="no-images">
                <i class="fas fa-image"></i>
                <h2>ไม่พบรูปภาพที่อัปโหลดไว้</h2>
                <p>คุณยังไม่ได้อัปโหลดรูปภาพใดๆ หรือไม่มีรูปภาพที่สามารถเข้าถึงได้</p>
                <a href="upload_img.php" class="upload-btn">
                    <i class="fas fa-cloud-upload-alt"></i>
                    อัปโหลดรูปภาพแรกของคุณ
                </a>
            </div>
        <?php else: ?>
            <div class="image-grid">
                <?php foreach ($images as $image): ?>
                <div class="image-card" onclick="openImageViewer('<?php echo htmlspecialchars($image['id']); ?>')">
                    <div class="image-placeholder">
                        <i class="fas fa-image"></i>
                    </div>
                    <div class="image-info">
                        <h3><?php echo htmlspecialchars($image['id']); ?></h3>
                        <div class="image-meta">
                            <span><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($image['upload_date']); ?></span>
                            <span><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($image['file_size']); ?> KB</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Image Viewer -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">ดูรูปภาพ</div>
                <button class="close-modal" onclick="closeImageViewer()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be loaded here -->
                <div class="loading">กำลังโหลดรูปภาพ...</div>
            </div>
            <div class="modal-meta" id="modalMeta">
                <!-- Metadata will be loaded here -->
            </div>
            <div class="modal-actions">
                <button class="download-btn" id="downloadBtn">
                    <i class="fas fa-download"></i> ดาวน์โหลด
                </button>
                <button class="delete-btn" id="deleteBtn">
                    <i class="fas fa-trash-alt"></i> ลบรูปภาพนี้
                </button>
            </div>
        </div>
    </div>

    <script>
        // ฟังก์ชั่นเปิดโมดัลแสดงรูปภาพ
        function openImageViewer(imageId) {
            const modal = document.getElementById('imageModal');
            const modalBody = document.getElementById('modalBody');
            const modalMeta = document.getElementById('modalMeta');
            const downloadBtn = document.getElementById('downloadBtn');
            const deleteBtn = document.getElementById('deleteBtn');
            
            // แสดงโมดัล
            modal.style.display = 'block';
            
            // กำหนดการโหลดข้อมูล
            modalBody.innerHTML = '<div style="text-align: center; padding: 30px;"><i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i><p style="margin-top: 10px;">กำลังโหลดรูปภาพ...</p></div>';
            
            // โหลดรูปภาพจาก URL
            fetch('?view=' + encodeURIComponent(imageId))
                .then(response => response.text())
                .then(html => {
                    // สร้าง parser สำหรับ HTML
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // ตรวจสอบว่ามีข้อมูลรูปภาพหรือไม่
                    if (doc.getElementById('viewImage')) {
                        const imgSrc = doc.getElementById('viewImage').getAttribute('src');
                        const uploadDate = doc.getElementById('imageDate').textContent;
                        
                        // แสดงรูปภาพในโมดัล
                        modalBody.innerHTML = `<img src="${imgSrc}" class="modal-image" id="modalImage" alt="รูปภาพที่ดู">`;
                        modalMeta.innerHTML = `<span><i class="fas fa-calendar-alt"></i> วันที่อัปโหลด: ${uploadDate}</span>`;
                        
                        // กำหนดปุ่มดาวน์โหลด
                        downloadBtn.onclick = function() {
                            const a = document.createElement('a');
                            a.href = imgSrc;
                            a.download = imageId + '.jpg'; // กำหนดชื่อไฟล์ดาวน์โหลด
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                        };
                        
                        // กำหนดปุ่มลบ (สามารถเพิ่มการยืนยันและฟังก์ชันลบได้ตามต้องการ)
                        deleteBtn.onclick = function() {
                            if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรูปภาพนี้?')) {
                                alert('ฟังก์ชันลบรูปภาพยังไม่ได้ถูกพัฒนา กรุณาติดต่อผู้ดูแลระบบ');
                                // ในกรณีที่ต้องการเพิ่มฟังก์ชันลบจริง สามารถใช้ fetch หรือ AJAX เพื่อส่งคำขอลบไปยังสคริปต์ PHP
                            }
                        };
                    } else {
                        // กรณีไม่พบรูปภาพหรือมีข้อผิดพลาด
                        modalBody.innerHTML = '<div class="modal-error"><i class="fas fa-exclamation-circle"></i> ไม่สามารถโหลดรูปภาพได้</div>';
                        modalMeta.innerHTML = '';
                        
                        downloadBtn.disabled = true;
                        deleteBtn.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('เกิดข้อผิดพลาดในการโหลดรูปภาพ:', error);
                    modalBody.innerHTML = '<div class="modal-error"><i class="fas fa-exclamation-circle"></i> เกิดข้อผิดพลาดในการโหลดรูปภาพ</div>';
                    modalMeta.innerHTML = '';
                    
                    downloadBtn.disabled = true;
                    deleteBtn.disabled = true;
                });
        }
        
        // ฟังก์ชั่นปิดโมดัล
        function closeImageViewer() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
            
            // รีเซ็ตปุ่ม
            document.getElementById('downloadBtn').disabled = false;
            document.getElementById('deleteBtn').disabled = false;
        }
        
        // ปิดโมดัลเมื่อคลิกนอกพื้นที่เนื้อหา
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                closeImageViewer();
            }
        }
    </script>
    
    <?php if ($view_image || $image_error): ?>
    <div style="display: none;">
        <?php if ($view_image): ?>
            <img id="viewImage" src="data:<?php echo htmlspecialchars($view_image['mime']); ?>;base64,<?php echo $view_image['data']; ?>" alt="รูปภาพที่ดู">
            <div id="imageDate"><?php echo htmlspecialchars($view_image['upload_date']); ?></div>
        <?php else: ?>
            <div id="imageError"><?php echo htmlspecialchars($image_error); ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</body>
</html>