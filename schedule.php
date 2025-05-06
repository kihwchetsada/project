<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตารางการแข่งขัน</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link rel="stylesheet" href="css/event-info.css">
    <style>
        .iframe-container {
            display: none;
        }
    </style>
</head>
<body>
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
