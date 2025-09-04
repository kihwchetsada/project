<?php
require('fpdf/fpdf.php');
require('db_connect.php');

// ฟังก์ชันช่วยแปลงข้อความจาก UTF-8 เป็น TIS-620
function u_to_tis($text) {
    return iconv('UTF-8', 'TIS-620', $text);
}

// ตรวจสอบ tournament id จาก URL
if (!isset($_GET['tid']) || !is_numeric($_GET['tid'])) {
    die("ไม่พบ Tournament");
}
$tournament_id = (int)$_GET['tid'];

// ดึงข้อมูล Tournament (โค้ดส่วนนี้เหมือนเดิม)
$stmtT = $conn->prepare("SELECT * FROM tournaments WHERE id = :id");
$stmtT->execute(['id' => $tournament_id]);
$tournament = $stmtT->fetch(PDO::FETCH_ASSOC);

if (!$tournament) {
    die("ไม่พบ Tournament ที่เลือก");
}

// 2. สร้าง Object PDF
$pdf = new FPDF('P', 'mm', 'A4'); // P=แนวตั้ง, mm=หน่วยมิลลิเมตร, A4=ขนาดกระดาษ
$pdf->AddPage();

// 3. เพิ่มฟอนต์ภาษาไทย (ให้ตรงกับชื่อไฟล์ฟอนต์ของคุณ)
$pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
// 4. สร้างส่วนหัวของเอกสาร
$pdf->SetFont('THSarabunNew', '', 18);
$pdf->Cell(0, 10, u_to_tis('รายชื่อทีมและสมาชิกทีม'), 0, 1, 'C');

$pdf->SetFont('THSarabunNew', '', 16);
$pdf->Cell(0, 10, u_to_tis('Tournament: ' . $tournament['tournament_name']), 0, 1, 'C');
$pdf->Ln(5); // เว้นบรรทัดเล็กน้อย

// 5. ดึงข้อมูลทีม (โค้ดส่วนนี้เหมือนเดิม)
$sqlTeams = "SELECT * FROM teams WHERE tournament_id = :tid ORDER BY team_name ASC";
$stmtTeams = $conn->prepare($sqlTeams);
$stmtTeams->execute(['tid' => $tournament_id]);
$teams = $stmtTeams->fetchAll(PDO::FETCH_ASSOC);

// 6. วนลูปเพื่อสร้างข้อมูลของแต่ละทีม
foreach ($teams as $team) {

    // --- START: โค้ดสำหรับจัดการการขึ้นหน้าใหม่ ---
    // กำหนดความสูงขั้นต่ำที่ต้องการสำหรับ 1 บล็อกข้อมูล
    $required_height = 50; // หน่วยเป็น mm (ปรับเปลี่ยนได้)
    // คำนวณพื้นที่ที่เหลือในหน้า (A4 สูง 297mm)
    $remaining_space = 297 - $pdf->GetY() - 20; // 20 คือขอบล่างที่เผื่อไว้
    // ถ้าพื้นที่ที่เหลือไม่พอ ให้ขึ้นหน้าใหม่ก่อน
    if ($remaining_space < $required_height) {
        $pdf->AddPage();
    }
    // --- END: โค้ดสำหรับจัดการการขึ้นหน้าใหม่ ---

    // แสดงข้อมูลทีม
    $pdf->SetFont('THSarabunNew', '', 14);
    $pdf->Cell(0, 8, u_to_tis('ทีม: ' . $team['team_name']), 0, 1);
    
    $pdf->SetFont('THSarabunNew', '', 12);
    $coach_info = 'โค้ช: ' . $team['coach_name'] . 
                    ' | เบอร์โทร: ' . $team['coach_phone'] . 
                    ' | สังกัด: ' . $team['leader_school'];
    $pdf->MultiCell(0, 7, u_to_tis($coach_info));
    $pdf->Ln(2);

    // สร้างส่วนหัวของตารางสมาชิก
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->SetFillColor(242, 242, 242); // สีพื้นหลังเทาอ่อนสำหรับหัวตาราง
    $pdf->Cell(10, 8, u_to_tis('ลำดับ'), 1, 0, 'C', true);
    $pdf->Cell(40, 8, u_to_tis('ชื่อ-นามสกุล'), 1, 0, 'C', true);
    $pdf->Cell(30, 8, u_to_tis('ชื่อในเกม'), 1, 0, 'C', true);
    $pdf->Cell(12, 8, u_to_tis('อายุ'), 1, 0, 'C', true);
    $pdf->Cell(28, 8, u_to_tis('วันเกิด'), 1, 0, 'C', true);
    $pdf->Cell(30, 8, u_to_tis('เบอร์โทร'), 1, 0, 'C', true);
    $pdf->Cell(40, 8, u_to_tis('เซ็นชื่อ'), 1, 1, 'C', true); // เลข 1 สุดท้ายคือให้ขึ้นบรรทัดใหม่

    // ดึงข้อมูลและสร้างแถวของสมาชิก
    $sqlMembers = "SELECT * FROM team_members WHERE team_id = :team_id";
    $stmtMembers = $conn->prepare($sqlMembers);
    $stmtMembers->execute(['team_id' => $team['team_id']]);
    $members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

    $pdf->SetFont('THSarabunNew', '', 12);
    $i = 1;
    foreach ($members as $m) {
        // เพิ่มความสูงของช่องเป็น 12 เพื่อให้มีที่ว่างสำหรับเซ็นชื่อ
        $pdf->Cell(10, 12, $i, 1, 0, 'C');
        $pdf->Cell(40, 12, u_to_tis($m['member_name']), 1, 0);
        $pdf->Cell(30, 12, u_to_tis($m['game_name']), 1, 0);
        $pdf->Cell(12, 12, $m['age'], 1, 0, 'C');
        $pdf->Cell(28, 12, $m['birthdate'], 1, 0, 'C');
        $pdf->Cell(30, 12, $m['phone'], 1, 0, 'C');
        $pdf->Cell(40, 12, '', 1, 1); // ช่องเซ็นชื่อว่างๆ
        $i++;
    }
    
    $pdf->Ln(10); // เว้นระยะห่างระหว่างตารางของแต่ละทีม
}

// 7. ส่งออกไฟล์ PDF ไปยังเบราว์เซอร์
$pdf->Output('I', 'teams_list.pdf', true); // 'I' คือแสดงผลบนเบราว์เซอร์

?>