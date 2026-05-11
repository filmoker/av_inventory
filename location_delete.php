<?php

require_once 'db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql_delete = "DELETE FROM locations WHERE id = $id";

    if ($conn->query($sql_delete) === TRUE) {
        echo "<script>alert('ลบข้อมูลสถานที่จัดเก็บเรียบร้อยแล้ว'); window.location.href='locations.php';</script>";
    } else {
        echo "<script>alert('ไม่สามารถลบได้ เนื่องจากอาจมีครุภัณฑ์ถูกเก็บไว้ในสถานที่นี้อยู่'); window.location.href='locations.php';</script>";
    }
} else {
    header("Location: locations.php");
}
?>