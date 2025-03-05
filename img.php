<?php
// รวมโค้ด PHP จากไฟล์เดิม
include 'upload_script.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>อัปโหลดและเข้ารหัสภาพ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }
        .upload-container {
            background-color: #f4f4f4;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        input[type="file"] {
            margin: 20px 0;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h1>อัปโหลดภาพแบบปลอดภัย</h1>
        
        <?php if ($encryption_success): ?>
            <p class="success">อัปโหลดและเข้ารหัสสำเร็จ!</p>
        <?php endif; ?>
        
        <?php if (!empty($encryption_error)): ?>
            <p class="error"><?php echo htmlspecialchars($encryption_error); ?></p>
        <?php endif; ?>
        
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <label for="image">เลือกภาพที่ต้องการอัปโหลด (สูงสุด 5MB):</label>
            <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/gif" required>
            
            <button type="submit">อัปโหลดและเข้ารหัสภาพ</button>
        </form>
        
        <p>รองรับไฟล์: JPEG, PNG, GIF</p>
    </div>
</body>
</html>