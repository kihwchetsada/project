<?php
$host = "localhost";         
$dbname = "test_login";  
$username = "root";          
$password = "";              

try {
    $userDb = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // ตั้งค่าให้ PDO แจ้ง error แบบ exception
    $userDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("การเชื่อมต่อล้มเหลว: " . $e->getMessage());
}
?>
