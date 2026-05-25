<?php
$servername = "localhost";
$username = "root";         
$password = "";             
$dbname = "internship_inventory_db"; 

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function save_log($conn, $username, $action, $details) {
    $sql = "INSERT INTO activity_logs (username, action, details) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $action, $details);
    $stmt->execute();
    $stmt->close();
}

?>