<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าตัวเลือก</title>
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .back-button{
            position: absolute;
            top: 20px;
            left: 20px;
            background: #fff;
            color: #333;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s, color 0.3s;
        }
        .back-button:hover{
            position: absolute;
            top: 20px;
            left: 20px;
            background: #8bd0f8ff;
            color: #ffffffff;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s, color 0.3s;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }

        .title {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: #666;
            margin-bottom: 40px;
            font-size: 1.1rem;
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .option-card {
            background: white;
            border-radius: 15px;
            padding: 30px 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            display: block;
        }

        a {
            text-decoration: none !important;
            color: inherit !important;
        }

        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }

        .option-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .option-card:hover::before {
            left: 100%;
        }

        .option-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }

        .option-1 .option-icon { color: #ff6b6b; }
        .option-2 .option-icon { color: #4ecdc4; }
        .option-3 .option-icon { color: #45b7d1; }
        .option-4 .option-icon { color: #f7b731; }

        .option-card:active {
            transform: translateY(-2px) scale(0.98);
        }

        .option-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .option-desc {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .submit-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .selected {
            border-color: #667eea !important;
            background: linear-gradient(135deg, #667eea10, #764ba210);
        }

        .result {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
            
            .title {
                font-size: 2rem;
            }
            
            .options-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <a href="organizer_dashboard.php" class="back-button">กลับไปหน้าแดชบอร์ด</a>

    <div class="container">
        <h1 class="title">จัดการเกียรติบัตร</h1>
        <p class="subtitle">กรุณาเลือกหน้าที่ต้องการจะไปจากด้านล่าง</p>

        <div class="options-grid">
            <a href="Certificate/backend-index.php" style="text-decoration: none; color: inherit;">
                <div class="option-card option-1">
                    <span class="option-icon">👥</span>
                    <h3 class="option-title">เพิ่มรายชื่อ</h3>
                    <p class="option-desc">เพิ่มรายชื่อผู้ที่ได้รับเกียรติบัตร</p>
                </div>
            </a>

            <a href="Certificate/upload-template.php" style="text-decoration: none; color: inherit;">
                <div class="option-card option-2">
                    <span class="option-icon">🎨</span>
                    <h3 class="option-title">จัดการแม่แบบ</h3>
                    <p class="option-desc">สร้างและแก้ไขแม่แบบเกียรติบัตร</p>
                </div>
            </a>

            <a href="Certificate/index.php" style="text-decoration: none; color: inherit;">
                <div class="option-card option-3">
                    <span class="option-icon">📜</span>
                    <h3 class="option-title">สร้างเกียรติบัตร</h3>
                    <p class="option-desc">สร้างเกียรติบัตรจากรายชื่อและแม่แบบ</p>
                </div>
            </a>

        </div>
    </div>

    <script>
        function selectOption(optionNumber) {
            // ลบ class selected จากทุกตัวเลือก
            document.querySelectorAll('.option-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // เพิ่ม class selected ให้ตัวเลือกที่เลือก
            document.querySelector(`.option-${optionNumber}`).classList.add('selected');
            
            // เลือก radio button
            document.getElementById(`option${optionNumber}`).checked = true;
            
            // เปิดใช้งานปุ่ม submit
            document.getElementById('submitBtn').disabled = false;
        }

        // เพิ่มเอฟเฟกต์เมื่อโหลดหน้า
        window.addEventListener('load', function() {
            const cards = document.querySelectorAll('.option-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(30px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 150);
            });
        });
    </script>
</body>
</html>