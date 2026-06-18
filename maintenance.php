<?php

require_once 'db_connect.php';

// ดึงข้อมูลประวัติการซ่อมบำรุง โดย JOIN กับตารางครุภัณฑ์เพื่อเอาชื่อและรหัสมาแสดง
$sql = "SELECT m.id, m.issue_detail, m.reported_date, m.fixed_date, m.repair_status, 
               e.equipment_code, e.name_model 
        FROM maintenance_logs m
        JOIN equipments e ON m.equipment_id = e.id
        ORDER BY m.id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติซ่อมบำรุง - ระบบบริหารครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .sidebar { background-color: #1e2b3c; min-height: 100vh; color: #fff; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid #2b3c53; }
        .sidebar a:hover, .sidebar a.active { background-color: #2b3c53; color: #fff; }
        .status-badge { width: 90px; display: inline-block; text-align: center; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- แถบเมนูด้านซ้าย -->
        <div class="col-md-2 sidebar p-0">
            <div class="p-4 text-center border-bottom border-secondary">
                <h5 class="m-0"><i class="fas fa-boxes"></i> AMDAT</h5>
            </div>
            <nav class="mt-3">
                <a href="index.php"><i class="fas fa-home me-2"></i> หน้าแรก</a>
                <a href="equipments.php"><i class="fas fa-desktop me-2"></i> รายการครุภัณฑ์</a>
                <a href="locations.php"><i class="fas fa-map-marker-alt me-2"></i> จัดการสถานที่</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> จัดการหมวดหมู่</a>
                <a href="report.php"><i class="fas fa-print me-2"></i> พิมพ์รายงานสรุปยอด</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
            </nav>
        </div>

        <!-- พื้นที่แสดงข้อมูลด้านขวา -->
        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4> ทะเบียนประวัติการแจ้งซ่อมและซ่อมบำรุง</h4>
                <a href="maintenance_add.php" class="btn btn-warning text-dark"> แจ้งซ่อมครุภัณฑ์</a>
            </div>

            <!-- ตารางแสดงข้อมูลการซ่อม -->
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="maintenanceTable" class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>วันที่แจ้ง</th>
                                    <th>รหัสครุภัณฑ์</th>
                                    <th>ชื่อ/รุ่นอุปกรณ์</th>
                                    <th>รายละเอียดอาการเสีย</th>
                                    <th>สถานะการซ่อม</th>
                                    <th>วันที่ซ่อมเสร็จ</th>
                                    <th class="text-center">อัปเดต</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($row['reported_date'])); ?></td>
                                        <td><strong><?php echo $row['equipment_code']; ?></strong></td>
                                        <td><?php echo $row['name_model']; ?></td>
                                        <td><span class="text-danger"><?php echo $row['issue_detail']; ?></span></td>
                                        <td>
                                            <?php 
                                                $badgeClass = 'bg-secondary';
                                                if($row['repair_status'] == 'รอตรวจสอบ') $badgeClass = 'bg-danger';
                                                elseif($row['repair_status'] == 'กำลังซ่อม') $badgeClass = 'bg-warning text-dark';
                                                elseif($row['repair_status'] == 'ซ่อมเสร็จแล้ว') $badgeClass = 'bg-success';
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?> status-badge"><?php echo $row['repair_status']; ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                                echo ($row['fixed_date']) ? date('d/m/Y', strtotime($row['fixed_date'])) : '<span class="text-muted">-</span>'; 
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <!-- ปุ่มสำหรับเข้าไปอัปเดตสถานะการซ่อม -->
                                            <a href="maintenance_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info" title="อัปเดตสถานะ"><i class="fas fa-edit"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">ไม่มีข้อมูลประวัติการซ่อมบำรุง</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#equipmentTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json"
            },
            "order": [], 
            "columnDefs": [
                { "orderable": false, "targets": [0, 2, 3, 4, 5] } 
            ]
        });
    });
</script>

</body>
</html>