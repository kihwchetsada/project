<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();        
require '../db_connect.php'; 

// ตรวจสอบว่าเข้าสู่ระบบหรือยัง
if (!isset($_SESSION['conn']) || $_SESSION['conn']['role'] !== 'organizer') {
    die('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
}

$approved_by = $_SESSION['conn']['username'];

$stmt = $conn->prepare("SELECT role FROM users WHERE username = ?");
$stmt->execute([$approved_by]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'organizer') {
    die('คุณไม่มีสิทธิ์ทำรายการนี้');
}

//  รับค่าที่ส่งมาจากฟอร์ม
$team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
$action = $_POST['action'] ?? null;

// ตรวจสอบข้อมูลแบบแยก เพื่อให้ทราบว่าปัญหาอยู่ตรงไหน
if ($team_id <= 0) {
    die('Team ID ไม่ถูกต้อง: ' . $team_id);
}

if (!in_array($action, ['approve', 'reject'])) {
    die('Action ไม่ถูกต้อง: ' . $action);
}

// ตรวจสอบว่าทีมมีอยู่จริงหรือไม่ - ใช้ team_id แทน id
$stmt = $conn->prepare("SELECT team_id, team_name FROM teams WHERE team_id = ?");
$stmt->execute([$team_id]);
$team = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$team) {
    die('ไม่พบทีมที่มี Team ID: ' . $team_id);
}

//  อนุมัติหรือไม่อนุมัติทีม - ใช้ team_id แทน id
if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE teams SET is_approved = 1, approved_by = ? WHERE team_id = ?");
    $stmt->execute([$approved_by, $team_id]);
} else {
    $reason = trim($_POST['rejection_reason'] ?? '');
    if ($reason === '') {
        $reason = 'ไม่ระบุเหตุผล';
    }
    $stmt = $conn->prepare("UPDATE teams SET is_approved = 2, approved_by = ?, rejection_reason = ? WHERE team_id = ?");
    $stmt->execute([$approved_by, $reason, $team_id]);
}

//  ส่งกลับไปหน้าอนุมัติ
$_SESSION['approval_status'] = ($action === 'approve') ? 'approved' : 'rejected';
header('Location: organizer_approve_teams.php');
exit;