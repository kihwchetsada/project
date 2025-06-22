<?php
session_start();
require 'db.php';         // user_db
require 'db_connect.php'; // competition_db

session_start();

if (!isset($_SESSION['userData']) || $_SESSION['userData']['role'] !== 'organizer') {
    die('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
}

$approved_by = $_SESSION['userData']['username'];

$team_id = $_POST['team_id'] ?? null;
$action = $_POST['action'] ?? null;
$approved_by = $_SESSION['userData']['username'];

// ตรวจสอบ role
$stmt = $userDb->prepare("SELECT role FROM users WHERE username = ?");
$stmt->execute([$approved_by]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'organizer') {
    die('คุณไม่มีสิทธิ์ทำรายการนี้');
}

if (!$team_id || !in_array($action, ['approve', 'reject'])) {
    die('ข้อมูลไม่ครบถ้วน');
}

if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE teams SET is_approved = 1, approved_by = ? WHERE id = ?");
    $stmt->execute([$approved_by, $team_id]);
} else {
    $reason = trim($_POST['rejection_reason'] ?? '');
    if ($reason === '') {
        $reason = 'ไม่ระบุเหตุผล';
    }
    $stmt = $conn->prepare("UPDATE teams SET is_approved = 2, approved_by = ?, rejection_reason = ? WHERE id = ?");
    $stmt->execute([$approved_by, $reason, $team_id]);
}

header('Location: organizer_approve_teams.php');
exit;
