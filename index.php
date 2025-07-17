<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROV Tournament Hub</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
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
    
    <section id="banner">   
        <section class="hero">
            <div class="hero-content">
                <h1>RMUTI Tournament System</h1>
                <p>ราชมงคลอีสปอร์ต ROV ประจำปี <?php echo date('Y'); ?></p>
                <a href="register.php" class="btn">สมัครทีม</a>
            </div>
        </section>
    </section>

</body>

  <footer class="footer">
        <p>© <?php echo date('Y'); ?> RMUTI Tournament System by Chetsadaphon wongwiwong</p>
    </footer>
</html>