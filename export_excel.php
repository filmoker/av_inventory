<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    exit("Access Denied");
}
require_once 'db_connect.php';

// --- 1. รับค่าการกรองเหมือนหน้า report.php ---
$filter_campus = isset($_GET['campus']) ? $_GET['campus'] : '';
$filter_cat = isset($_GET['category']) ? $_GET['category'] : '';
$filter_loc = isset($_GET['location']) ? $_GET['location'] : '';
$filter_unit = isset($_GET['unit_id']) ? $_GET['unit_id'] : '';

$where_clauses = [];
if ($filter_campus != '') $where_clauses[] = "e.campus = '" . $conn->real_escape_string($filter_campus) . "'";
if ($filter_cat != '')    $where_clauses[] = "e.category_id = '" . $conn->real_escape_string($filter_cat) . "'";
if ($filter_loc != '')    $where_clauses[] = "e.location_id = '" . $conn->real_escape_string($filter_loc) . "'";
if ($filter_unit != '')   $where_clauses[] = "e.unit_id = '" . $conn->real_escape_string($filter_unit) . "'";

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- 2. ตั้งค่า Header เพื่อส่งออกเป็นไฟล์ Excel (.xls) ---
$filename = "AV_Inventory_Report_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// --- 3. ดึงข้อมูล ---
$sql = "SELECT e.*, c.category_name, l.location_name, u.unit_name 
        FROM equipments e
        LEFT JOIN categories c ON e.category_id = c.id
        LEFT JOIN locations l ON e.location_id = l.id
        LEFT JOIN units u ON e.unit_id = u.id
        $where_sql
        ORDER BY e.id DESC";
$result = $conn->query($sql);
?>

<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
</head>
<body>
    <strong>รายงานรายการครุภัณฑ์โสตทัศนูปกรณ์ (ข้อมูล ณ วันที่ <?php echo date('d/m/Y H:i'); ?>)</strong><br>
    <table border="1">
        <thead>
            <tr style="background-color: #f2f2f2; font-weight: bold; text-align: center;">
                <th>ลำดับ</th>
                <th>รหัสครุภัณฑ์</th>
                <th>ชื่อรายการ</th>
                <th>ยี่ห้อ</th>
                <th>รุ่น</th>
                <th>ซีเรียลนัมเบอร์</th>
                <th>วิทยาเขต</th>
                <th>หน่วยงาน</th>
                <th>สถานที่/ห้อง</th>
                <th>หมวดหมู่</th>
                <th>สถานะ</th>
                <th>วันที่รับเข้า</th>
                <th>หมายเหตุ</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $n = 1;
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td align='center'>".$n++."</td>";
                    echo "<td>" . htmlspecialchars($row['equipment_code']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['equipment_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['brand'] ?: '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['model'] ?: '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['serial_number'] ?: '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['campus']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['unit_name'] ?: '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['location_name'] ?: '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['category_name'] ?: '-') . "</td>";
                    echo "<td align='center'>" . htmlspecialchars($row['status']) . "</td>";
                    echo "<td align='center'>" . ($row['entry_date'] ? date('d/m/Y', strtotime($row['entry_date'])) : '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['remark'] ?: '-') . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='13' align='center'>ไม่พบข้อมูล</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>