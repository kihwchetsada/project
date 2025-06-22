<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link rel="stylesheet" href="css/contact.css">
    <title>ติดต่อเรา - RMUTI SURIN E-Sport Club</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
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

#banner {
    width: 100%;
    height: 720px;
    background-image: url("../img/poster1.png");
    background-size: cover;
    background-position: center;
    position: relative;
    border-bottom: 2px solid var(--secondary-color);
};
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">
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

    <div class="w-full max-w-md">
        <div class="bg-white bg-opacity-20 shadow-2xl rounded-lg p-8 card animate-fade-in">
            <h1 class="text-4xl font-bold text-center text-black mb-6 text-glow">ติดต่อเรา</h1>
            
            <div class="space-y-5 text-center">
                <h2 class="text-xl text-white text-opacity-150">ชมรม RMUTI SURIN E-Sport Club</h2>
                <h2 class="text-xl text-white text-opacity-150">มหาวิทยาลัยเทคโนโลยีราชมงคลอีสาน วิทยาเขต สุรินทร์</h2>
                <h2 class="text-xl text-white text-opacity-150">อาคาร อาทิตยาทร ชั้น 5</h2>
                <h2 class="text-xl text-white text-opacity-150">คณะเกษตรศาสตร์และเทคโนโลยี</h2>
                <h2 class="text-xl text-white text-opacity-150">สาขาเทคโนโลยีคอมพิวเตอร์</h2>
            </div>
            
            <div class="flex justify-center space-x-6 mt-8">
                <a href="https://www.facebook.com/profile.php?id=100063619509747" 
                   target="_blank" 
                   class="social-link text-white hover:text-blue-300">
                    <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z"/>
                    </svg>
                </a>
                
                <a href="https://www.instagram.com/rmutisurin_esports/" 
                   target="_blank" 
                   class="social-link text-white hover:text-pink-300">
                    <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2c-2.714 0-3.055.012-4.121.06-1.064.049-1.791.218-2.427.465a4.901 4.901 0 0 0-1.772 1.153 4.9 4.9 0 0 0-1.153 1.772c-.247.636-.416 1.363-.465 2.427C2.012 8.945 2 9.286 2 12s.012 3.055.06 4.121c.049 1.064.218 1.791.465 2.427a4.9 4.9 0 0 0 1.153 1.772 4.901 4.901 0 0 0 1.772 1.153c.636.247 1.363.416 2.427.465C8.945 21.988 9.286 22 12 22s3.055-.012 4.121-.06c1.064-.049 1.791-.218 2.427-.465a4.901 4.901 0 0 0 1.772-1.153 4.9 4.9 0 0 0 1.153-1.772c.247-.636.416-1.363.465-2.427.048-1.066.06-1.407.06-4.121s-.012-3.055-.06-4.121c-.049-1.064-.218-1.791-.465-2.427a4.9 4.9 0 0 0-1.153-1.772 4.901 4.901 0 0 0-1.772-1.153c-.636-.247-1.363-.416-2.427-.465C15.055 2.012 14.714 2 12 2zm0 1.8c2.667 0 2.986.01 4.04.058.976.045 1.505.207 1.858.344.467.182.8.4 1.15.748.348.35.566.683.748 1.15.137.353.3.882.344 1.858.048 1.054.058 1.373.058 4.04s-.01 2.986-.058 4.04c-.045.976-.207 1.505-.344 1.858-.182.467-.4.8-.748 1.15a3.12 3.12 0 0 1-1.15.748c-.353.137-.882.3-1.858.344-1.054.048-1.373.058-4.04.058s-2.986-.01-4.04-.058c-.976-.045-1.505-.207-1.858-.344-.467-.182-.8-.4-1.15-.748a3.122 3.122 0 0 1-.748-1.15c-.137-.353-.3-.882-.344-1.858-.048-1.054-.058-1.373-.058-4.04s.01-2.986.058-4.04c.045-.976.207-1.505.344-1.858.182-.467.4-.8.748-1.15.35-.348.683-.566 1.15-.748.353-.137.882-.3 1.858-.344 1.054-.048 1.373-.058 4.04-.058z"/>
                        <path d="M12 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7zm0-8.9a5.4 5.4 0 1 0 0 10.8 5.4 5.4 0 0 0 0-10.8zm6.406-1.116a1.26 1.26 0 1 1-2.519 0 1.26 1.26 0 0 1 2.519 0z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <div class="w-full max-w-md mt-6">
        <div class="bg-white bg-opacity-20 shadow-2xl rounded-lg p-4 card">
            <h2 class="text-3xl font-bold text-center text-black mb-4 text-glow">แผนที่ตั้ง</h2>
            <div class="w-full aspect-w-16 aspect-h-9">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d7712.934342913407!2d103.47918274011788!3d14.85513304264751!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3119e306364fc1d7%3A0x132ca056240e0d4b!2z4Lit4Liy4LiE4Liy4Lij4Lit4LiX4Li04LiV4Lii4Liy4LiX4Lij4oCLIOC4hOC4k-C4sOC5gOC4geC4qeC4leC4o-C4qOC4suC4quC4leC4o-C5jOKAi-C5geC4peC4sOKAi-C5gOC4l-C4hOC5guC4meC5guC4peC4ouC4teKAiyDguKHguKvguLLguKfguLTguJfguKLguLLguKXguLHguKLigIvguYDguJfguITguYLguJnguYLguKXguKLguLXigIvguKPguLLguIrguKHguIfguITguKXigIvguK3guLXguKrguLLguJnigIsg4Lin4Li04LiX4Lii4Liy4LmA4LiC4LiV4oCL4Liq4Li44Lij4Li04LiZ4LiX4Lij4LmM4oCL!5e0!3m2!1sth!2sth!4v1742967583871!5m2!1sth!2sth"
                    width="100%" 
                    height="300" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade"
                    class="rounded-lg"
                ></iframe>
            </div>
        </div>
    </div>
</body>
</html>