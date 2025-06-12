<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตารางการแข่งขัน</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link rel="stylesheet" href="css/event-info.css">
    <style>
        :root {
    --primary-color: #2e91ca;
    --secondary-color: #8e44ad;
    --accent-color: #e74c3c;
    --dark-bg: #0a0e17;
    --light-text: #ffffff;
    --nav-hover: #2980b9;
}

      .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 5%;
        background-color: rgba(10, 14, 23, 0.9);
        -webkit-backdrop-filter: blur(15px);
        backdrop-filter: blur(15px);
        border-bottom: 2px solid var(--primary-color);
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.5);
    }

    .logo {
        display: flex;
        align-items: center;
    }

    .logo img {
        width: 60px;
        height: 60px;
        border-radius: 30px;
        object-fit: cover;
        border: 3px solid var(--primary-color);
        margin-right: 15px;
        box-shadow: 0 0 15px rgba(52, 152, 219, 0.7);
        transition: transform 0.5s ease, box-shadow 0.5s ease;
    }

    .logo img:hover {
        transform: scale(1.05);
        box-shadow: 0 0 20px rgba(41, 45, 244, 0.9);
    }

    .logo h2 {
        color: var(--primary-color);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        text-shadow: 0 0 10px rgba(219, 52, 52, 0.5);
        transition: color 0.3s ease;
    }

    .logo:hover h2 {
        color: #992222;
        text-shadow: 0 0 15px rgba(0, 0, 0, 0.6);
    }

    nav {
        display: flex;
        gap: 25px;
    }

    nav a {
        color: var(--light-text);
        text-decoration: none;
        font-weight: 600;
        padding: 10px 18px;
        border-radius: 8px;
        transition: all 0.3s ease;
        position: relative;
        letter-spacing: 1px;
        text-transform: uppercase;
        font-size: 1.3rem;
    }

    nav a:hover {
        color: var(--primary-color);
        background-color: rgba(52, 152, 219, 0.1);
        transform: translateY(-3px);
    }

    nav a::after {
        content: '';
        position: absolute;
        width: 0;
        height: 3px;
        background-color: var(--primary-color);
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        transition: width 0.3s ease;
        border-radius: 3px;
    }

    nav a:hover::after {
        width: 70%;
    }

        .iframe-container {
            display: none;
        }

    </style>
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

    <div class="container">
        <h1>ตารางการแข่งขัน</h1>
        
        <div class="rules-container">
            <div class="rule">
                <h2>รุ่นอายุไม่เกิน 18 ปี</h2>
                <p>รุ่นระดับมัธยมศึกษาหรืออาชีวศึกษา อายุต่ำกว่า 18 ปี</p>
                <a href="javascript:void(0);" onclick="showIframe('iframe1')">ตารางการแข่งขัน</a>
                <div id="iframe1" class="iframe-container">
                    <h3>ตารางการแข่งขัน</h3>
                    <iframe src="https://challonge.com/th/ROV_RMUTI5/module" width="100%" height="500" frameborder="100" scrolling="auto" allowtransparency="true"></iframe>
                </div>
            </div>
    
            <div class="rule">
                <h2>รุ่นอายุตั้งแต่ 18 ปีขึ้นไป</h2>
                <p>รุ่นระดับอุดมศึกษาหรือบุคคลทั่วไป ไม่จำกัดอายุ</p>
                <a href="javascript:void(0);" onclick="showIframe('iframe2')">ตารางการแข่งขัน</a>
                <div id="iframe2" class="iframe-container">
                    <h3>ตารางการแข่งขัน</h3>
                    <iframe src="https://challonge.com/th/ROV_RMUTI5/module" width="100%" height="500" frameborder="100" scrolling="auto" allowtransparency="true"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showIframe(iframeId) {
            var iframe = document.getElementById(iframeId);
            iframe.style.display = 'block';
        }
    </script>
</body>
</html>
