<?php
// 1. เปิดใช้งาน Session เพื่อให้ระบบรู้จักว่ากำลังจะลบ Session ของใคร
session_start();

// 2. ล้างค่าตัวแปร Session ทั้งหมดที่เราเคยสร้างไว้ (เช่น is_logged_in, username, full_name)
$_SESSION = array();

// 3. ลบ Cookie ที่เก็บ Session ID ในเครื่องผู้ใช้ (เพื่อความปลอดภัยขั้นสุด ป้องกันการสวมรอย)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. ทำลาย Session ทิ้งอย่างเป็นทางการ
session_destroy();

// 5. เด้งผู้ใช้กลับไปที่หน้าล็อกอิน
header("Location: login.php");
exit();
?>