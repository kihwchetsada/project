<?php
// เปิด error เพื่อ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ======= ตั้งค่า DB =======
require 'db_connect.php'; 

// ตัวแปรสำหรับแสดงข้อความ
$message = null;
$message_type = null;

// เชื่อมต่อ DB
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// รับค่าจากฟอร์ม
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ป้องกัน XSS/Injection + trim ช่องว่าง
    $name   = isset($_POST['tournament_name']) ? trim($_POST['tournament_name']) : '';
    $url    = isset($_POST['tournament_url'])  ? trim($_POST['tournament_url'])  : '';
    $mode   = isset($_POST['mode']) ? $_POST['mode'] : 'insert'; // insert | overwrite
    $target = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;

    if ($name === '' || $url === '') {
        $message = "กรุณากรอกข้อมูลให้ครบ";
        $message_type = "warning";
    } else {
        if ($mode === 'overwrite') {
            if ($target <= 0) {
                $message = "กรุณาเลือกรายการที่จะอัปเดต (แทนที่)";
                $message_type = "warning";
            } else {
                // อัปเดตแทนที่รายการที่เลือก
                $stmt = $conn->prepare("UPDATE tournaments SET tournament_name = ?, tournament_url = ? WHERE id = ?");
                // หมายเหตุ: ถ้าคอลัมน์ PK ไม่ใช่ id ให้เปลี่ยนชื่อคอลัมน์ใน WHERE
                $stmt->bind_param("ssi", $name, $url, $target);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows >= 0) {
                        $message = "✅ อัปเดต (แทนที่) รายการ #$target เรียบร้อย";
                        $message_type = "success";
                    } else {
                        $message = "ไม่พบรายการที่ต้องการแทนที่";
                        $message_type = "warning";
                    }
                } else {
                    $message = "เกิดข้อผิดพลาดตอนอัปเดต: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } else {
            // เพิ่มใหม่
            $stmt = $conn->prepare("INSERT INTO tournaments (tournament_name, tournament_url) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $url);
            if ($stmt->execute()) {
                $message = "🎉 เพิ่มทัวร์นาเมนต์สำเร็จ!";
                $message_type = "success";
            } else {
                // บอกใบ้ผู้ใช้ให้ใช้โหมดแทนที่
                $message = "เกิดข้อผิดพลาดตอนเพิ่ม: " . $stmt->error . " — หากเป็นข้อมูลซ้ำ ลองเลือกโหมด 'แทนที่รายการที่เลือก'";
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// ดึงรายการทั้งหมดมาแสดงใน dropdown/ตาราง
$tournaments = [];
$res = $conn->query("SELECT id, tournament_name, tournament_url FROM tournaments ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $tournaments[] = $row;
    }
    $res->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่ม/แทนที่ทัวร์นาเมนต์ | Tournament Manager</title>
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        * {margin:0;padding:0;box-sizing:border-box;}
        body {font-family:'Segoe UI',Tahoma, Geneva, Verdana, sans-serif;background:whitesmoke;min-height:100vh;padding:20px;}
        .container {max-width:800px;margin:0 auto;background:#fff;border-radius:20px;box-shadow:0 20px 40px rgba(0,0,0,0.1);overflow:hidden;}
        .header {background:linear-gradient(135deg,#2196F3,#21CBF3);color:#fff;padding:30px;text-align:center;}
        .header h1 {font-size:1.9rem;margin-bottom:6px;}
        .content {padding:32px;}
        .alert {padding:14px 18px;border-radius:10px;margin-bottom:22px;font-weight:500;display:flex;gap:10px;align-items:center;}
        .alert.success {background:#d4edda;border:1px solid #c3e6cb;color:#155724;}
        .alert.error {background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;}
        .alert.warning {background:#fff3cd;border:1px solid #ffeaa7;color:#856404;}
        .form-grid {display:grid;grid-template-columns:1fr;gap:18px;margin-bottom:16px;}
        .row-2 {display:grid;grid-template-columns:1fr 1fr;gap:14px;}
        .form-group label {display:block;margin-bottom:8px;font-weight:600;color:#333;}
        .form-group input, .form-group select {width:100%;padding:12px 14px;border:2px solid #e1e8ed;border-radius:12px;background:#f8f9fa;font-size:1rem;}
        .form-group input:focus, .form-group select:focus {outline:none;border-color:#2196F3;background:#fff;box-shadow:0 0 0 3px rgba(33,150,243,.1);}
        .mode-box {border:2px dashed #e1e8ed;border-radius:14px;padding:14px;}
        .note{font-size:1rem;color:red;margin-bottom:8px;}
        .mode-line {display:flex;align-items:center;gap:10px;margin-bottom:10px;}
        .btn {background:linear-gradient(135deg,#2196F3,#21CBF3);color:#fff;padding:14px 18px;border:none;border-radius:12px;font-size:1.05rem;font-weight:700;cursor:pointer;width:100%;}
        .btn:hover {transform:translateY(-1px);box-shadow:0 8px 20px rgba(33,150,243,.25);}
        .back-link {display:inline-flex;align-items:center;gap:8px;color:#6c757d;text-decoration:none;margin-top:18px;font-weight:500;border: 2px solid #555;padding: 10px 15px;border-radius: 8px;}
        .back-link:hover {background-color:#555;color:#fff;}
        .table {width:100%;border-collapse:separate;border-spacing:0 8px;margin-top:26px;}
        .table th {text-align:left;font-size:.9rem;color:#555;padding:8px 10px;}
        .table td {background:#f8f9fa;padding:12px 10px;border-radius:10px;}
        @media (max-width: 768px) {.row-2 {grid-template-columns:1fr;}}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-trophy"></i> เพิ่ม/แทนที่ทัวร์นาเมนต์</h1>
            <p>กรอกข้อมูลใหม่ แล้วเลือกโหมดว่าจะ “เพิ่มใหม่” หรือ “แทนที่รายการที่เลือก”</p>
        </div>

        <div class="content">
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo htmlspecialchars($message_type); ?>">
                    <?php if ($message_type === 'success'): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php elseif ($message_type === 'error'): ?>
                        <i class="fas fa-exclamation-triangle"></i>
                    <?php else: ?>
                        <i class="fas fa-info-circle"></i>
                    <?php endif; ?>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form method="post" id="tForm" onsubmit="return beforeSubmit();">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="tournament_name"><i class="fas fa-gamepad"></i> ชื่อทัวร์นาเมนต์</label>
                        <input type="text" name="tournament_name" id="tournament_name" required
                            placeholder="ใส่ชื่อทัวร์นาเมนต์..."
                        value="<?php echo isset($_POST['tournament_name']) ? htmlspecialchars($_POST['tournament_name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="tournament_url"><i class="fas fa-link"></i> URL ของ Challonge</label>
                        <input type="text" name="tournament_url" id="tournament_url" required
                            placeholder="เช่น ROV_RMUTI5"
                            value="<?php echo isset($_POST['tournament_url']) ? htmlspecialchars($_POST['tournament_url']) : ''; ?>">
                    </div>

                    <div class="mode-box">
                        <p class="note">**สามารถเพิ่มได้สูงสุด 2 รายการเท่านั้น**</p>
                        <div class="mode-line">
                            <input type="radio" id="mode-insert" name="mode" value="insert"
                            <?php echo (!isset($_POST['mode']) || $_POST['mode'] === 'insert') ? 'checked' : ''; ?>
                            onchange="toggleMode()" />
                            <label for="mode-insert"><strong>เพิ่มใหม่</strong> (INSERT)</label>
                        </div>
                        <div class="mode-line">
                            <input type="radio" id="mode-overwrite" name="mode" value="overwrite"
                                <?php echo (isset($_POST['mode']) && $_POST['mode'] === 'overwrite') ? 'checked' : ''; ?>
                                onchange="toggleMode()" />
                            <label for="mode-overwrite"><strong>แทนที่รายการที่เลือก</strong> (UPDATE)</label>
                        </div>

                        <div class="row-2" style="margin-top:10px;">
                            <div class="form-group">
                                <label for="target_id"><i class="fas fa-list"></i> เลือกรายการที่จะอัปเดต (แทนที่)</label>
                                <select name="target_id" id="target_id" <?php echo (!isset($_POST['mode']) || $_POST['mode'] === 'insert') ? 'disabled' : ''; ?>>
                                    <option value="0">— เลือก —</option>
                                    <?php foreach ($tournaments as $t): ?>
                                        <option value="<?php echo (int)$t['id']; ?>"
                                            <?php
                                                $sel = (isset($_POST['target_id']) && (int)$_POST['target_id'] === (int)$t['id']) ? 'selected' : '';
                                                echo $sel;
                                            ?>>
                                            id<?php echo (int)$t['id']; ?> — <?php echo htmlspecialchars($t['tournament_name']); ?> (<?php echo htmlspecialchars($t['tournament_url']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small style="color:#6c757d;display:block;margin-top:6px;">* เมื่อเลือกโหมด “แทนที่” ระบบจะอัปเดตชื่อ+URL ตามค่าที่กรอกด้านบน</small>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> บันทึก
                </button>
            </form>

            <a href="backend/admin_dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> กลับไปหน้าแดชบอร์ด
            </a>

            <h3 style="margin-top:28px;margin-bottom:8px;">รายการที่มีอยู่</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:80px;">ID</th>
                        <th>ชื่อทัวร์นาเมนต์</th>
                        <th>URL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tournaments)): ?>
                        <tr><td colspan="3" style="padding:8px 0;color:#777;">(ยังไม่มีข้อมูล)</td></tr>
                    <?php else: foreach ($tournaments as $t): ?>
                        <tr>
                            <td><div class="td"><?php echo (int)$t['id']; ?></div></td>
                            <td><div class="td"><?php echo htmlspecialchars($t['tournament_name']); ?></div></td>
                            <td><div class="td"><?php echo htmlspecialchars($t['tournament_url']); ?></div></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function toggleMode() {
        const overwrite = document.getElementById('mode-overwrite').checked;
        document.getElementById('target_id').disabled = !overwrite;
    }
    function beforeSubmit() {
        const modeOverwrite = document.getElementById('mode-overwrite').checked;
        if (modeOverwrite) {
            const sel = document.getElementById('target_id');
            if (sel.value === "0") {
                alert("กรุณาเลือกรายการที่จะอัปเดต (แทนที่)");
                return false;
            }
            return confirm("⚠️ คุณกำลังจะ 'แทนที่' รายการที่เลือกด้วยค่าที่กรอกด้านบน\nยืนยันดำเนินการ?");
        }
        return true;
    }
    // init
    toggleMode();
    </script>
</body>
</html>
