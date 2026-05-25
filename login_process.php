<?php
session_start();
// 1. ดึงไฟล์เชื่อมต่อฐานข้อมูลมา (เผื่อต้องใช้ในอนาคต)
require_once 'db_connect.php'; 

$login_id = strtolower(trim($_POST['username'])); 
$login_pwd = $_POST['password'];

if (empty($login_id) || empty($login_pwd)) {
    echo "<script>alert('กรุณากรอก Username และ Password'); window.location.replace('login.php');</script>";
    exit();
}

// ตั้งค่า LDAP
$ldaprdn = "uid={$login_id},dc=swu,dc=ac,dc=th";
$ldapconn = ldap_connect("ldap://ldap.swu.ac.th") or die("Could not connect to LDAP server.");

if ($ldapconn) {
    // 2. ตรวจสอบรหัสผ่านกับ LDAP
    $ldapbind = @ldap_bind($ldapconn, $ldaprdn, $login_pwd);

    if ($ldapbind) {
        // =========================================
        // 1: ล็อกอินผ่าน LDAP ของมหาลัยสำเร็จ!
        // =========================================
        $full_name = $login_id; // ตั้งค่าเริ่มต้นเป็นรหัสไว้ก่อน
        
        // วิ่งไปขอชื่อ-นามสกุลจาก API ของ SWU
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, "https://mobile.swu.ac.th/api/person?q={$login_id}");
        $result_api = curl_exec($ch);
        curl_close($ch);

        if ($result_api) {
            $json = json_decode($result_api, true);
            if (isset($json['total']) && $json['total'] > 0) {
                foreach ($json['data'] as $person) {
                    if (trim($person['user_id']) == $login_id) {
                        $full_name = $person['full_name'];
                        break;
                    }
                }
            }
        }

        // =========================================
        // 2: ให้สิทธิ์เข้าเว็บทันทีโดยไม่ต้องเช็ก SQL 
        // =========================================
        $_SESSION['is_logged_in'] = true;
        $_SESSION['username'] = $login_id;
        $_SESSION['full_name'] = $full_name; 

        // บันทึกประวัติล็อกอิน
        save_log($conn, $login_id, 'เข้าสู่ระบบ', 'เข้าใช้งานระบบบริหารจัดการครุภัณฑ์');

        // ส่งตรงเข้าหน้าหลักทันที
        header("Location: index.php");
        exit();

    } else {
        // =========================================
        // รหัสผ่านผิด หรือไม่มีชื่อในระบบมหาวิทยาลัย
        // =========================================
        echo "<script>
                alert('Username หรือ Password ไม่ถูกต้อง');
                window.location.replace('login.php');
              </script>";
    }
    ldap_close($ldapconn);
}
?>