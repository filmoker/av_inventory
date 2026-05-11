<?php

require_once 'db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql_delete = "DELETE FROM categories WHERE id = $id";

    if ($conn->query($sql_delete) === TRUE) {
        echo "<script>alert('ลบข้อมูลหมวดหมู่เรียบร้อยแล้ว'); window.location.href='categories.php';</script>";
    } else {
        echo "<script>alert('ไม่สามารถลบได้ เนื่องจากอาจมีครุภัณฑ์กำลังผูกกับหมวดหมู่นี้อยู่'); window.location.href='categories.php';</script>";
    }
} else {
    header("Location: categories.php");
}
?>