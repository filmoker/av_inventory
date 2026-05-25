<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
require_once 'db_connect.php';

// เช็กว่ามีการส่ง Array ของ ID มาหรือไม่
if (isset($_POST['eq_ids']) && is_array($_POST['eq_ids'])) {
    
    // แปลงทุก ID ให้เป็นตัวเลขเพื่อความปลอดภัย
    $ids = array_map('intval', $_POST['eq_ids']);
    $id_list = implode(',', $ids); // แปลงเป็นข้อความเช่น "1,2,3,4"

    // สเต็ป 1: ดึงรหัสครุภัณฑ์ทั้งหมดที่จะโดนลบ มาเตรียมไว้เขียน Log
    $query = $conn->query("SELECT equipment_code FROM equipments WHERE id IN ($id_list)");
    $deleted_codes = [];
    while($row = $query->fetch_assoc()) {
        $deleted_codes[] = $row['equipment_code'];
    }
    $codes_string = implode(', ', $deleted_codes);
    $count = count($deleted_codes);

    if ($count > 0) {
        // สั่งลบข้อมูลรวดเดียว
        $sql_delete = "DELETE FROM equipments WHERE id IN ($id_list)";
        
        if ($conn->query($sql_delete) === TRUE) {
            
            // บันทึก Log แค่บรรทัดเดียว สรุปยอดรวดเดียว!
            $username = $_SESSION['username'];
            save_log($conn, $username, 'ลบข้อมูล', "ลบครุภัณฑ์แบบกลุ่มจำนวน {$count} รายการ (รหัส: {$codes_string})");

            echo "<script>alert('ลบข้อมูล {$count} รายการเรียบร้อยแล้ว'); window.location.href='equipments.php';</script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาด: " . $conn->error . "'); window.history.back();</script>";
        }
    }
} else {
    echo "<script>alert('กรุณาเลือกรายการที่ต้องการลบอย่างน้อย 1 รายการ'); window.history.back();</script>";
}
?>