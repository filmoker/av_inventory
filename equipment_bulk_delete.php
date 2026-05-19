<?php
require_once 'db_connect.php';

// เช็กว่ามีการส่งค่า ids มาหรือไม่
if (isset($_GET['ids']) && !empty($_GET['ids'])) {
    
    // รับค่า ids ที่ต่อกันมาด้วยลูกน้ำ (เช่น 384,383,382)
    $ids_string = $_GET['ids'];
    
    // ป้องกัน SQL Injection โดยการหั่นข้อความและแปลงเป็นตัวเลขเท่านั้น
    $id_array = explode(',', $ids_string);
    $clean_ids = [];
    foreach ($id_array as $id) {
        $clean_id = intval(trim($id));
        if ($clean_id > 0) {
            $clean_ids[] = $clean_id;
        }
    }
    
    // ถ้ารายการ id มีอยู่จริง
    if (count($clean_ids) > 0) {
        $ids_for_query = implode(',', $clean_ids);
        $sql = "DELETE FROM equipments WHERE id IN ($ids_for_query)";
        
        if ($conn->query($sql) === TRUE) {
            header("Location: equipments.php?msg=delete_success");
            exit();
        } else {
            echo "เกิดข้อผิดพลาดในการลบข้อมูล: " . $conn->error;
            exit();
        }
    } else {
        header("Location: equipments.php?msg=error");
        exit();
    }
} else {
    header("Location: equipments.php");
    exit();
}
?>