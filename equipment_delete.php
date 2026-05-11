<?php

require_once 'db_connect.php';

// ตรวจสอบว่ามีการส่งค่า ID มาเพื่อลบหรือไม่
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // คำสั่ง SQL สำหรับลบข้อมูล
    $sql_delete = "DELETE FROM equipments WHERE id = $id";

    // ทำการลบข้อมูลและเช็คผลลัพธ์
    if ($conn->query($sql_delete) === TRUE) {
        // ถ้าลบสำเร็จ แจ้งเตือนแล้วกลับไปหน้าตาราง
        echo "<script>alert('ลบข้อมูลครุภัณฑ์ออกจากระบบเรียบร้อยแล้ว'); window.location.href='equipments.php';</script>";
    } else {
        // ถ้าเกิดข้อผิดพลาด
        echo "<script>alert('เกิดข้อผิดพลาดในการลบข้อมูล: " . $conn->error . "'); window.location.href='equipments.php';</script>";
    }
} else {
    // ถ้าไม่มีการส่ง ID มา ให้เด้งกลับไปหน้าตารางแบบเงียบๆ
    header("Location: equipments.php");
    exit();
}
?>