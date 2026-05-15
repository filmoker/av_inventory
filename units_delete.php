<?php
require_once 'db_connect.php';

if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    
    // ทำการลบข้อมูล
    $sql = "DELETE FROM units WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        // ลบสำเร็จ ให้กลับไปหน้า units.php
        header("Location: units.php");
        exit();
    } else {
        echo "เกิดข้อผิดพลาดในการลบข้อมูล: " . $conn->error;
    }
} else {
    // ถ้าไม่มี ID ส่งมา ให้เด้งกลับไปหน้า units.php
    header("Location: units.php");
    exit();
}
?>