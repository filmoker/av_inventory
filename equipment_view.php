<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // ดึงข้อมูลครุภัณฑ์แบบละเอียด พร้อมชื่อหมวดหมู่และสถานที่
    $sql = "SELECT e.*, c.category_name, l.location_name 
            FROM equipments e 
            LEFT JOIN categories c ON e.category_id = c.id 
            LEFT JOIN locations l ON e.location_id = l.id 
            WHERE e.id = $id";
    $result = $conn->query($sql);
    $eq = $result->fetch_assoc();

    // ดึงประวัติการซ่อมของชิ้นนี้โดยเฉพาะ
    $sql_history = "SELECT * FROM maintenance_logs WHERE equipment_id = $id ORDER BY reported_date DESC";
    $res_history = $conn->query($sql_history);
} else {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายละเอียดครุภัณฑ์ - <?php echo $eq['name_model']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .info-label { color: #6c757d; font-weight: 500; }
    </style>
</head>
<body class="p-4">
    <div class="container bg-white p-4 shadow-sm rounded">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-info-circle text-primary me-2"></i> รายละเอียดข้อมูลครุภัณฑ์</h4>
            <a href="index.php" class="btn btn-secondary btn-sm">กลับหน้าหลัก</a>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 border-end">
                <p><span class="info-label">รหัสครุภัณฑ์:</span> <?php echo $eq['equipment_code']; ?></p>
                <p><span class="info-label">ชื่อ/รุ่นอุปกรณ์:</span> <strong><?php echo $eq['name_model']; ?></strong></p>
                <p><span class="info-label">Serial Number (S/N):</span> <?php echo $eq['serial_number'] ?: '-'; ?></p>
                <p><span class="info-label">หมวดหมู่:</span> <?php echo $eq['category_name']; ?></p>
            </div>
            <div class="col-md-6 ps-md-4">
                <p><span class="info-label">สถานที่จัดเก็บ:</span> <?php echo $eq['location_name']; ?></p>
                <p><span class="info-label">สถานะปัจจุบัน:</span> 
                    <span class="badge <?php echo ($eq['status'] == 'พร้อมใช้งาน') ? 'bg-success' : (($eq['status'] == 'ชำรุด') ? 'bg-danger' : 'bg-warning text-dark'); ?>">
                        <?php echo $eq['status']; ?>
                    </span>
                </p>
                <p><span class="info-label">วันที่เริ่มรับเข้า:</span> <?php echo date('d/m/Y', strtotime($eq['created_at'])); ?></p>
            </div>
        </div>

        <hr>
        <h5><i class="fas fa-history me-2"></i> ประวัติการซ่อมบำรุงของอุปกรณ์นี้</h5>
        <div class="table-responsive">
            <table class="table table-sm mt-2">
                <thead class="table-light">
                    <tr>
                        <th>วันที่แจ้ง</th>
                        <th>อาการที่พบ</th>
                        <th>สถานะ</th>
                        <th>วันที่ซ่อมเสร็จ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($res_history->num_rows > 0): ?>
                        <?php while($h = $res_history->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($h['reported_date'])); ?></td>
                            <td><?php echo $h['issue_detail']; ?></td>
                            <td><?php echo $h['repair_status']; ?></td>
                            <td><?php echo $h['fixed_date'] ? date('d/m/Y', strtotime($h['fixed_date'])) : '-'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted">ไม่เคยมีประวัติการส่งซ่อม</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>