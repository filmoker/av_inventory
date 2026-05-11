<?php
//  เริ่มต้น Session เพื่อเข้าไปจัดการค่าที่ค้างอยู่
session_start();

//  ล้างข้อมูลทั้งหมดใน Session
session_unset();

//  ทำลาย Session ทิ้งจากระบบ Server
session_destroy();

//  ส่งผู้ใช้งานกลับไปยังหน้าแรก  
header("Location: login.php");
exit();
?>