<?php
$host = "localhost";         
$dbname = "test_login";  
$username = "root";          
$password = "";              

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // ตั้งค่าให้ PDO แจ้ง error แบบ exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("การเชื่อมต่อล้มเหลว: " . $e->getMessage());
}
?>
