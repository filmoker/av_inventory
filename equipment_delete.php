<?php
session_start(); // เปิด Session เพื่อดึงชื่อคนล็อกอิน

// ป้องกันคนก๊อปปี้ URL มาเข้าโดยไม่ได้ล็อกอิน
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// ตรวจสอบว่ามีการส่งค่า ID มาเพื่อลบหรือไม่
if (isset($_GET['id'])) {
    $id = (int)$_GET['id']; // แปลงเป็นตัวเลขเพื่อป้องกันการแฮก (SQL Injection)
    
    // ดึงข้อมูลครุภัณฑ์มาเก็บไว้ก่อนลบ (เพื่อเอาไปเขียนลง Log)
    $query = $conn->query("SELECT equipment_code, equipment_name FROM equipments WHERE id = $id");
    
    if ($query->num_rows > 0) {
        $eq = $query->fetch_assoc();
        $eq_code = $eq['equipment_code'];
        $eq_name = $eq['equipment_name'];

        // คำสั่ง SQL สำหรับลบข้อมูล
        $sql_delete = "DELETE FROM equipments WHERE id = $id";

        // ทำการลบข้อมูลและเช็คผลลัพธ์
        if ($conn->query($sql_delete) === TRUE) {
            
            // บันทึกประวัติ (Log) ว่าใครลบอะไรไป
            $username = $_SESSION['username'];
            save_log($conn, $username, 'ลบข้อมูล', "ลบครุภัณฑ์ รหัส: {$eq_code} ({$eq_name}) ออกจากระบบ");

            // ถ้าลบสำเร็จ แจ้งเตือนแล้วกลับไปหน้าตาราง
            echo "<script>alert('ลบข้อมูลครุภัณฑ์ออกจากระบบเรียบร้อยแล้ว'); window.location.href='equipments.php';</script>";
        } else {
            // ถ้าเกิดข้อผิดพลาด
            echo "<script>alert('เกิดข้อผิดพลาดในการลบข้อมูล: " . $conn->error . "'); window.location.href='equipments.php';</script>";
        }
    } else {
        echo "<script>alert('ไม่พบข้อมูลครุภัณฑ์ที่ต้องการลบ'); window.location.href='equipments.php';</script>";
    }
} else {
    // ถ้าไม่มีการส่ง ID มา ให้เด้งกลับไปหน้าตารางแบบเงียบๆ
    header("Location: equipments.php");
    exit();
}
?>