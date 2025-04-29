<?php
$host = "localhost";         // โฮสต์ของ MySQL (ส่วนใหญ่ใช้ localhost)
$dbname = "test_login";  // ชื่อฐานข้อมูล
$username = "root";          // ชื่อผู้ใช้ฐานข้อมูล
$password = "";              // รหัสผ่าน (ถ้าใช้ XAMPP มักจะเว้นว่าง)

$conn = new mysqli($host, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}
?>
