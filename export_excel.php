<?php
require_once 'db_connect.php';

// 1. รับค่าเงื่อนไขเดียวกันกับหน้า report
$filter_campus = isset($_GET['campus']) ? $_GET['campus'] : '';
$filter_cat = isset($_GET['category']) ? $_GET['category'] : '';
$filter_loc = isset($_GET['location']) ? $_GET['location'] : '';

$where_clauses = [];
if ($filter_campus != '') $where_clauses[] = "e.campus = '" . $conn->real_escape_string($filter_campus) . "'";
if ($filter_cat != '') $where_clauses[] = "e.category_id = '" . $conn->real_escape_string($filter_cat) . "'";
if ($filter_loc != '') $where_clauses[] = "e.location_id = '" . $conn->real_escape_string($filter_loc) . "'";

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// 2. Query ข้อมูลที่ต้องการ export
$sql = "SELECT e.equipment_code, e.equipment_name, e.brand, e.model, e.campus, l.location_name, c.category_name, e.status
        FROM equipments e
        LEFT JOIN categories c ON e.category_id = c.id
        LEFT JOIN locations l ON e.location_id = l.id
        $where_sql
        ORDER BY e.campus ASC, e.id DESC";
$result = $conn->query($sql);

// 3. ตั้งค่า Header เพื่อบังคับดาวน์โหลดเป็นไฟล์ CSV (ที่ Excel เปิดได้)
$filename = "report_equipment_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '";');

// 4. ใส่ BOM เพื่อให้ Excel อ่านภาษาไทยออก (UTF-8)
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// เขียนหัวตาราง
fputcsv($output, array('รหัสครุภัณฑ์', 'ชื่อครุภัณฑ์', 'ยี่ห้อ', 'รุ่น', 'วิทยาเขต', 'สถานที่จัดเก็บ', 'หมวดหมู่', 'สถานะ'));

// เขียนข้อมูลลงในแถวต่างๆ
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, array(
            $row['equipment_code'],
            $row['equipment_name'],
            $row['brand'],
            $row['model'],
            $row['campus'],
            $row['location_name'],
            $row['category_name'],
            $row['status']
        ));
    }
}

fclose($output);
exit();
?>